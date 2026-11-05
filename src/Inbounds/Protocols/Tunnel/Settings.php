<?php

namespace XUI\Inbounds\Protocols\Tunnel;

/**
 * `settings` object of a dokodemo-door (tunnel) inbound.
 *
 * @link https://xtls.github.io/en/config/inbounds/dokodemo.html
 */
class Settings
{
    /** Network: TCP and UDP. */
    public const NETWORK_TCP_UDP = 'tcp,udp';

    /** Network: TCP only. */
    public const NETWORK_TCP = 'tcp';

    /** Network: UDP only. */
    public const NETWORK_UDP = 'udp';

    /** Forward destination address. */
    public string $address;

    /** Forward destination port. */
    public int $port;

    /** Enabled transports. */
    public string $network;

    /** Follow REDIRECT rules of the destination. */
    public bool $follow_redirect;

    /**
     * @param string $address Forward destination address.
     * @param int $port Forward destination port.
     * @param string $network Enabled transports.
     * @param bool $follow_redirect Follow REDIRECT rules.
     */
    public function __construct(string $address, int $port, string $network = self::NETWORK_TCP_UDP, bool $follow_redirect = false)
    {
        $this->address = $address;
        $this->port = $port;
        $this->network = $network;
        $this->follow_redirect = $follow_redirect;
    }

    /**
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        return [
            'address' => $this->address,
            'port' => $this->port,
            'network' => $this->network,
            'followRedirect' => $this->follow_redirect,
        ];
    }
}
