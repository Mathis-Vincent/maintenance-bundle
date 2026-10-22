<?php

namespace MathisVincent\MaintenanceBundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Handles cryptographic signing and verification of bypass tokens using HMAC.
 */
class MaintenanceTokenSigner
{
    private string $secret;

    public function __construct(ParameterBagInterface $params)
    {
        $this->secret = $params->get('kernel.secret');
    }

    public function generateToken(int $lifetimeSeconds): string
    {
        $payload = [
            'exp' => time() + $lifetimeSeconds,
            'nonce' => bin2hex(random_bytes(16))
        ];

        $jsonPayload = json_encode($payload);
        $base64Payload = rtrim(strtr(base64_encode($jsonPayload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $base64Payload, $this->secret);

        return $base64Payload . '.' . $signature;
    }

    public function validateToken(string $token): bool
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        [$base64Payload, $signature] = $parts;

        $expectedSignature = hash_hmac('sha256', $base64Payload, $this->secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $jsonPayload = base64_decode(strtr($base64Payload, '-_', '+/'));
        $payload = json_decode($jsonPayload, true);

        if (!$payload || !isset($payload['exp'])) {
            return false;
        }

        if (time() > $payload['exp']) {
            return false;
        }

        return true;
    }
}