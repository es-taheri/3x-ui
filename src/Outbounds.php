<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Outbounds\Protocols\Blackhole\Blackhole;
use XUI\Outbounds\Protocols\Dns\Dns;
use XUI\Outbounds\Protocols\Freedom\Freedom;
use XUI\Outbounds\Protocols\Http\Http;
use XUI\Outbounds\Protocols\Shadowsocks\Shadowsocks;
use XUI\Outbounds\Protocols\Socks\Socks;
use XUI\Outbounds\Protocols\Trojan\Trojan;
use XUI\Outbounds\Protocols\Vless\Vless;
use XUI\Outbounds\Protocols\Vmess\Vmess;
use XUI\Outbounds\StreamSettings as OutboundStreamSettings;

/**
 * Outbound helpers backed by the panel's Xray config template.
 *
 * The panel API has no dedicated outbound endpoints, so outbounds are read and
 * written through `/panel/api/xray/` ({@see Xray}) while this class offers a
 * typed, tag-oriented view on top of it.
 */
class Outbounds
{
    /** Lazily created Xray helper. */
    private ?Xray $xray = null;

    /**
     * @param Request $request Shared HTTP transport.
     */
    public function __construct(
        /** @var Request Shared HTTP transport. */
        private readonly Request $request
    )
    {
    }

    /**
     * Append an outbound to the Xray config.
     *
     * @param string $tag Unique outbound tag.
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config Outbound configuration.
     * @param array|null $proxy_settings Optional `proxySettings` object.
     * @param string $send_through Source address used for outgoing connections.
     * @param array $mux Optional `mux` object.
     * @return Result
     */
    public function add(
        string $tag,
        Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config,
        ?array $proxy_settings = null,
        string $send_through = '0.0.0.0',
        array $mux = []
    ): Result
    {
        $xray = $this->xray();
        $result = $xray->get('outbounds');
        if (!$result->ok || !$result->success) {
            return $result;
        }

        $outbounds = (array)$result->obj(Statics::OUTPUT_ARRAY);
        $outbound = [
            'sendThrough' => $send_through,
            'protocol' => $config->protocol,
            'settings' => $config->settings->settings(),
            'tag' => $tag,
            'streamSettings' => isset($config->stream_settings) ? $config->stream_settings->stream_settings() : [],
        ];
        if (!empty($proxy_settings)) {
            $outbound['proxySettings'] = $proxy_settings;
        }
        if (!empty($mux)) {
            $outbound['mux'] = $mux;
        }
        $outbounds[] = $outbound;

        return $xray->update(['outbounds' => $outbounds]);
    }

    /**
     * List every outbound of the current Xray config.
     *
     * @return Result
     */
    public function list(): Result
    {
        return $this->xray()->get('outbounds');
    }

    /**
     * Fetch one outbound by tag.
     *
     * @param string $outbound_tag Outbound tag.
     * @return Result
     */
    public function get(string $outbound_tag): Result
    {
        $result = $this->list();
        if (!$result->ok || !$result->success) {
            return $result;
        }
        foreach ((array)$result->obj(Statics::OUTPUT_ARRAY) as $outbound) {
            if (($outbound['tag'] ?? null) === $outbound_tag) {
                return Result::make_ok(Result::make_response(true, $outbound));
            }
        }
        return Result::make_fail(404, 'Outbound tag not found');
    }

    /**
     * Check whether an outbound tag exists.
     *
     * @param string $outbound_tag Outbound tag.
     * @return bool
     */
    public function exist(string $outbound_tag): bool
    {
        $result = $this->list();
        if (!$result->ok || !$result->success) {
            return false;
        }
        foreach ((array)$result->obj(Statics::OUTPUT_ARRAY) as $outbound) {
            if (($outbound['tag'] ?? null) === $outbound_tag) {
                return true;
            }
        }
        return false;
    }

