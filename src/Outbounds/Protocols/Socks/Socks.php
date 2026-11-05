<?php

namespace XUI\Outbounds\Protocols\Socks;

/**
 * SOCKS outbound configuration.
 *
 * @link https://xtls.github.io/en/config/outbounds/socks.html
 */
class Socks
{
    /** Xray protocol name. */
    public string $protocol = 'socks';

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
            $server = (array)($data['servers'][0] ?? []);
            $this->settings = new Settings(
                (string)($server['address'] ?? ''),
                (int)($server['port'] ?? 0),
                (array)($server['users'] ?? [])
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
