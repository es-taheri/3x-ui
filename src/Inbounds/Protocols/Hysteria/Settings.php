<?php

namespace XUI\Inbounds\Protocols\Hysteria;

use XUI\Helper\Statics;

/**
 * `settings` object of a Hysteria 2 inbound (3x-ui extension).
 *
 * Every client authenticates with an `auth` string; TLS is configured through
 * the inbound stream settings.
 */
class Settings
{
    /** Clients attached to the inbound. */
    public array $clients;

    /** Hysteria protocol version. */
    public int $version;

    /**
     * @param array $clients Initial client list.
     * @param int $version Hysteria protocol version.
     */
    public function __construct(array $clients = [], int $version = 2)
    {
        $this->clients = $clients;
        $this->version = $version;
    }

    /**
     * Add a Hysteria client.
     *
     * @param bool $enable Whether the client may connect.
     * @param string|null $email Unique client email, generated when omitted.
     * @param string|null $auth Authentication secret, generated when omitted.
     * @param int $total_traffic Traffic quota in bytes.
     * @param int $expiry_time Expiry in Unix seconds.
     * @param int $limit_ip Maximum concurrent IPs.
     * @param int|null $tgid Telegram user id.
     * @param string|null $subid Subscription id.
     * @param int $reset Traffic reset interval in days.
     * @param array $extra Additional raw client fields.
     * @return string The authentication secret.
     */
    public function add_client(
        bool $enable = true,
        ?string $email = null,
        ?string $auth = null,
        int $total_traffic = 0,
        int $expiry_time = 0,
        int $limit_ip = 0,
        ?int $tgid = null,
        ?string $subid = null,
        int $reset = 0,
        array $extra = []
    ): string
    {
        $email ??= Statics::random(8);
        $auth ??= Statics::random(16);
        $this->clients[] = array_merge([
            'email' => $email,
            'auth' => $auth,
            'totalGB' => $total_traffic,
            'expiryTime' => Statics::to_millis($expiry_time),
            'limitIp' => $limit_ip,
            'enable' => $enable,
            'tgId' => $tgid,
            'subId' => $subid ?? Statics::random(20),
            'reset' => $reset,
        ], $extra);
        return $auth;
    }

    /**
     * Fetch one client by email.
     *
     * @param string $email Client email.
     * @return array|false
     */
    public function get_client(string $email): array|false
    {
        foreach ($this->clients as $client) {
            if (($client['email'] ?? null) === $email) {
                return $client;
            }
        }
        return false;
    }

    /**
     * Check whether a client email exists.
     *
     * @param string $email Client email.
     * @return bool
     */
    public function has_client(string $email): bool
    {
        return $this->get_client($email) !== false;
    }

    /**
     * Remove one client by email.
     *
     * @param string $email Client email.
     * @return bool True when a client was removed.
     */
    public function remove_client(string $email): bool
    {
        foreach ($this->clients as $key => $client) {
            if (($client['email'] ?? null) === $email) {
                unset($this->clients[$key]);
                $this->clients = array_values($this->clients);
                return true;
            }
        }
        return false;
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
            'version' => $this->version,
        ];
    }
}
