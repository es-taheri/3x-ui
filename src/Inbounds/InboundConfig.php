<?php

namespace XUI\Inbounds;

/**
 * Contract implemented by every inbound configuration class.
 *
 * It lets {@see \XUI\Inbounds} accept any protocol without knowing its concrete
 * settings/stream/sniffing classes, while keeping full IDE completion on the
 * concrete classes themselves.
 */
interface InboundConfig
{
    /**
     * Xray protocol name, e.g. `vless`, `trojan` or `wireguard`.
     *
     * @return string
     */
    public function protocol(): string;

    /**
     * Value of the inbound `settings` member.
     *
     * @return array
     */
    public function settings_payload(): array;

    /**
     * Value of the inbound `streamSettings` member (empty for protocols that do not use it).
     *
     * @return array
     */
    public function stream_settings_payload(): array;

    /**
     * Value of the inbound `sniffing` member (empty for protocols that do not use it).
     *
     * @return array
     */
    public function sniffing_payload(): array;
}
