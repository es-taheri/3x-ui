<?php

namespace XUI\Outbounds\Protocols\Freedom;

use JSON\json;

class Freedom
{
    public string $protocol = 'freedom';
    public Settings $settings;

    public function __construct(string $settings = null)
    {
        if (!is_null($settings)):
            $settings = json::_in($settings, true);
            $this->settings = new Settings($settings['domainStrategy'], $settings['redirect'], $settings['fragment'], $settings['proxyProtocol']);
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