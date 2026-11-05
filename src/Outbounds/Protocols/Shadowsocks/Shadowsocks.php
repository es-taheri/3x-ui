<?php

namespace XUI\Outbounds\Protocols\Shadowsocks;

use XUI\Outbounds\StreamSettings;

/**
 * Shadowsocks outbound configuration.
 *
 * @link https://xtls.github.io/en/config/outbounds/shadowsocks.html
 */
class Shadowsocks
{
    /** Xray protocol name. */
    public string $protocol = 'shadowsocks';

    /** Protocol settings. */
    public Settings $settings;

    /** Stream settings. */
    public StreamSettings $stream_settings;

    /**
     * Rebuild a configuration from stored JSON strings.
     *
     * @param string|null $settings JSON encoded `settings` object.
     * @param string|null $stream_settings JSON encoded `streamSettings` object.
     */
    public function __construct(?string $settings = null, ?string $stream_settings = null)
    {
        if (!is_null($settings)) {
            $data = (array)(json_decode($settings, true) ?: []);
            $server = (array)($data['servers'][0] ?? []);
            $this->settings = new Settings(
                (string)($server['address'] ?? ''),
                (int)($server['port'] ?? 0),
                (string)($server['password'] ?? ''),
                (string)($server['method'] ?? Settings::METHOD_aes_128_gcm),
                (string)($server['email'] ?? ''),
                (bool)($server['uot'] ?? true)
            );
        }
        if (!is_null($stream_settings)) {
            $this->stream_settings = StreamSettings::from_array((array)(json_decode($stream_settings, true) ?: []));
        }
    }

    /**
     * Fill every member.
     *
     * @param Settings $settings Protocol settings.
     * @param StreamSettings $stream_settings Stream settings.
     * @return void
     */
    public function generate(Settings $settings, StreamSettings $stream_settings): void
    {
        $this->settings = $settings;
        $this->stream_settings = $stream_settings;
    }

    /**
     * Replace only the members that are provided.
     *
     * @param Settings|null $settings New protocol settings.
     * @param StreamSettings|null $stream_settings New stream settings.
     * @return void
     */
    public function modify(?Settings $settings = null, ?StreamSettings $stream_settings = null): void
    {
        if (!is_null($settings)) $this->settings = $settings;
        if (!is_null($stream_settings)) $this->stream_settings = $stream_settings;
    }
}
