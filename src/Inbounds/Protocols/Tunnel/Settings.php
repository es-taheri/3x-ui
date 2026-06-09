<?php

namespace XUI\Inbounds\Protocols\Tunnel;

class Settings

{
    public string $address;
    public int $port;
    public string $network;
    public bool $follow_redirect;
    public const NETWORK_TCP_UDP = 'tcp,udp';
    public const NETWORK_TCP = 'tcp';
    public const NETWORK_UDP = 'udp';

    public function __construct(string $address, int $port, string $network, bool $follow_redirect)
    {
        $this->address = $address;
        $this->port = $port;
        $this->network = $network;
        $this->follow_redirect = $follow_redirect;
    }

    /**
     * Returns fully structured settings for xray json config
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