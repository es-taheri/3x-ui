<?php

namespace XUI\Inbounds\Protocols\Vmess;

use XUI\Inbounds\InboundConfig;
use XUI\Inbounds\Protocols\InboundTrait;
use XUI\Inbounds\Sniffing;
use XUI\Inbounds\StreamSettings;

/**
 * VMess inbound configuration.
 *
 * @link https://xtls.github.io/en/config/inbounds/vmess.html
 */
class Vmess implements InboundConfig
{
    use InboundTrait;

    /** Transport name: raw TCP. */
    public const NETWORK_TCP = StreamSettings::NETWORK_TCP;

    /** Transport name: mKCP. */
    public const NETWORK_KCP = StreamSettings::NETWORK_KCP;

    /** Transport name: WebSocket. */
    public const NETWORK_WS = StreamSettings::NETWORK_WS;

    /** Transport name: HTTP/2. */
    public const NETWORK_HTTP = StreamSettings::NETWORK_HTTP;

    /** Transport name: QUIC. */
    public const NETWORK_QUIC = StreamSettings::NETWORK_QUIC;

    /** Transport name: gRPC. */
    public const NETWORK_GRPC = StreamSettings::NETWORK_GRPC;

    /** Transport name: HTTPUpgrade. */
    public const NETWORK_HTTPUPGRADE = StreamSettings::NETWORK_HTTPUPGRADE;

    /** Transport name: XHTTP. */
    public const NETWORK_XHTTP = StreamSettings::NETWORK_XHTTP;

    /** Security name: none. */
    public const SECURITY_NONE = StreamSettings::SECURITY_NONE;

    /** Security name: TLS. */
    public const SECURITY_TLS = StreamSettings::SECURITY_TLS;

    /** Xray protocol name. */
    public string $protocol = 'vmess';

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
                (array)($data['clients'] ?? $data['users'] ?? []),
                (int)($data['default']['level'] ?? 0)
            );
        }
        if (!is_null($stream_settings)) {
            $this->stream_settings = StreamSettings::from_array((array)(json_decode($stream_settings, true) ?: []));
        }
        $this->sniffing = is_null($sniffing) ? new Sniffing() : Sniffing::from_array($sniffing);
    }

    /**
     * Fill every member, generating sensible defaults for omitted ones.
     *
     * @param string|null $listen Listen address.
     * @param int|null $port Listen port, random when omitted.
     * @param Settings|null $settings Protocol settings, one random client when omitted.
     * @param StreamSettings|null $stream_settings Stream settings, TCP/none when omitted.
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
            $stream_settings = new StreamSettings(self::NETWORK_TCP, self::SECURITY_NONE);
            $stream_settings->tcp_settings();
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
