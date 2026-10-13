<?php

declare(strict_types=1);

namespace Library\Adapters\Inbound\Http;

use Illuminate\Http\Request;

final class ApiToken
{
    public static function issue(string $subject, string $role): string
    {
        $header = self::encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = self::encode([
            'sub' => $subject,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + (int) env('JWT_TTL', 28800),
        ]);
        $signature = self::encode(hash_hmac('sha256', "{$header}.{$payload}", (string) env('JWT_SECRET'), true));

        return "{$header}.{$payload}.{$signature}";
    }

    public static function fromRequest(Request $request): array
    {
        $value = $request->bearerToken();
        if ($value === null) {
            throw new ApiException('UNAUTHENTICATED', 'Se requiere un token de acceso.', 401);
        }

        $parts = explode('.', $value);
        if (count($parts) !== 3) {
            throw new ApiException('UNAUTHENTICATED', 'El token no es válido o ha vencido.', 401);
        }

        $expected = self::encode(hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", (string) env('JWT_SECRET'), true));
        $payload = json_decode(self::decode($parts[1]), true);
        if (!hash_equals($expected, $parts[2]) || !is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            throw new ApiException('UNAUTHENTICATED', 'El token no es válido o ha vencido.', 401);
        }

        return $payload;
    }

    private static function encode(string|array $value): string
    {
        $json = is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value;
        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    private static function decode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true) ?: '';
    }
}