    /**
     * Replace an outbound.
     *
     * @param string $outbound_tag Tag of the outbound to update.
     * @param string|null $tag New tag, unchanged when omitted.
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole|null $config New configuration, unchanged when omitted.
     * @param array|null $proxy_settings New `proxySettings`, unchanged when omitted.
     * @param string|null $send_through New `sendThrough`, unchanged when omitted.
     * @param array|null $mux New `mux`, unchanged when omitted.
     * @return Result
     */
    public function update(
        string $outbound_tag,
        ?string $tag = null,
        Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole|null $config = null,
        ?array $proxy_settings = null,
        ?string $send_through = null,
        ?array $mux = null
    ): Result
    {
        $xray = $this->xray();
        $result = $xray->get('outbounds');
        if (!$result->ok || !$result->success) {
            return $result;
        }

        $outbounds = (array)$result->obj(Statics::OUTPUT_ARRAY);
        foreach ($outbounds as $key => $outbound) {
            if (($outbound['tag'] ?? null) !== $outbound_tag) {
                continue;
            }

            if (!is_null($config)) {
                $outbound['protocol'] = $config->protocol;
                $outbound['settings'] = $config->settings->settings();
                $outbound['streamSettings'] = isset($config->stream_settings)
                    ? $config->stream_settings->stream_settings()
                    : [];
            }
            if (!is_null($tag)) {
                $outbound['tag'] = $tag;
            }
            if (!is_null($send_through)) {
                $outbound['sendThrough'] = $send_through;
            }
            if (!is_null($proxy_settings)) {
                $outbound['proxySettings'] = $proxy_settings;
            }
            if (!is_null($mux)) {
                $outbound['mux'] = $mux;
            }
            $outbounds[$key] = $outbound;

            return $xray->update(['outbounds' => $outbounds]);
        }

        return Result::make_fail(404, 'Outbound tag not found');
    }

    /**
     * Delete an outbound by tag.
     *
     * @param string $outbound_tag Outbound tag.
     * @return Result
     */
    public function delete(string $outbound_tag): Result
    {
        $xray = $this->xray();
        $result = $xray->get('outbounds');
        if (!$result->ok || !$result->success) {
            return $result;
        }

        $outbounds = (array)$result->obj(Statics::OUTPUT_ARRAY);
        foreach ($outbounds as $key => $outbound) {
            if (($outbound['tag'] ?? null) === $outbound_tag) {
                unset($outbounds[$key]);
                return $xray->update(['outbounds' => array_values($outbounds)]);
            }
        }

        return Result::make_fail(404, 'Outbound tag not found');
    }

    /**
     * Rebuild a typed outbound configuration from an outbound payload.
     *
     * @param array|string|object $outbound Outbound payload.
     * @return Http|Socks|Vless|Trojan|Shadowsocks|Vmess|Freedom|Blackhole|Dns|false
     */
    public static function read(
        array|string|object $outbound
    ): Http|Socks|Vless|Trojan|Shadowsocks|Vmess|Freedom|Blackhole|Dns|false
    {
        $data = match (true) {
            is_array($outbound) => $outbound,
            is_object($outbound) => (array)$outbound,
            default => (array)(json_decode($outbound, true) ?: []),
        };
        if ($data === []) {
            return false;
        }

        $settings = is_string($data['settings'] ?? null) ? $data['settings'] : json_encode($data['settings'] ?? []);
        $stream = is_string($data['streamSettings'] ?? null) ? $data['streamSettings'] : json_encode($data['streamSettings'] ?? []);

        return match ((string)($data['protocol'] ?? '')) {
            'vmess' => new Vmess($settings, $stream),
            'vless' => new Vless($settings, $stream),
            'trojan' => new Trojan($settings, $stream),
            'shadowsocks' => new Shadowsocks($settings, $stream),
            'socks' => new Socks($settings),
            'http' => new Http($settings),
            'freedom' => new Freedom($settings),
            'blackhole' => new Blackhole($settings),
            'dns' => new Dns($settings),
            default => false,
        };
    }

