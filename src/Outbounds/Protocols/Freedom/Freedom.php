<?php

namespace XUI\Outbounds\Protocols\Freedom;

/**
 * Freedom outbound configuration.
 *
 * @link https://xtls.github.io/en/config/outbounds/freedom.html
 */
class Freedom
{
    /** Xray protocol name. */
    public string $protocol = 'freedom';

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
                (string)($data['domainStrategy'] ?? 'AsIs'),
                isset($data['redirect']) ? (int)$data['redirect'] : null,
                isset($data['fragment']) && is_array($data['fragment']) ? $data['fragment'] : null,
                (int)($data['proxyProtocol'] ?? 0),
                isset($data['noise']) && is_array($data['noise']) ? $data['noise'] : null
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
