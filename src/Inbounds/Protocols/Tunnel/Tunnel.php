<?php

namespace XUI\Inbounds\Protocols\Tunnel;

use JSON\json;

class Tunnel

{
    public string $protocol = 'dokodemo-door';
    public string $listen;
    public int $port;
    public Settings $settings;

    public function __construct(string $listen = null, int $port = null, string $settings = null)
    {
        if (!is_null($listen)) $this->listen = $listen;
        if (!is_null($port)) $this->port = $port;
        if (!is_null($settings)):
            $settings = json::_in($settings);
            $this->settings = new Settings($settings->address, $settings->port, $settings->network, $settings->followRedirect);
        endif;
    }

    public function generate(string $listen = null, int $port = null, Settings $settings): void
    {
        $this->listen = (is_null($listen)) ? '' : $listen;
        $this->port = (is_null($port)) ? rand(1024, 65535) : $port;
        $this->settings = $settings;
    }

    public function modify(string $listen = null, int $port = null, Settings $settings = null): void
    {
        if (!is_null($listen)) $this->listen = $listen;
        if (!is_null($port)) $this->port = $port;
        if (!is_null($settings)) $this->settings = $settings;
    }
}