    /**
     * Convert a supported outbound configuration into an inbound configuration.
     *
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http $outbound_config Source outbound.
     * @param string $listen Listen address for the inbound.
     * @param int|null $port Listen port, defaults to the outbound port.
     * @return \XUI\Inbounds\Protocols\Vmess\Vmess|\XUI\Inbounds\Protocols\Vless\Vless|\XUI\Inbounds\Protocols\Trojan\Trojan|\XUI\Inbounds\Protocols\Shadowsocks\Shadowsocks|\XUI\Inbounds\Protocols\Socks\Socks|\XUI\Inbounds\Protocols\Http\Http|false
     */
    public static function to_inbound(
        Vmess|Vless|Trojan|Shadowsocks|Socks|Http $outbound_config,
        string $listen = '',
        ?int $port = null
    ): \XUI\Inbounds\Protocols\Vmess\Vmess|\XUI\Inbounds\Protocols\Vless\Vless|\XUI\Inbounds\Protocols\Trojan\Trojan|\XUI\Inbounds\Protocols\Shadowsocks\Shadowsocks|\XUI\Inbounds\Protocols\Socks\Socks|\XUI\Inbounds\Protocols\Http\Http|false
    {
        $port ??= $outbound_config->settings->port;
        $stream = isset($outbound_config->stream_settings)
            ? self::stream_to_inbound($outbound_config->stream_settings)
            : null;

        switch ($outbound_config->protocol) {
            case 'vmess':
                $config = new \XUI\Inbounds\Protocols\Vmess\Vmess($listen, $port);
                $settings = new \XUI\Inbounds\Protocols\Vmess\Settings();
                $user = $outbound_config->settings->users[0] ?? null;
                if ($user !== null) {
                    $settings->add_client(true, (string)$user['id']);
                }
                break;
            case 'vless':
                $config = new \XUI\Inbounds\Protocols\Vless\Vless($listen, $port);
                $settings = new \XUI\Inbounds\Protocols\Vless\Settings();
                $user = $outbound_config->settings->users[0] ?? null;
                if ($user !== null) {
                    $settings->add_client(true, (string)$user['id'], flow: (string)($user['flow'] ?? ''));
                }
                break;
            case 'trojan':
                $config = new \XUI\Inbounds\Protocols\Trojan\Trojan($listen, $port);
                $settings = new \XUI\Inbounds\Protocols\Trojan\Settings();
                $settings->add_client(
                    true,
                    $outbound_config->settings->email,
                    $outbound_config->settings->password
                );
                break;
            case 'shadowsocks':
                $config = new \XUI\Inbounds\Protocols\Shadowsocks\Shadowsocks($listen, $port);
                $settings = new \XUI\Inbounds\Protocols\Shadowsocks\Settings(
                    [],
                    $outbound_config->settings->password,
                    $outbound_config->settings->method
                );
                $settings->add_client(
                    true,
                    $outbound_config->settings->method,
                    $outbound_config->settings->password,
                    $outbound_config->settings->email
                );
                break;
            case 'socks':
                $config = new \XUI\Inbounds\Protocols\Socks\Socks($listen, $port);
                $settings = new \XUI\Inbounds\Protocols\Socks\Settings();
                $user = $outbound_config->settings->users[0] ?? null;
                if ($user !== null) {
                    $settings->add_account((string)$user['username'], (string)$user['password']);
                }
                break;
            case 'http':
                $config = new \XUI\Inbounds\Protocols\Http\Http($listen, $port);
                $settings = new \XUI\Inbounds\Protocols\Http\Settings();
                $user = $outbound_config->settings->users[0] ?? null;
                if ($user !== null) {
                    $settings->add_account((string)$user['username'], (string)$user['password']);
                }
                break;
            default:
                return false;
        }

        $config->settings = $settings;
        if (!is_null($stream)) {
            $config->stream_settings = $stream;
        }
        return $config;
    }

