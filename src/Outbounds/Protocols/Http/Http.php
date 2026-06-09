<?php

namespace XUI\Outbounds\Protocols\Http;

use JSON\json;

class Http
{
    public string $protocol = 'http';
    public Settings $settings;
    public function __construct(string $settings = null)
    {
        if (!is_null($settings)):
            $settings = json::_in($settings, true);
            $this->settings = new Settings($settings['servers'][0]['address'], $settings['servers'][0]['port'], $settings['servers'][0]['users']);
        endif;
    }

    public function generate(
        Settings $settings
    ): void
    {
        $this->settings = $settings;
    }

    public function modify(
        Settings $settings = null
    ): void
    {
        if (!is_null($settings)) $this->settings = $settings;
    }
}