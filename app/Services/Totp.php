<?php

namespace App\Services;

/** Implementasi TOTP (RFC 6238) sederhana untuk MFA step-up, kompatibel Google/Microsoft Authenticator. */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 16): string
    {
        $s = '';
        for ($i = 0; $i < $length; $i++) $s .= self::ALPHABET[random_int(0, 31)];
        return $s;
    }

    public static function code(string $secret, ?int $time = null): string
    {
        $counter = intdiv($time ?? time(), 30);
        $key = self::base32Decode($secret);
        $bin = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $num = ((ord($hash[$offset]) & 0x7f) << 24) | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8) | (ord($hash[$offset + 3]) & 0xff);
        return str_pad((string) ($num % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::code($secret, time() + $i * 30), $code)) return true;
        }
        return false;
    }

    public static function uri(string $secret, string $label): string
    {
        return 'otpauth://totp/'.rawurlencode('SENTRA:'.$label).'?secret='.$secret.'&issuer=SENTRA';
    }

    private static function base32Decode(string $b32): string
    {
        $b32 = strtoupper($b32); $bits = ''; $out = '';
        foreach (str_split($b32) as $c) {
            $v = strpos(self::ALPHABET, $c);
            if ($v === false) continue;
            $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
        }
        foreach (str_split($bits, 8) as $byte) if (strlen($byte) === 8) $out .= chr(bindec($byte));
        return $out;
    }
}
