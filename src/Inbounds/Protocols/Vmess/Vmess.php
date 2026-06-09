<?php

namespace XUI\Inbounds\Protocols\Vmess;

use JSON\json;
use XUI\Inbounds\Sniffing;
use XUI\Inbounds\StreamSettings;

class Vmess
{
    public string $protocol = 'vmess';
    public string $listen;
    public int $port;
    public Settings $settings;
    public Sniffing $sniffing;
    public StreamSettings $stream_settings;
    public const NETWORK_TCP = 'tcp';
    public const NETWORK_KCP = 'kcp';
    public const NETWORK_WS = 'ws';
    public const NETWORK_HTTP = 'http';
    public const NETWORK_QUICK = 'quic';
    public const NETWORK_GRPC = 'grpc';
    public const SECURITY_NONE = 'none';
    public const SECURITY_TLS = 'tls';

    public function __construct(string $listen = null, int $port = null, string $settings = null, string $stream_settings = null, string $sniffing = null)
    {
        if (!is_null($listen)) $this->listen = $listen;
        if (!is_null($port)) $this->port = $port;
        if (!is_null($settings)):
            $settings = json::_in($settings, true);
            $this->settings = new Settings($settings['clients']);
        endif;
        if (!is_null($stream_settings)):
            $stream_settings = json::_in($stream_settings, true);
            $extrernalProxy = (isset($stream_settings['externalProxy'])) ? $stream_settings['externalProxy'] : [];
            $this->stream_settings = new StreamSettings($stream_settings['network'], $stream_settings['security'], $extrernalProxy);
            if (isset($stream_settings['tlsSettings'])) $this->stream_settings->tls_settings = $stream_settings['tlsSettings'];
            if (isset($stream_settings['tcpSettings'])) $this->stream_settings->tcp_settings = $stream_settings['tcpSettings'];
            if (isset($stream_settings['kcpSettings'])) $this->stream_settings->kcp_settings = $stream_settings['kcpSettings'];
            if (isset($stream_settings['wsSettings'])) $this->stream_settings->ws_settings = $stream_settings['wsSettings'];
            if (isset($stream_settings['httpSettings'])) $this->stream_settings->http_settings = $stream_settings['httpSettings'];
            if (isset($stream_settings['quicSettings'])) $this->stream_settings->quic_settings = $stream_settings['quicSettings'];
            if (isset($stream_settings['dsSettings'])) $this->stream_settings->ds_settings = $stream_settings['dsSettings'];
            if (isset($stream_settings['grpcSettings'])) $this->stream_settings->grpc_settings = $stream_settings['grpcSettings'];
            if (isset($stream_settings['sockopt'])) $this->stream_settings->sockopt = $stream_settings['sockopt'];
        endif;
        if (is_null($sniffing)) {
            $this->sniffing = new Sniffing();
        } else {
            $sniffing = json::_in($sniffing);
            $this->sniffing = new Sniffing($sniffing->enabled, $sniffing->destOverride);
        }
    }

    public function generate(
        string $listen = null, int $port = null, Settings $settings = null, StreamSettings $stream_settings = null, Sniffing $sniffing = null
    ): void
    {
        $this->listen = (is_null($listen)) ? '' : $listen;
        $this->port = (is_null($port)) ? rand(1024, 65535) : $port;
        if (is_null($settings)) :
            $settings = new Settings();
            $settings->add_client();
        endif;
        if (is_null($stream_settings)) :
            $stream_setting = new StreamSettings(self::NETWORK_TCP, self::SECURITY_NONE);
            $stream_setting->tcp_settings(false, 'none');
        endif;
        if (is_null($sniffing)) $sniffing = new Sniffing();
        $this->settings = $settings;
        $this->stream_settings = $stream_settings;
        $this->sniffing = $sniffing;
    }

    public function modify(
        string $listen = null, int $port = null, Settings $settings = null, StreamSettings $stream_settings = null, Sniffing $sniffing = null
    ): void
    {
        if (!is_null($listen)) $this->listen = $listen;
        if (!is_null($port)) $this->port = $port;
        if (!is_null($settings)) $this->settings = $settings;
        if (!is_null($stream_settings)) $this->stream_settings = $stream_settings;
        if (!is_null($sniffing)) $this->sniffing = $sniffing;
    }
}