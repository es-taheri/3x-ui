<?php

namespace XUI\Helper;

use JSON\json;

/**
 * Shared constants and small utility helpers used across the library.
 *
 * Every method here is stateless, so the class is safe to call from anywhere
 * without instantiating it.
 */
class Statics
{
    /** Ask {@see output()} to return a JSON encoded string. */
    public const OUTPUT_JSON = 111;

    /** Ask {@see output()} to return a stdClass object tree. */
    public const OUTPUT_OBJECT = 112;

    /** Ask {@see output()} to return a plain PHP array. */
    public const OUTPUT_ARRAY = 113;

    /** Size of one byte. */
    public const UNIT_BYTE = 1;

    /** Size of one kilobyte in bytes. */
    public const UNIT_KILOBYTE = 1024;

    /** Size of one megabyte in bytes. */
    public const UNIT_MEGABYTE = 1024 * self::UNIT_KILOBYTE;

    /** Size of one gigabyte in bytes. */
    public const UNIT_GIGABYTE = 1024 * self::UNIT_MEGABYTE;

    /** Size of one terabyte in bytes. */
    public const UNIT_TERABYTE = 1024 * self::UNIT_GIGABYTE;

    /** Default directory used to persist cookie jars between requests. */
    public const COOKIE_DIR = __DIR__ . '/../.cookie';

    /** Lower-case hexadecimal characters, used by {@see random()}. */
    private const LOWER_HEX = '0123456789abcdef';

    /** Mixed alpha-numeric characters, used by {@see random()}. */
    private const ALPHANUMERIC = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Normalise decoded JSON data to the requested output type.
     *
     * Scalars are returned unchanged, which keeps boolean/string `obj` values
     * (2FA state, CSRF token, ...) usable.
     *
     * @param mixed $data Decoded JSON data (or a raw JSON string).
     * @param int $type One of the OUTPUT_* constants.
     * @return mixed
     */
    public static function output(mixed $data, int $type = self::OUTPUT_JSON): mixed
    {
        if (!is_array($data) && !is_object($data) && !is_string($data)) {
            return $data;
        }
        return match ($type) {
            self::OUTPUT_JSON => json::to_json($data),
            self::OUTPUT_OBJECT => json::to_object($data),
            default => json::to_array($data),
        };
    }

    /**
     * Generate a cryptographically secure random string.
     *
     * @param int $length Number of characters to generate.
     * @param string $characters Pool of characters to pick from.
     * @return string
     */
    public static function random(int $length = 32, string $characters = self::ALPHANUMERIC): string
    {
        $length = max(1, $length);
        $max = strlen($characters) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $characters[random_int(0, $max)];
        }
        return $out;
    }

    /**
     * Generate a random UUID v4.
     *
     * @return string
     */
    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Current Unix time in milliseconds, the format used by the panel API.
     *
     * @return int
     */
    public static function now(): int
    {
        return self::to_millis(time());
    }

    /**
     * Convert Unix seconds to Unix milliseconds.
     *
     * @param int $seconds Unix timestamp in seconds.
     * @return int
     */
    public static function to_millis(int $seconds): int
    {
        return $seconds * 1000;
    }

    /**
     * Convert Unix milliseconds to Unix seconds.
     *
     * @param int $milliseconds Unix timestamp in milliseconds.
     * @return int
     */
    public static function to_seconds(int $milliseconds): int
    {
        return intdiv($milliseconds, 1000);
    }

    /**
     * Build a Unix millisecond timestamp from a relative number of days.
     *
     * @param int $days Days from now (may be negative).
     * @return int
     */
    public static function days_from_now(int $days): int
    {
        return self::to_millis(time() + ($days * 86400));
    }

    /**
     * Convert loose panel values (`"true"`, `"1"`, `"on"`, ...) to bool.
     *
     * @param mixed $value Raw value coming from the panel API or a form.
     * @return bool
     */
    public static function bool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return $value !== 0;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
        }
        return false;
    }

    /**
     * Decode a JSON value to an array, tolerating arrays/objects/strings.
     *
     * @param mixed $data Value to normalise.
     * @return array
     */
    public static function array(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }
        if (is_object($data)) {
            return json::to_array($data) ?: [];
        }
        if (is_string($data) && $data !== '') {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
}
