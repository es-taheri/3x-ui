<?php

namespace XUI\Inbounds\Protocols\WireGuard;

use XUI\Helper\Statics;

/**
 * `settings` object of a WireGuard inbound (3x-ui extension).
 *
 * It stores the server key pair, the tunnel subnet and the peers. In 3x-ui a
 * "client" of a WireGuard inbound is one peer.
 */
class Settings
{
    /** Server private key. */
    public string $secret_key;

    /** Server tunnel addresses, e.g. `['10.0.0.1/24']`. */
    public array $address;

    /** Peers attached to the tunnel. */
    public array $peers;

    /** Tunnel MTU. */
    public int $mtu;

    /** DNS servers handed to peers. */
    public string $dns;

    /**
     * @param string|null $secret_key Server private key, generated when omitted.
     * @param array $address Server tunnel addresses.
     * @param array $peers Initial peers.
     * @param int $mtu Tunnel MTU.
     * @param string $dns DNS servers handed to peers.
     */
    public function __construct(
        ?string $secret_key = null,
        array $address = [],
        array $peers = [],
        int $mtu = 1420,
        string $dns = ''
    )
    {
        $this->secret_key = $secret_key ?? base64_encode(random_bytes(32));
        $this->address = $address;
        $this->peers = $peers;
        $this->mtu = $mtu;
        $this->dns = $dns;
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
     * @param int|string $keep_alive Keep-alive interval, 0 disables it.
     * @param int $total_traffic Traffic quota in bytes.
     * @param int $expiry_time Expiry in Unix seconds.
     * @param int $limit_ip Maximum concurrent IPs.
     * @param array $extra Additional raw peer fields (comment, limitHwid, ...).
     * @return string The peer public key.
     */
    public function add_peer(
        bool $enable = true,
        ?string $email = null,
        ?string $public_key = null,
        ?string $private_key = null,
        array $allowed_ips = [],
        string $pre_shared_key = '',
        int|string $keep_alive = 0,
        int $total_traffic = 0,
        int $expiry_time = 0,
        int $limit_ip = 0,
        array $extra = []
    ): string
    {
        $email ??= Statics::random(8);
        $public_key ??= base64_encode(random_bytes(32));
        $private_key ??= base64_encode(random_bytes(32));
        $this->peers[] = array_merge([
            'email' => $email,
            'publicKey' => $public_key,
            'privateKey' => $private_key,
            'allowedIPs' => $allowed_ips,
            'preSharedKey' => $pre_shared_key,
            'keepAlive' => $keep_alive,
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
    public function get_peer(string $email): array|false
    {
        foreach ($this->peers as $peer) {
            if (($peer['email'] ?? null) === $email) {
                return $peer;
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
    public function has_peer(string $email): bool
    {
        return $this->get_peer($email) !== false;
    }

    /**
     * Remove one peer by email.
     *
     * @param string $email Peer email.
     * @return bool True when a peer was removed.
     */
    public function remove_peer(string $email): bool
    {
        foreach ($this->peers as $key => $peer) {
            if (($peer['email'] ?? null) === $email) {
                unset($this->peers[$key]);
                $this->peers = array_values($this->peers);
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
            'secretKey' => $this->secret_key,
            'address' => $this->address,
            'peers' => $this->peers,
            'mtu' => $this->mtu,
            'dns' => $this->dns,
        ];
    }
}
