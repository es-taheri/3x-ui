<?php

namespace XUI\Outbounds\Protocols\Dns;

/**
 * DNS outbound configuration.
 *
 * @link https://xtls.github.io/en/config/outbounds/dns.html
 */
class Dns
{
    /** Xray protocol name. */
    public string $protocol = 'dns';

    /** Protocol settings. */
    public Settings $settings;

    /**
     * Rebuild a configuration from stored JSON strings.
     *
     * @param string|null $settings JSON encoded `settings` object.
     */
    public function __construct(?string $settings = null)
    {
        if (!is_null($settings)) {
            $data = (array)(json_decode($settings, true) ?: []);
            $this->settings = new Settings(
                (string)($data['network'] ?? 'udp'),
                (string)($data['address'] ?? ''),
                (int)($data['port'] ?? 53),
                (string)($data['nonIPQuery'] ?? 'drop')
            );
        }
    }

    /**
     * Fill every member.
     *
     * @param Settings $settings Protocol settings.
     * @return void
     */
    public function generate(Settings $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * Replace only the members that are provided.
     *
     * @param Settings|null $settings New protocol settings.
     * @return void
     */
    public function modify(?Settings $settings = null): void
    {
        if (!is_null($settings)) $this->settings = $settings;
    }
}
