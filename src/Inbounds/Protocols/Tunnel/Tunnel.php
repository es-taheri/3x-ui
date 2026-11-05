<?php

namespace XUI\Inbounds\Protocols\Tunnel;

use XUI\Inbounds\InboundConfig;
use XUI\Inbounds\Protocols\InboundTrait;

/**
 * Tunnel (dokodemo-door) inbound configuration.
 *
 * @link https://xtls.github.io/en/config/inbounds/dokodemo.html
 */
class Tunnel implements InboundConfig
{
    use InboundTrait;

    /** Xray protocol name. */
    public string $protocol = 'dokodemo-door';

    /** Listen address, empty for every interface. */
    public string $listen = '';

    /** Listen port. */
    public int $port = 0;

    /** Protocol settings. */
    public Settings $settings;

    /**
     * Rebuild a configuration from stored JSON strings.
     *
     * @param string|null $listen Listen address.
     * @param int|null $port Listen port.
     * @param string|null $settings JSON encoded `settings` object.
     */
    public function __construct(?string $listen = null, ?int $port = null, ?string $settings = null)
    {
        if (!is_null($listen)) {
            $this->listen = $listen;
        }
        if (!is_null($port)) {
            $this->port = $port;
        }
        if (!is_null($settings)) {
            $data = (array)(json_decode($settings, true) ?: []);
            $this->settings = new Settings(
                (string)($data['address'] ?? ''),
                (int)($data['port'] ?? 0),
                (string)($data['network'] ?? Settings::NETWORK_TCP_UDP),
                (bool)($data['followRedirect'] ?? false)
            );
        }
    }

    /**
     * Fill every member.
     *
     * @param string|null $listen Listen address.
     * @param int|null $port Listen port, random when omitted.
     * @param Settings|null $settings Protocol settings; required.
     * @return void
     */
    public function generate(?string $listen = null, ?int $port = null, ?Settings $settings = null): void
    {
        $this->listen = $listen ?? '';
        $this->port = $port ?? random_int(1024, 65535);
        if (!is_null($settings)) {
            $this->settings = $settings;
        }
    }

    /**
     * Replace only the members that are provided.
     *
     * @param string|null $listen New listen address.
     * @param int|null $port New listen port.
     * @param Settings|null $settings New protocol settings.
     * @return void
     */
    public function modify(?string $listen = null, ?int $port = null, ?Settings $settings = null): void
    {
        if (!is_null($listen)) $this->listen = $listen;
        if (!is_null($port)) $this->port = $port;
        if (!is_null($settings)) $this->settings = $settings;
    }
}