    /**
     * Convert outbound stream settings into inbound stream settings.
     *
     * @param OutboundStreamSettings $stream Outbound stream settings.
     * @return \XUI\Inbounds\StreamSettings
     */
    private static function stream_to_inbound(OutboundStreamSettings $stream): \XUI\Inbounds\StreamSettings
    {
        $in = new \XUI\Inbounds\StreamSettings($stream->network, $stream->security);

        switch ($stream->network) {
            case OutboundStreamSettings::NETWORK_TCP:
                $header = $stream->tcp_settings['header'] ?? [];
                $in->tcp_settings((bool)($stream->tcp_settings['acceptProxyProtocol'] ?? false), (string)($header['type'] ?? 'none'));
                break;
            case OutboundStreamSettings::NETWORK_WS:
                $in->ws_settings(
                    (bool)($stream->ws_settings['acceptProxyProtocol'] ?? false),
                    (string)($stream->ws_settings['path'] ?? '/'),
                    (string)($stream->ws_settings['host'] ?? ''),
                    (array)($stream->ws_settings['headers'] ?? [])
                );
                break;
            case OutboundStreamSettings::NETWORK_KCP:
                $kcp = $stream->kcp_settings;
                $in->kcp_settings(
                    (string)($kcp['header']['type'] ?? 'none'),
                    isset($kcp['seed']) ? (string)$kcp['seed'] : null,
                    (int)($kcp['mtu'] ?? 1350),
                    (int)($kcp['tti'] ?? 50),
                    (int)($kcp['uplinkCapacity'] ?? 5),
                    (int)($kcp['downlinkCapacity'] ?? 20),
                    (bool)($kcp['congestion'] ?? false),
                    (int)($kcp['readBufferSize'] ?? 2),
                    (int)($kcp['writeBufferSize'] ?? 5)
                );
                break;
            case OutboundStreamSettings::NETWORK_HTTP:
                $http = $stream->http_settings;
                $in->http_settings(
                    (string)($http['method'] ?? 'PUT'),
                    (string)($http['path'] ?? '/'),
                    (array)($http['host'] ?? []),
                    isset($http['read_idle_timeout']) ? (int)$http['read_idle_timeout'] : null,
                    (int)($http['health_check_timeout'] ?? 15)
                );
                break;
            case OutboundStreamSettings::NETWORK_GRPC:
                $grpc = $stream->grpc_settings;
                $in->grpc_settings(
                    isset($grpc['serviceName']) ? (string)$grpc['serviceName'] : null,
                    (bool)($grpc['multiMode'] ?? false),
                    (int)($grpc['idle_timeout'] ?? 60),
                    (int)($grpc['health_check_timeout'] ?? 20),
                    (bool)($grpc['permit_without_stream'] ?? false),
                    (int)($grpc['initial_windows_size'] ?? 0)
                );
                break;
            case OutboundStreamSettings::NETWORK_QUIC:
                $quic = $stream->quic_settings;
                $in->quic_settings(
                    (string)($quic['security'] ?? 'none'),
                    isset($quic['key']) ? (string)$quic['key'] : null,
                    (string)($quic['header']['type'] ?? 'none')
                );
                break;
            case OutboundStreamSettings::NETWORK_HTTPUPGRADE:
                $upgrade = $stream->httpupgrade_settings;
                $in->httpupgrade_settings(
                    (bool)($upgrade['acceptProxyProtocol'] ?? false),
                    (string)($upgrade['host'] ?? ''),
                    (string)($upgrade['path'] ?? '/'),
                    (array)($upgrade['headers'] ?? [])
                );
                break;
            case OutboundStreamSettings::NETWORK_XHTTP:
                $xhttp = $stream->xhttp_settings;
                $in->xhttp_settings(
                    (string)($xhttp['path'] ?? '/'),
                    (string)($xhttp['host'] ?? ''),
                    (string)($xhttp['mode'] ?? 'auto'),
                    (array)($xhttp['headers'] ?? []),
                    (array)($xhttp['extra'] ?? [])
                );
                break;
            case OutboundStreamSettings::NETWORK_DOMAINSOCKET:
                $ds = $stream->ds_settings;
                $in->ds_settings((string)($ds['path'] ?? '/'), (bool)($ds['abstract'] ?? false), (bool)($ds['padding'] ?? false));
                break;
        }

        if ($stream->security === OutboundStreamSettings::SECURITY_TLS) {
            $tls = $stream->tls_settings;
            $in->tls_settings(
                (string)($tls['serverName'] ?? ''),
                (bool)($tls['allowInsecure'] ?? false),
                (array)($tls['alpn'] ?? ['h2', 'http/1.1']),
                fingerprint: (string)($tls['fingerprint'] ?? '')
            );
        }

        if (!empty($stream->sockopt)) {
            $sockopt = $stream->sockopt;
            $in->sockopt(
                (int)($sockopt['mark'] ?? 0),
                (bool)($sockopt['tcpFastOpen'] ?? false),
                (string)($sockopt['tproxy'] ?? 'off'),
                (string)($sockopt['domainStrategy'] ?? 'AsIs'),
                (string)($sockopt['dialerProxy'] ?? ''),
                (bool)($sockopt['acceptProxyProtocol'] ?? false),
                (int)($sockopt['tcpKeepAliveInterval'] ?? 0),
                (string)($sockopt['tcpcongestion'] ?? ''),
                (string)($sockopt['interface'] ?? ''),
                (bool)($sockopt['tcpMptcp'] ?? false),
                (bool)($sockopt['tcpNoDelay'] ?? false)
            );
        }

        return $in;
    }

    /**
     * Return the shared Xray helper, creating it on first use.
     *
     * @return Xray
     */
    private function xray(): Xray
    {
        return $this->xray ??= new Xray($this->request);
    }
}
