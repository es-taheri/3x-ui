<?php

namespace XUI\Outbounds\Protocols\Blackhole;

use JSON\json;

class Blackhole
{
    public string $protocol = 'blackhole';
    public Settings $settings;

    public function __construct(string $settings = null)
    {
        if (!is_null($settings)):
            $settings = json::_in($settings, true);
            $this->settings = new Settings($settings['response']['type']);
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