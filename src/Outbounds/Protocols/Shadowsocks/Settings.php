<?php

namespace XUI\Outbounds\Protocols\Shadowsocks;

/**
 * `settings` object of a Shadowsocks outbound.
 *
 * @link https://xtls.github.io/en/config/outbounds/shadowsocks.html
 */
class Settings
{
    /** Cipher: Shadowsocks 2022 AES-128-GCM. */
    public const METHOD_2022_blake3_aes_128_gcm = '2022-blake3-aes-128-gcm';

    /** Cipher: Shadowsocks 2022 AES-256-GCM. */
    public const METHOD_2022_blake3_aes_256_gcm = '2022-blake3-aes-256-gcm';

    /** Cipher: Shadowsocks 2022 ChaCha20-Poly1305. */
    public const METHOD_2022_blake3_chacha20_poly1305 = '2022-blake3-chacha20-poly1305';

    /** Cipher: AES-256-GCM. */
    public const METHOD_aes_256_gcm = 'aes-256-gcm';

    /** Cipher: AES-128-GCM. */
    public const METHOD_aes_128_gcm = 'aes-128-gcm';

    /** Cipher: ChaCha20-Poly1305 (legacy). */
    public const METHOD_chacha20_poly1305 = 'chacha20-poly1305';

    /** Cipher: ChaCha20-IETF-Poly1305. */
    public const METHOD_chacha20_ietf_poly1305 = 'chacha20-ietf-poly1305';

    /** Cipher: xchacha20-poly1305 (legacy). */
    public const METHOD_xchacha20_poly1305 = 'xchacha20-poly1305';

    /** Cipher: xchacha20-ietf-poly1305. */
    public const METHOD_xchacha20_ietf_poly1305 = 'xchacha20-ietf-poly1305';

    /** Cipher: no encryption (testing only). */
    public const METHOD_none = 'none';

    /** Cipher: plain text (testing only). */
    public const METHOD_plain = 'plain';

    /** Network: TCP and UDP. */
    public const NETWORK_TCP_UDP = 'tcp,udp';

    /** Network: TCP only. */
    public const NETWORK_TCP = 'tcp';

    /** Network: UDP only. */
    public const NETWORK_UDP = 'udp';

    /** Remote server address. */
    public string $address;

    /** Remote server port. */
    public int $port;

    /** Remote password. */
    public string $password;

    /** Remote cipher. */
    public string $method;

    /** Optional email used for logging. */
    public string $email;

    /** Enable UDP over TCP. */
    public bool $uot;

    /**
     * @param string $address Remote server address.
     * @param int $port Remote server port.
     * @param string $password Remote password.
     * @param string $method Remote cipher.
     * @param string $email Optional email.
     * @param bool $uot Enable UDP over TCP.
     */
    public function __construct(
        string $address,
        int $port,
        string $password,
        string $method = self::METHOD_aes_128_gcm,
        string $email = '',
        bool $uot = true
    )
    {
        $this->address = $address;
        $this->port = $port;
        $this->password = $password;
        $this->method = $method;
        $this->email = $email;
        $this->uot = $uot;
    }

    /**
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        return [
            'servers' => [[
                'address' => $this->address,
                'port' => $this->port,
                'password' => $this->password,
                'email' => $this->email,
                'method' => $this->method,
                'uot' => $this->uot,
            ]],
        ];
    }
}
