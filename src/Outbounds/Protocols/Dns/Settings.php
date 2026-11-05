<?php

namespace XUI\Outbounds\Protocols\Dns;

/**
 * `settings` object of a DNS outbound.
 *
 * @link https://xtls.github.io/en/config/outbounds/dns.html
 */
class Settings
{
    /** Network: `tcp` or `udp`. */
    public string $network;

    /** DNS server address. */
    public string $address;

    /** DNS server port. */
    public int $port;

    /** Behaviour for non-IP queries: `drop` or `skip`. */
    public string $non_ip_query;

    /**
     * @param string $network Network transport.
     * @param string $address DNS server address.
     * @param int $port DNS server port.
     * @param string $non_ip_query Non-IP query behaviour.
     */
    public function __construct(string $network, string $address, int $port = 53, string $non_ip_query = 'drop')
    {
        $this->network = $network;
        $this->address = $address;
        $this->port = $port;
        $this->non_ip_query = $non_ip_query;
    }

    /**
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        return [
            'network' => $this->network,
            'address' => $this->address,
            'port' => $this->port,
            'nonIPQuery' => $this->non_ip_query,
        ];
    }
}
