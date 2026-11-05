<?php

namespace XUI\Inbounds\Protocols\AmneziaWG;

use XUI\Helper\Statics;

/**
 * `settings` object of an AmneziaWG inbound.
 *
 * Holds the shared interface block and the peer list.
 */
class Settings
{
    /** Interface-level configuration. */
    public ServerSettings $server;

    /** Peers attached to the tunnel. */
    public array $clients;

    /**
     * @param ServerSettings|null $server Interface configuration.
     * @param array $clients Initial peer list.
     */
    public function __construct(?ServerSettings $server = null, array $clients = [])
    {
        $this->server = $server ?? new ServerSettings();
        $this->clients = $clients;
    }

    /**
     * Add a peer.
     *
     * @param bool $enable Whether the peer may connect.
     * @param string|null $email Unique peer email, generated when omitted.
     * @param string|null $public_key Peer public key, generated when omitted.
     * @param string|null $private_key Peer private key, generated when omitted.
     * @param array $allowed_ips Addresses routed to the peer.
     * @param string $pre_shared_key Optional pre-shared key.
     * @param int|string $keep_alive Keep-alive interval.
     * @param string $forwarded_ports Per-client port-forwarding spec, e.g. `80,443,8000-8100`.
     * @param int $total_traffic Traffic quota in bytes.
     * @param int $expiry_time Expiry in Unix seconds.
     * @param int $limit_ip Maximum concurrent IPs.
     * @param array $extra Additional raw peer fields (comment, limitHwid, ...).
     * @return string The peer public key.
     */
    public function add_client(
        bool $enable = true,
        ?string $email = null,
        ?string $public_key = null,
        ?string $private_key = null,
        array $allowed_ips = [],
        string $pre_shared_key = '',
        int|string $keep_alive = 0,
        string $forwarded_ports = '',
        int $total_traffic = 0,
        int $expiry_time = 0,
        int $limit_ip = 0,
        array $extra = []
    ): string
    {
        $email ??= Statics::random(8);
        $public_key ??= base64_encode(random_bytes(32));
        $private_key ??= base64_encode(random_bytes(32));
        $this->clients[] = array_merge([
            'email' => $email,
            'publicKey' => $public_key,
            'privateKey' => $private_key,
            'allowedIPs' => $allowed_ips,
            'preSharedKey' => $pre_shared_key,
            'keepAlive' => $keep_alive,
            'forwardedPorts' => $forwarded_ports,
            'totalGB' => $total_traffic,
            'expiryTime' => Statics::to_millis($expiry_time),
            'limitIp' => $limit_ip,
            'enable' => $enable,
            'subId' => Statics::random(20),
        ], $extra);
        return $public_key;
    }

    /**
     * Fetch one peer by email.
     *
     * @param string $email Peer email.
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
     * Check whether a peer email exists.
     *
     * @param string $email Peer email.
     * @return bool
     */
    public function has_client(string $email): bool
    {
        return $this->get_client($email) !== false;
    }

    /**
     * Remove one peer by email.
     *
     * @param string $email Peer email.
     * @return bool True when a peer was removed.
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
            'server' => $this->server->settings(),
            'clients' => $this->clients,
        ];
    }
}
