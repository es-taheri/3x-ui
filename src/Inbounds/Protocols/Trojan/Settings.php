<?php

namespace XUI\Inbounds\Protocols\Trojan;

use XUI\Helper\Statics;

/**
 * `settings` object of a Trojan inbound.
 *
 * @link https://xtls.github.io/en/config/inbounds/trojan.html
 */
class Settings
{
    /** Clients attached to the inbound. */
    public array $clients;

    /** Trojan fallback rules. */
    public array $fallbacks;

    /**
     * @param array $clients Initial client list.
     * @param array $fallbacks Initial fallback rules.
     */
    public function __construct(array $clients = [], array $fallbacks = [])
    {
        $this->clients = $clients;
        $this->fallbacks = $fallbacks;
    }

    /**
     * Add a Trojan client.
     *
     * @param bool $enable Whether the client may connect.
     * @param string|null $email Unique client email, generated when omitted.
     * @param string|null $password Client password, generated when omitted.
     * @param int $total_traffic Traffic quota in bytes, 0 for unlimited.
     * @param int $expiry_time Expiry in Unix seconds, 0 for never.
     * @param int $limit_ip Maximum concurrent IPs, 0 for unlimited.
     * @param int|null $tgid Telegram user id for notifications.
     * @param string|null $subid Subscription id, generated when omitted.
     * @param int $reset Traffic reset interval in days.
     * @param array $extra Additional raw client fields (comment, limitHwid, flow, ...).
     * @return string The client password.
     */
    public function add_client(
        bool $enable = true,
        ?string $email = null,
        ?string $password = null,
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
        $password ??= Statics::random(16);
        $subid ??= Statics::random(20);
        $this->clients[] = array_merge([
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
        return $password;
    }

    /**
     * Update an existing client in place.
     *
     * @param string $client_email Current email identifying the client.
     * @param bool|null $enable New enabled flag.
     * @param string|null $email New email.
     * @param string|null $password New password.
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
        ?string $email = null,
        ?string $password = null,
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
            if (!is_null($email)) $client['email'] = $email;
            if (!is_null($enable)) $client['enable'] = $enable;
            if (!is_null($total_traffic)) $client['totalGB'] = $total_traffic;
            if (!is_null($expiry_time)) $client['expiryTime'] = Statics::to_millis($expiry_time);
            if (!is_null($limit_ip)) $client['limitIp'] = $limit_ip;
            if (!is_null($password)) $client['password'] = $password;
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
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        return [
            'clients' => $this->clients,
            'fallbacks' => $this->fallbacks,
        ];
    }
}
