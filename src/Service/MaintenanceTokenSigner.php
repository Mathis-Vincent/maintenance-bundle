<?php

namespace MathisVincent\MaintenanceBundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Handles cryptographic signing and verification of bypass tokens using HMAC.
 */
final class MaintenanceTokenSigner
{
    private string $secret;

    public function __construct(ParameterBagInterface $params)
    {
        $secret = $params->get('kernel.secret');
        \assert(\is_string($secret));
        $this->secret = $secret;
    }

    public function generateToken(int $lifetimeSeconds, string $level, string $id): string
    {
        $payload = [
            'exp' => time() + $lifetimeSeconds,
            'level' => $level,
            'id' => $id,
            'nonce' => bin2hex(random_bytes(16)),
        ];

        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $base64Payload = rtrim(strtr(base64_encode($jsonPayload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $base64Payload, $this->secret);

        return $base64Payload.'.'.$signature;
    }

    public function validateToken(string $token, string $level, string $id): bool
    {
        $parts = explode('.', $token);
        if (2 !== count($parts)) {
            return false;
        }

        [$base64Payload, $signature] = $parts;

        $expectedSignature = hash_hmac('sha256', $base64Payload, $this->secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $jsonPayload = base64_decode(strtr($base64Payload, '-_', '+/'));
        /** @var array{exp?: int, level?: string, id?: string}|null $payload */
        $payload = json_decode($jsonPayload, true);

        if (!is_array($payload) || !isset($payload['exp'])) {
            return false;
        }

        if (time() > $payload['exp']) {
            return false;
        }

        // a token only bypasses the level and the maintenance it was issued for
        return ($payload['level'] ?? null) === $level && ($payload['id'] ?? null) === $id;
    }
}
