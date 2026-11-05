<?php

namespace XUI\Inbounds\Protocols\Shadowsocks;

use XUI\Helper\Statics;

/**
 * `settings` object of a Shadowsocks inbound.
 *
 * Supports both legacy single-user (`method` + `password`) and multi-client
 * (`clients`) layouts used by 3x-ui.
 *
 * @link https://xtls.github.io/en/config/inbounds/shadowsocks.html
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

    /** Clients attached to the inbound. */
    public array $clients;

    /** Inbound password used by the single-user layout. */
    public string $password;

    /** Inbound cipher. */
    public string $method;

    /** Enabled transports. */
    public string $network;

    /** Enable the IV check hardening. */
    public bool $iv_check;

    /**
     * @param array $clients Initial client list.
     * @param string|null $password Inbound password, generated when omitted.
     * @param string $method Inbound cipher.
     * @param string $network Enabled transports.
     * @param bool $iv_check Enable the IV check hardening.
     */
    public function __construct(
        array $clients = [],
        ?string $password = null,
        string $method = self::METHOD_aes_128_gcm,
        string $network = self::NETWORK_TCP_UDP,
        bool $iv_check = false
    )
    {
        $this->clients = $clients;
        $this->password = $password ?? Statics::random(16);
        $this->method = $method;
        $this->network = $network;
        $this->iv_check = $iv_check;
    }

    /**
     * Add a Shadowsocks client.
     *
     * @param bool $enable Whether the client may connect.
     * @param string|null $method Client cipher, defaults to the given method.
     * @param string|null $password Client password, generated when omitted.
     * @param string|null $email Unique client email, generated when omitted.
     * @param int $total_traffic Traffic quota in bytes, 0 for unlimited.
     * @param int $expiry_time Expiry in Unix seconds, 0 for never.
     * @param int $limit_ip Maximum concurrent IPs, 0 for unlimited.
     * @param int|null $tgid Telegram user id for notifications.
     * @param string|null $subid Subscription id, generated when omitted.
     * @param int $reset Traffic reset interval in days.
     * @param array $extra Additional raw client fields (comment, limitHwid, ...).
     * @return string The client email.
     */
    public function add_client(
        bool $enable = true,
        ?string $method = null,
        ?string $password = null,
        ?string $email = null,
        int $total_traffic = 0,
        int $expiry_time = 0,
        int $limit_ip = 0,
        ?int $tgid = null,
        ?string $subid = null,
        int $reset = 0,
        array $extra = []
    ): string
    {
        $method ??= $this->method;
        $password ??= Statics::random(16);
        $email ??= Statics::random(8);
        $subid ??= Statics::random(20);
        $this->clients[] = array_merge([
            'method' => $method,
            'email' => $email,
            'password' => $password,
            'limitIp' => $limit_ip,
            'totalGB' => $total_traffic,
            'expiryTime' => Statics::to_millis($expiry_time),
            'enable' => $enable,
            'tgId' => $tgid,
            'subId' => $subid,
            'reset' => $reset,
        ], $extra);
        return $email;
    }

    /**
     * Update an existing client in place.
     *
     * @param string $client_email Current email identifying the client.
     * @param bool|null $enable New enabled flag.
     * @param string|null $method New cipher.
     * @param string|null $password New password.
     * @param string|null $email New email.
     * @param int|null $total_traffic New quota in bytes.
     * @param int|null $expiry_time New expiry in Unix seconds.
     * @param int|null $limit_ip New IP limit.
     * @param int|null $tgid New Telegram id.
     * @param string|null $subid New subscription id.
     * @param int|null $reset New reset interval in days.
     * @return bool True when the client was found and updated.
     */
    public function update_client(
        string $client_email,
        ?bool $enable = null,
        ?string $method = null,
        ?string $password = null,
        ?string $email = null,
        ?int $total_traffic = null,
        ?int $expiry_time = null,
        ?int $limit_ip = null,
        ?int $tgid = null,
        ?string $subid = null,
        ?int $reset = null
    ): bool
    {
        foreach ($this->clients as $key => $client) {
            if ($client['email'] !== $client_email) {
                continue;
            }
            if (!is_null($method)) $client['method'] = $method;
            if (!is_null($password)) $client['password'] = $password;
            if (!is_null($email)) $client['email'] = $email;
            if (!is_null($enable)) $client['enable'] = $enable;
            if (!is_null($total_traffic)) $client['totalGB'] = $total_traffic;
            if (!is_null($expiry_time)) $client['expiryTime'] = Statics::to_millis($expiry_time);
            if (!is_null($limit_ip)) $client['limitIp'] = $limit_ip;
            if (!is_null($tgid)) $client['tgId'] = $tgid;
            if (!is_null($subid)) $client['subId'] = $subid;
            if (!is_null($reset)) $client['reset'] = $reset;
            $this->clients[$key] = $client;
            return true;
        }
        return false;
    }

    /**
     * Fetch one client by email.
     *
     * @param string $client_email Client email.
     * @return array|false
     */
    public function get_client(string $client_email): array|false
    {
        foreach ($this->clients as $client) {
            if ($client['email'] === $client_email) {
                return $client;
            }
        }
        return false;
    }

    /**
     * Check whether a client email exists.
     *
     * @param string $client_email Client email.
     * @return bool
     */
    public function has_client(string $client_email): bool
    {
        return $this->get_client($client_email) !== false;
    }

    /**
     * Remove one client by email.
     *
     * @param string $client_email Client email.
     * @return bool True when a client was removed.
     */
    public function remove_client(string $client_email): bool
    {
        foreach ($this->clients as $key => $client) {
            if ($client['email'] === $client_email) {
                unset($this->clients[$key]);
                $this->clients = array_values($this->clients);
                return true;
            }
        }
        return false;
    }

    /**
     * Remove every client.
     *
     * @return void
     */
    public function clear_clients(): void
    {
        $this->clients = [];
    }

    /**
     * Pick the first client cipher, falling back to the inbound method.
     *
     * @return string
     */
    public function client_method(): string
    {
        return (string)($this->clients[0]['method'] ?? $this->method);
    }

    /**
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        return [
            'clients' => $this->clients,
            'password' => $this->password,
            'method' => $this->method,
            'network' => $this->network,
            'ivCheck' => $this->iv_check,
        ];
    }
}
