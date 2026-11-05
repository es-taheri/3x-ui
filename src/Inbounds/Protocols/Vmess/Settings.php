<?php

namespace XUI\Inbounds\Protocols\Vmess;

use XUI\Helper\Statics;

/**
 * `settings` object of a VMess inbound.
 *
 * @link https://xtls.github.io/en/config/inbounds/vmess.html
 */
class Settings
{
    /** Clients attached to the inbound. */
    public array $clients;

    /** Default user level applied to new clients. */
    public int $default_level;

    /**
     * @param array $clients Initial client list.
     * @param int $default_level Default user level.
     */
    public function __construct(array $clients = [], int $default_level = 0)
    {
        $this->clients = $clients;
        $this->default_level = $default_level;
    }

    /**
     * Add a VMess client.
     *
     * @param bool $enable Whether the client may connect.
     * @param string|null $uuid Client UUID, generated when omitted.
     * @param string|null $email Unique client email, generated when omitted.
     * @param int $total_traffic Traffic quota in bytes, 0 for unlimited.
     * @param int $expiry_time Expiry in Unix seconds, 0 for never.
     * @param int $limit_ip Maximum concurrent IPs, 0 for unlimited.
     * @param int|null $tgid Telegram user id for notifications.
     * @param string|null $subid Subscription id, generated when omitted.
     * @param int $reset Traffic reset interval in days.
     * @param array $extra Additional raw client fields (security, comment, limitHwid, ...).
     * @return string The client UUID.
     */
    public function add_client(
        bool $enable = true,
        ?string $uuid = null,
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
        $uuid ??= Statics::uuid();
        $email ??= Statics::random(8);
        $subid ??= Statics::random(20);
        $this->clients[] = array_merge([
            'id' => $uuid,
            'email' => $email,
            'limitIp' => $limit_ip,
            'totalGB' => $total_traffic,
            'expiryTime' => Statics::to_millis($expiry_time),
            'enable' => $enable,
            'tgId' => $tgid,
            'subId' => $subid,
            'reset' => $reset,
        ], $extra);
        return $uuid;
    }

    /**
     * Update an existing client in place.
     *
     * @param string $client_email Current email identifying the client.
     * @param bool|null $enable New enabled flag.
     * @param string|null $uuid New UUID.
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
        ?string $uuid = null,
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
            if (!is_null($uuid)) $client['id'] = $uuid;
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
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        return [
            'clients' => $this->clients,
            'default' => ['level' => $this->default_level],
        ];
    }
}
