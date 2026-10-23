<?php

namespace MathisVincent\MaintenanceBundle\Tests\Unit;

use MathisVincent\MaintenanceBundle\Service\MaintenanceTokenSigner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class MaintenanceTokenSignerTest extends TestCase
{
    public function testGeneratedTokenIsValid(): void
    {
        $signer = $this->signer();

        self::assertTrue($signer->validateToken($signer->generateToken(60, 'admin'), 'admin'));
    }

    public function testTokenFromAnotherLevelIsRejected(): void
    {
        $signer = $this->signer();

        self::assertFalse($signer->validateToken($signer->generateToken(60, 'admin'), 'default'));
    }

    public function testTokensAreUnique(): void
    {
        $signer = $this->signer();

        self::assertNotSame($signer->generateToken(60, 'admin'), $signer->generateToken(60, 'admin'));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $signer = $this->signer();

        self::assertFalse($signer->validateToken($signer->generateToken(-1, 'admin'), 'admin'));
    }

    public function testTokenSignedWithAnotherSecretIsRejected(): void
    {
        self::assertFalse($this->signer('secret-a')->validateToken($this->signer('secret-b')->generateToken(60, 'admin'), 'admin'));
    }

    public function testTamperedPayloadIsRejected(): void
    {
        [, $signature] = explode('.', $this->signer()->generateToken(60, 'admin'));
        $payload = rtrim(strtr(base64_encode('{"exp":9999999999}'), '+/', '-_'), '=');

        self::assertFalse($this->signer()->validateToken($payload.'.'.$signature, 'admin'));
    }

    public function testSignedPayloadWithoutExpiryIsRejected(): void
    {
        $payload = rtrim(strtr(base64_encode('{"nonce":"x"}'), '+/', '-_'), '=');

        self::assertFalse($this->signer()->validateToken($payload.'.'.hash_hmac('sha256', $payload, 'secret'), 'admin'));
    }

    #[DataProvider('malformedTokens')]
    public function testMalformedTokenIsRejected(string $token): void
    {
        self::assertFalse($this->signer()->validateToken($token, 'admin'));
    }

    public static function malformedTokens(): iterable
    {
        yield 'empty' => [''];
        yield 'no separator' => ['abc'];
        yield 'too many parts' => ['a.b.c'];
    }

    private function signer(string $secret = 'secret'): MaintenanceTokenSigner
    {
        return new MaintenanceTokenSigner(new ParameterBag(['kernel.secret' => $secret]));
    }
}
