<?php

namespace XUI\Outbounds\Protocols\Shadowsocks;

use JSON\json;
use stdClass;

class Settings
{
    public string $address;
    public int $port;
    public string $password;
    public string $method;
    public string $email;
    public bool $uot;
    public const NETWORK_TCP_UDP = 'tcp,udp';
    public const NETWORK_TCP = 'tcp';
    public const NETWORK_UDP = 'udp';
    public const METHOD_2022_blake3_aes_128_gcm = '2022-blake3-aes-128-gcm';
    public const METHOD_2022_blake3_aes_256_gcm = '2022-blake3-aes-256-gcm';
    public const METHOD_2022_blake3_chacha20_poly1305 = '2022-blake3-chacha20-poly1305';
    public const METHOD_aes_256_gcm = 'aes-256-gcm';
    public const METHOD_aes_128_gcm = 'aes-128-gcm';
    public const METHOD_chacha20_poly1305 = 'chacha20-poly1305';
    public const METHOD_chacha20_ietf_poly1305 = 'chacha20-ietf-poly1305';
    public const METHOD_xchacha20_poly1305 = 'xchacha20-poly1305';
    public const METHOD_xchacha20_ietf_poly1305 = 'xchacha20-ietf-poly1305';
    public const METHOD_none = 'none';
    public const METHOD_plain = 'plain';

    public function __construct(string $address, int $port, string $password, string $method, string $email, bool $uot = true)
    {
        $this->address = $address;
        $this->port = $port;
        $this->password = $password;
        $this->method = $method;
        $this->email = $email;
        $this->uot = $uot;
    }

    /**
     * Returns fully structured settings for xray json config
     * @return array
     */
    public function settings(): array
    {
        return [
            'servers' => [
                [
                    'address' => $this->address,
                    'port' => $this->port,
                    'password' => $this->password,
                    'email' => $this->email,
                    'method' => $this->method,
                    'uot' => $this->uot
                ]
            ]
        ];
    }

    public static function random(int $length = 32): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 1; $i <= $length; $i++) :
            $out .= $chars[rand(0, strlen($chars) - 1)];
        endfor;
        return $out;
    }

    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}