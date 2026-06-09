<?php

namespace XUI\Outbounds\Protocols\Dns;

use JSON\json;
use stdClass;

class Settings

{
    public string $network;
    public int $address;
    public array $port;
    public int $non_ip_query;

    public function __construct(string $network, int $address, array $port, bool $non_ip_query)
    {
        $this->network = $network;
        $this->address = $address;
        $this->port = $port;
        $this->non_ip_query = $non_ip_query;
    }

    /**
     * Returns fully structured settings for xray json config
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