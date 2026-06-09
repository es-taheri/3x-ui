<?php

namespace XUI\Outbounds\Protocols\Dns;

use JSON\json;

class Dns
{
    public string $protocol = 'dns';
    public Settings $settings;

    public function __construct(string $settings = null)
    {
        if (!is_null($settings)):
            $settings = json::_in($settings, true);
            $this->settings = new Settings($settings['network'], $settings['address'], $settings['port'], $settings['nonIPQuery']);
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