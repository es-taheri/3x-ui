<?php

namespace XUI\Inbounds\Protocols;

/**
 * Default implementation of {@see \XUI\Inbounds\InboundConfig}.
 *
 * Every protocol configuration class only has to expose the matching public
 * properties (`protocol`, `settings`, `stream_settings`, `sniffing`) and use
 * this trait to satisfy the interface.
 */
trait InboundTrait
{
    /**
     * {@inheritDoc}
     */
    public function protocol(): string
    {
        return $this->protocol;
    }

    /**
     * {@inheritDoc}
     */
    public function settings_payload(): array
    {
        return isset($this->settings) ? $this->settings->settings() : [];
    }

    /**
     * {@inheritDoc}
     */
    public function stream_settings_payload(): array
    {
        return isset($this->stream_settings) ? $this->stream_settings->stream_settings() : [];
    }

    /**
     * {@inheritDoc}
     */
    public function sniffing_payload(): array
    {
        return isset($this->sniffing) ? $this->sniffing->sniffing() : [];
    }
}
