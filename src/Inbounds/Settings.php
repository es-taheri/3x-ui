<?php

namespace XUI\Inbounds;

/**
 * Generic, protocol-agnostic inbound settings container.
 *
 * Use it when a protocol has no dedicated configuration class yet:
 *
 * ```php
 * $settings = Settings::make('wireguard', ['secretKey' => '...', 'peers' => []]);
 * ```
 */
class Settings implements InboundConfig
{
    /** Raw inbound `settings` object. */
    public array $settings = [];

    /** Xray protocol name. */
    public string $protocol = '';

    /**
     * @param string $protocol Xray protocol name.
     * @param array $settings Raw `settings` object.
     */
    public function __construct(string $protocol = '', array $settings = [])
    {
        $this->protocol = $protocol;
        $this->settings = $settings;
    }

    /**
     * Named constructor.
     *
     * @param string|null $protocol Xray protocol name.
     * @param array $settings Raw `settings` object.
     * @return self
     */
    public static function make(?string $protocol = null, array $settings = []): self
    {
        return new self($protocol ?? '', $settings);
    }

    /**
     * Configure a VLESS settings object.
     *
     * @param array $clients Client list.
     * @param string $encryption Inbound encryption.
     * @param string $decryption Inbound decryption.
     * @param array $fallbacks Fallback rules.
     * @return self
     */
    public function vless(array $clients = [], string $encryption = 'none', string $decryption = 'none', array $fallbacks = []): self
    {
        $this->protocol = 'vless';
        $this->settings = [
            'clients' => $clients,
            'encryption' => $encryption,
            'decryption' => $decryption,
            'fallbacks' => $fallbacks,
        ];
        return $this;
    }

    /**
     * Configure a VMess settings object.
     *
     * @param array $clients Client list.
     * @param int $default_level Default user level.
     * @return self
     */
    public function vmess(array $clients = [], int $default_level = 0): self
    {
        $this->protocol = 'vmess';
        $this->settings = [
            'clients' => $clients,
            'default' => ['level' => $default_level],
        ];
        return $this;
    }

    /**
     * Configure a Trojan settings object.
     *
     * @param array $clients Client list.
     * @param array $fallbacks Fallback rules.
     * @return self
     */
    public function trojan(array $clients = [], array $fallbacks = []): self
    {
        $this->protocol = 'trojan';
        $this->settings = [
            'clients' => $clients,
            'fallbacks' => $fallbacks,
        ];
        return $this;
    }

    /**
     * Configure a Shadowsocks settings object.
     *
     * @param array $clients Client list.
     * @param string $method Inbound cipher.
     * @param string|null $password Inbound password, generated when omitted.
     * @param string $network Enabled transports.
     * @param bool $iv_check Enable the IV check hardening.
     * @return self
     */
    public function shadowsocks(
        array $clients = [],
        string $method = \XUI\Inbounds\Protocols\Shadowsocks\Settings::METHOD_aes_128_gcm,
        ?string $password = null,
        string $network = \XUI\Inbounds\Protocols\Shadowsocks\Settings::NETWORK_TCP_UDP,
        bool $iv_check = false
    ): self
    {
        $this->protocol = 'shadowsocks';
        $this->settings = [
            'clients' => $clients,
            'method' => $method,
            'password' => $password ?? \XUI\Helper\Statics::random(16),
            'network' => $network,
            'ivCheck' => $iv_check,
        ];
        return $this;
    }

    /**
     * Configure a SOCKS settings object.
     *
     * @param array $accounts Account list.
     * @param bool $udp Enable UDP relay.
     * @return self
     */
    public function socks(array $accounts = [], bool $udp = false): self
    {
        $this->protocol = 'socks';
        $this->settings = $accounts === []
            ? ['auth' => 'noauth', 'udp' => $udp, 'ip' => '127.0.0.1']
            : ['auth' => 'password', 'accounts' => $accounts, 'udp' => $udp, 'ip' => '127.0.0.1'];
        return $this;
    }

    /**
     * Configure an HTTP proxy settings object.
     *
     * @param array $accounts Account list.
     * @param int $timeout Idle timeout in seconds.
     * @return self
     */
    public function http(array $accounts = [], int $timeout = 30): self
    {
        $this->protocol = 'http';
        $this->settings = [
            'timeout' => $timeout,
            'accounts' => $accounts,
        ];
        return $this;
    }

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
        return $this->settings;
    }

    /**
     * {@inheritDoc}
     */
    public function stream_settings_payload(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function sniffing_payload(): array
    {
        return [];
    }
}
