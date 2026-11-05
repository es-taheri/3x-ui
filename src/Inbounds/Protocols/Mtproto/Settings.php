<?php

namespace XUI\Inbounds\Protocols\Mtproto;

use XUI\Helper\Statics;

/**
 * `settings` object of an MTProto inbound (3x-ui extension).
 *
 * Each client carries a FakeTLS `secret` and, optionally, a Telegram sponsor
 * channel tag (`adTag`).
 */
class Settings
{
    /** Clients attached to the inbound. */
    public array $clients;

    /** Fronting domain used to derive FakeTLS secrets; empty uses `www.cloudflare.com`. */
    public string $domain;

    /**
     * @param array $clients Initial client list.
     * @param string $domain Fronting domain.
     */
    public function __construct(array $clients = [], string $domain = '')
    {
        $this->clients = $clients;
        $this->domain = $domain;
    }

    /**
     * Add an MTProto client.
     *
     * @param bool $enable Whether the client may connect.
     * @param string|null $email Unique client email, generated when omitted.
     * @param string|null $secret FakeTLS secret, generated when omitted.
     * @param string $ad_tag Telegram sponsor channel tag.
     * @param int $total_traffic Traffic quota in bytes.
     * @param int $expiry_time Expiry in Unix seconds.
     * @param int $limit_ip Maximum concurrent IPs.
     * @param int|null $tgid Telegram user id.
     * @param string|null $subid Subscription id.
     * @param int $reset Traffic reset interval in days.
     * @param array $extra Additional raw client fields.
     * @return string The client secret.
     */
    public function add_client(
        bool $enable = true,
        ?string $email = null,
        ?string $secret = null,
        string $ad_tag = '',
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
        $secret ??= self::generate_secret($this->domain);
        $this->clients[] = array_merge([
            'email' => $email,
            'secret' => $secret,
            'adTag' => $ad_tag,
            'totalGB' => $total_traffic,
            'expiryTime' => Statics::to_millis($expiry_time),
            'limitIp' => $limit_ip,
            'enable' => $enable,
            'tgId' => $tgid,
            'subId' => $subid ?? Statics::random(20),
            'reset' => $reset,
        ], $extra);
        return $secret;
    }

    /**
     * Generate a FakeTLS secret for the given fronting domain.
     *
     * @param string $domain Fronting domain, defaults to `www.cloudflare.com`.
     * @return string Hex encoded `ee` + random 16 bytes + hex encoded domain.
     */
    public static function generate_secret(string $domain = ''): string
    {
        $domain = $domain === '' ? 'www.cloudflare.com' : $domain;
        return 'ee' . bin2hex(random_bytes(16)) . bin2hex($domain);
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
            'domain' => $this->domain,
        ];
    }
}
