<?php

namespace XUI\Inbounds\Protocols\Http;

use JSON\json;

class Http


{
    public string $protocol = 'http';
    public string $listen;
    public int $port;
    public Settings $settings;

    public function __construct(string $listen = null, int $port = null, string $settings = null)
    {
        if (!is_null($listen)) $this->listen = $listen;
        if (!is_null($port)) $this->port = $port;
        if (!is_null($settings)):
            $settings = json::_in($settings,true);
            $this->settings = new Settings($settings['accounts'],$settings['timeout']);
        endif;
    }

    public function generate(
        string $listen = null, int $port = null, Settings $settings = null
    ): void
    {
        $this->listen = (is_null($listen)) ? '' : $listen;
        $this->port = (is_null($port)) ? rand(1024, 65535) : $port;
        if (is_null($settings)) :
            $settings = new Settings();
            $settings->add_account();
        endif;
        $this->settings = $settings;
    }

    public function modify(
        string $listen = null, int $port = null, Settings $settings = null
    ): void
    {
        if (!is_null($listen)) $this->listen = $listen;
        if (!is_null($port)) $this->port = $port;
        if (!is_null($settings)) $this->settings = $settings;
    }
}