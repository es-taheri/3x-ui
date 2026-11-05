<?php

namespace XUI\Inbounds\Protocols\Hysteria;

use XUI\Inbounds\InboundConfig;
use XUI\Inbounds\Protocols\InboundTrait;
use XUI\Inbounds\Sniffing;
use XUI\Inbounds\StreamSettings;

/**
 * Hysteria 2 inbound configuration (3x-ui extension).
 *
 * Uses a TLS stream and per-client `auth` secrets.
 */
class Hysteria implements InboundConfig
{
    use InboundTrait;

    /** Xray protocol name. */
    public string $protocol = 'hysteria';

    /** Listen address, empty for every interface. */
    public string $listen = '';

    /** Listen port. */
    public int $port = 0;

    /** Protocol settings. */
    public Settings $settings;

    /** Sniffing settings. */
    public Sniffing $sniffing;

    /** Stream settings. */
    public StreamSettings $stream_settings;

    /**
     * Rebuild a configuration from stored JSON strings.
     *
     * @param string|null $listen Listen address.
     * @param int|null $port Listen port.
     * @param string|null $settings JSON encoded `settings` object.
     * @param string|null $stream_settings JSON encoded `streamSettings` object.
     * @param string|null $sniffing JSON encoded `sniffing` object.
     */
    public function __construct(
        ?string $listen = null,
        ?int $port = null,
        ?string $settings = null,
        ?string $stream_settings = null,
        ?string $sniffing = null
    )
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
                (array)($data['clients'] ?? []),
                (int)($data['version'] ?? 2)
            );
        }
        if (!is_null($stream_settings)) {
            $this->stream_settings = StreamSettings::from_array((array)(json_decode($stream_settings, true) ?: []));
        }
        $this->sniffing = is_null($sniffing) ? new Sniffing() : Sniffing::from_array($sniffing);
    }

    /**
     * Fill every member, generating one client when settings are omitted.
     *
     * @param string|null $listen Listen address.
     * @param int|null $port Listen port, random when omitted.
     * @param Settings|null $settings Protocol settings.
     * @param StreamSettings|null $stream_settings Stream settings.
     * @param Sniffing|null $sniffing Sniffing settings.
     * @return void
     */
    public function generate(
        ?string $listen = null,
        ?int $port = null,
        ?Settings $settings = null,
        ?StreamSettings $stream_settings = null,
        ?Sniffing $sniffing = null
    ): void
    {
        $this->listen = $listen ?? '';
        $this->port = $port ?? random_int(1024, 65535);
        if (is_null($settings)) {
            $settings = new Settings();
            $settings->add_client();
        }
        if (is_null($stream_settings)) {
            $stream_settings = new StreamSettings(StreamSettings::NETWORK_TCP, StreamSettings::SECURITY_TLS);
            $stream_settings->tls_settings();
        }
        $this->settings = $settings;
        $this->stream_settings = $stream_settings;
        $this->sniffing = $sniffing ?? new Sniffing();
    }

    /**
     * Replace only the members that are provided.
     *
     * @param string|null $listen New listen address.
     * @param int|null $port New listen port.
     * @param Settings|null $settings New protocol settings.
     * @param StreamSettings|null $stream_settings New stream settings.
     * @param Sniffing|null $sniffing New sniffing settings.
     * @return void
     */
    public function modify(
        ?string $listen = null,
        ?int $port = null,
        ?Settings $settings = null,
        ?StreamSettings $stream_settings = null,
        ?Sniffing $sniffing = null
    ): void
    {
        if (!is_null($listen)) $this->listen = $listen;
        if (!is_null($port)) $this->port = $port;
        if (!is_null($settings)) $this->settings = $settings;
        if (!is_null($stream_settings)) $this->stream_settings = $stream_settings;
        if (!is_null($sniffing)) $this->sniffing = $sniffing;
    }
}
