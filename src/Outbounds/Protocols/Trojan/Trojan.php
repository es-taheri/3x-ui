<?php

namespace XUI\Outbounds\Protocols\Trojan;

use XUI\Outbounds\StreamSettings;

/**
 * Trojan outbound configuration.
 *
 * @link https://xtls.github.io/en/config/outbounds/trojan.html
 */
class Trojan
{
    /** Xray protocol name. */
    public string $protocol = 'trojan';

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
            $this->settings = new Settings(
                (string)($data['servers'][0]['address'] ?? ''),
                (int)($data['servers'][0]['port'] ?? 0),
                (string)($data['servers'][0]['password'] ?? ''),
                isset($data['servers'][0]['email']) ? (string)$data['servers'][0]['email'] : null
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
