<?php

namespace XUI\Outbounds;

use XUI\Helper\Statics;

/**
 * Builds the `streamSettings` object of an Xray outbound (client side).
 *
 * @link https://xtls.github.io/en/config/transport.html
 */
class StreamSettings
{
    /** Transport name: raw TCP. */
    public const NETWORK_TCP = 'tcp';

    /** Transport name: mKCP. */
    public const NETWORK_KCP = 'kcp';

    /** Transport name: WebSocket. */
    public const NETWORK_WS = 'ws';

    /** Transport name: HTTP/2. */
    public const NETWORK_HTTP = 'http';

    /** Transport name: QUIC. */
    public const NETWORK_QUIC = 'quic';

    /** Transport name: gRPC. */
    public const NETWORK_GRPC = 'grpc';

    /** Transport name: HTTPUpgrade. */
    public const NETWORK_HTTPUPGRADE = 'httpupgrade';

    /** Transport name: XHTTP. */
    public const NETWORK_XHTTP = 'xhttp';

    /** Transport name: Unix domain socket. */
    public const NETWORK_DOMAINSOCKET = 'domainsocket';

    /** Security name: none. */
    public const SECURITY_NONE = 'none';

    /** Security name: TLS. */
    public const SECURITY_TLS = 'tls';

    /** Security name: REALITY. */
    public const SECURITY_REALITY = 'reality';

    /** Selected transport. */
    public string $network;

    /** Selected security layer. */
    public string $security;

    /** `tlsSettings` object. */
    public array $tls_settings = [];

    /** `tcpSettings` object. */
    public array $tcp_settings = [];

    /** `kcpSettings` object. */
    public array $kcp_settings = [];

    /** `wsSettings` object. */
    public array $ws_settings = [];

    /** `httpSettings` object. */
    public array $http_settings = [];

    /** `quicSettings` object. */
    public array $quic_settings = [];

    /** `dsSettings` object. */
    public array $ds_settings = [];

    /** `grpcSettings` object. */
    public array $grpc_settings = [];

    /** `httpupgradeSettings` object. */
    public array $httpupgrade_settings = [];

    /** `xhttpSettings` object. */
    public array $xhttp_settings = [];

    /** `sockopt` object. */
    public array $sockopt = [];

    /** `realitySettings` object. */
    public array $reality_settings = [];

    /**
     * @param string $network Selected transport.
     * @param string $security Selected security layer.
     */
    public function __construct(string $network, string $security)
    {
        $this->network = $network;
        $this->security = $security;
    }

    /**
     * Rebuild an instance from a decoded `streamSettings` array.
     *
     * @param array $stream_settings Decoded stream settings.
     * @return self
     */
    public static function from_array(array $stream_settings): self
    {
        $instance = new self(
            (string)($stream_settings['network'] ?? self::NETWORK_TCP),
            (string)($stream_settings['security'] ?? self::SECURITY_NONE)
        );
        $map = [
            'tlsSettings' => 'tls_settings',
            'tcpSettings' => 'tcp_settings',
            'kcpSettings' => 'kcp_settings',
            'wsSettings' => 'ws_settings',
            'httpSettings' => 'http_settings',
            'quicSettings' => 'quic_settings',
            'dsSettings' => 'ds_settings',
            'grpcSettings' => 'grpc_settings',
            'httpupgradeSettings' => 'httpupgrade_settings',
            'xhttpSettings' => 'xhttp_settings',
            'sockopt' => 'sockopt',
            'realitySettings' => 'reality_settings',
        ];
        foreach ($map as $key => $property) {
            if (isset($stream_settings[$key]) && is_array($stream_settings[$key])) {
                $instance->{$property} = $stream_settings[$key];
            }
        }
        return $instance;
    }

    /**
     * Configure TLS.
     *
     * @param string $server_name SNI used by the client.
     * @param bool $allow_insecure Accept any certificate.
     * @param array $alpn ALPN list.
     * @param string $fingerprint uTLS fingerprint.
     * @param bool $show Keep the settings editable in the panel UI.
     * @return void
     * @link https://xtls.github.io/en/config/transport.html#tlsobject
     */
    public function tls_settings(
        string $server_name,
        bool $allow_insecure = false,
        array $alpn = ['h2', 'http/1.1'],
        string $fingerprint = 'chrome',
        bool $show = false
    ): void
    {
        $this->security = self::SECURITY_TLS;
        $this->tls_settings = [
            'serverName' => $server_name,
            'allowInsecure' => $allow_insecure,
            'alpn' => $alpn,
            'fingerprint' => $fingerprint,
            'show' => $show,
        ];
        $this->reality_settings = [];
    }

    /**
     * Configure raw TCP.
     *
     * @param string $header_type `none` or `http`.
     * @param array $header_request HTTP camouflage request.
     * @return void
     * @link https://xtls.github.io/en/config/transports/tcp.html#tcpobject
     */
    public function tcp_settings(string $header_type = 'none', array $header_request = []): void
    {
        $this->network = self::NETWORK_TCP;
        $this->tcp_settings = ['header' => ['type' => $header_type]];
        if (!empty($header_request)) {
            $this->tcp_settings['header']['request'] = $header_request;
        }
        $this->reset_transports_except(self::NETWORK_TCP);
    }

    /**
     * Configure mKCP.
     *
     * @param string $header_type Obfuscation header.
     * @param string|null $seed Optional obfuscation seed.
     * @param int $mtu Maximum transmission unit.
     * @param int $tti Transmission time interval in milliseconds.
     * @param int $uplink_capacity Uplink capacity in MB/s.
     * @param int $down_link_capacity Downlink capacity in MB/s.
     * @param bool $congestion Enable congestion control.
     * @param int $read_buffer_size Read buffer in MB.
     * @param int $write_buffer_size Write buffer in MB.
     * @return void
     * @link https://xtls.github.io/en/config/transports/mkcp.html#kcpobject
     */
    public function kcp_settings(
        string $header_type = 'none',
        ?string $seed = null,
        int $mtu = 1350,
        int $tti = 50,
        int $uplink_capacity = 5,
        int $down_link_capacity = 20,
        bool $congestion = false,
        int $read_buffer_size = 2,
        int $write_buffer_size = 5
    ): void
    {
        $this->network = self::NETWORK_KCP;
        $this->kcp_settings = [
            'mtu' => $mtu,
            'tti' => $tti,
            'uplinkCapacity' => $uplink_capacity,
            'downlinkCapacity' => $down_link_capacity,
            'congestion' => $congestion,
            'readBufferSize' => $read_buffer_size,
            'writeBufferSize' => $write_buffer_size,
            'header' => ['type' => $header_type],
            'seed' => $seed,
        ];
        $this->reset_transports_except(self::NETWORK_KCP);
    }

    /**
     * Configure WebSocket.
     *
     * @param bool $accept_proxy_protocol Accept the PROXY protocol.
     * @param string $path WebSocket path.
     * @param string $host Value of the `Host` header.
     * @param array $headers Extra HTTP headers.
     * @return void
     * @link https://xtls.github.io/en/config/transports/websocket.html#websocketobject
     */
    public function ws_settings(bool $accept_proxy_protocol = false, string $path = '/', string $host = '', array $headers = []): void
    {
        $this->network = self::NETWORK_WS;
        $this->ws_settings = [
            'acceptProxyProtocol' => $accept_proxy_protocol,
            'host' => $host,
            'path' => $path,
        ];
        if (!empty($headers)) {
            $this->ws_settings['headers'] = $headers;
        }
        $this->reset_transports_except(self::NETWORK_WS);
    }

    /**
     * Configure HTTP/2.
     *
     * @param string $method HTTP method.
     * @param string $path HTTP path.
     * @param array $host Allowed `Host` values.
     * @param int|null $read_idle_timeout Idle timeout in seconds.
     * @param int $health_check_timeout Health check timeout in seconds.
     * @param array $headers_header Extra headers.
     * @return void
     * @link https://xtls.github.io/en/config/transports/h2.html#httpobject
     */
    public function http_settings(
        string $method = 'PUT',
        string $path = '/',
        array $host = [],
        ?int $read_idle_timeout = null,
        int $health_check_timeout = 15,
        array $headers_header = []
    ): void
    {
        $this->network = self::NETWORK_HTTP;
        $this->http_settings = [
            'host' => $host,
            'path' => $path,
            'read_idle_timeout' => $read_idle_timeout,
            'health_check_timeout' => $health_check_timeout,
            'method' => $method,
        ];
        if (!empty($headers_header)) {
            $this->http_settings['headers'] = ['header' => $headers_header];
        }
        $this->reset_transports_except(self::NETWORK_HTTP);
    }

    /**
     * Configure QUIC.
     *
     * @param string $security QUIC encryption.
     * @param string|null $key Pre-shared key, generated when omitted.
     * @param string $header_type Obfuscation header.
     * @return void
     * @link https://xtls.github.io/en/config/transports/quic.html#quicobject
     */
    public function quic_settings(string $security = 'none', ?string $key = null, string $header_type = 'none'): void
    {
        $this->network = self::NETWORK_QUIC;
        $this->quic_settings = [
            'security' => $security,
            'key' => $key ?? Statics::random(32),
            'header' => ['type' => $header_type],
        ];
        $this->reset_transports_except(self::NETWORK_QUIC);
    }

    /**
     * Configure gRPC.
     *
     * @param string|null $service_name gRPC service name, generated when omitted.
     * @param bool $multi_mode Enable multi-mode.
     * @param int $idle_timeout Idle timeout in seconds.
     * @param int $health_check_timeout Health check timeout in seconds.
     * @param bool $permit_without_stream Keep the stream alive without traffic.
     * @param int $initial_windows_size Initial HTTP/2 window size.
     * @return void
     * @link https://xtls.github.io/en/config/transports/grpc.html#grpcobject
     */
    public function grpc_settings(
        ?string $service_name = null,
        bool $multi_mode = false,
        int $idle_timeout = 60,
        int $health_check_timeout = 20,
        bool $permit_without_stream = false,
        int $initial_windows_size = 0
    ): void
    {
        $this->network = self::NETWORK_GRPC;
        $this->grpc_settings = [
            'serviceName' => $service_name ?? Statics::random(12),
            'multiMode' => $multi_mode,
            'idle_timeout' => $idle_timeout,
            'health_check_timeout' => $health_check_timeout,
            'permit_without_stream' => $permit_without_stream,
            'initial_windows_size' => $initial_windows_size,
        ];
        $this->reset_transports_except(self::NETWORK_GRPC);
    }

    /**
     * Configure HTTPUpgrade.
     *
     * @param bool $accept_proxy_protocol Accept the PROXY protocol.
     * @param string $host Value of the `Host` header.
     * @param string $path HTTP path.
     * @param array $headers Extra HTTP headers.
     * @return void
     * @link https://xtls.github.io/en/config/transports/httpupgrade.html
     */
    public function httpupgrade_settings(bool $accept_proxy_protocol = false, string $host = '', string $path = '/', array $headers = []): void
    {
        $this->network = self::NETWORK_HTTPUPGRADE;
        $this->httpupgrade_settings = [
            'acceptProxyProtocol' => $accept_proxy_protocol,
            'host' => $host,
            'path' => $path,
        ];
        if (!empty($headers)) {
            $this->httpupgrade_settings['headers'] = $headers;
        }
        $this->reset_transports_except(self::NETWORK_HTTPUPGRADE);
    }

    /**
     * Configure XHTTP.
     *
     * @param string $path HTTP path.
     * @param string $host Value of the `Host` header.
     * @param string $mode Transfer mode.
     * @param array $headers Extra HTTP headers.
     * @param array $extra Free-form `extra` object.
     * @return void
     * @link https://xtls.github.io/en/config/transports/splithttp.html
     */
    public function xhttp_settings(string $path = '/', string $host = '', string $mode = 'auto', array $headers = [], array $extra = []): void
    {
        $this->network = self::NETWORK_XHTTP;
        $this->xhttp_settings = [
            'path' => $path,
            'host' => $host,
            'mode' => $mode,
        ];
        if (!empty($headers)) {
            $this->xhttp_settings['headers'] = $headers;
        }
        if (!empty($extra)) {
            $this->xhttp_settings['extra'] = $extra;
        }
        $this->reset_transports_except(self::NETWORK_XHTTP);
    }

    /**
     * Configure a Unix domain socket transport.
     *
     * @param string $path Socket path.
     * @param bool $abstract Use the Linux abstract namespace.
     * @param bool $padding Pad the payload.
     * @return void
     * @link https://xtls.github.io/en/config/transports/domainsocket.html#domainsocketobject
     */
    public function ds_settings(string $path = '/', bool $abstract = false, bool $padding = false): void
    {
        $this->network = self::NETWORK_DOMAINSOCKET;
        $this->ds_settings = [
            'path' => $path,
            'abstract' => $abstract,
            'padding' => $padding,
        ];
        $this->reset_transports_except(self::NETWORK_DOMAINSOCKET);
    }

    /**
     * Configure socket options.
     *
     * @param int $mark Firewall mark.
     * @param bool $tcp_fast_open Enable TCP Fast Open.
     * @param string $tproxy TPROXY mode.
     * @param string $domain_strategy Domain strategy.
     * @param string $dialer_proxy Tag of the outbound used to dial this one.
     * @param bool $accept_proxy_protocol Accept the PROXY protocol.
     * @param int $tcp_keep_alive_interval Keep-alive interval in seconds.
     * @param string $tcpcongestion TCP congestion control algorithm.
     * @param string $interface Bind to this network interface.
     * @param bool $tcp_mptcp Enable Multipath TCP.
     * @param bool $tcp_no_delay Disable Nagle's algorithm.
     * @return void
     * @link https://xtls.github.io/en/config/transport.html#sockoptobject
     */
    public function sockopt(
        int $mark = 0,
        bool $tcp_fast_open = false,
        string $tproxy = 'off',
        string $domain_strategy = 'AsIs',
        string $dialer_proxy = '',
        bool $accept_proxy_protocol = false,
        int $tcp_keep_alive_interval = 0,
        string $tcpcongestion = '',
        string $interface = '',
        bool $tcp_mptcp = false,
        bool $tcp_no_delay = false
    ): void
    {
        $this->sockopt = [
            'mark' => $mark,
            'tcpFastOpen' => $tcp_fast_open,
            'tproxy' => $tproxy,
            'domainStrategy' => $domain_strategy,
            'dialerProxy' => $dialer_proxy,
            'acceptProxyProtocol' => $accept_proxy_protocol,
            'tcpKeepAliveInterval' => $tcp_keep_alive_interval,
            'tcpcongestion' => $tcpcongestion,
            'interface' => $interface,
            'tcpMptcp' => $tcp_mptcp,
            'tcpNoDelay' => $tcp_no_delay,
        ];
    }

    /**
     * Configure REALITY (client side).
     *
     * @param bool $show Keep the settings editable in the panel UI.
     * @param string $fingerprint uTLS fingerprint.
     * @param string $server_name SNI to present.
     * @param string $public_key Server public key.
     * @param string $short_id REALITY short id.
     * @param string $spider_x Spider path.
     * @return void
     * @link https://github.com/XTLS/REALITY/blob/main/README.en.md
     */
    public function reality_settings(
        bool $show,
        string $fingerprint,
        string $server_name,
        string $public_key,
        string $short_id,
        string $spider_x = ''
    ): void
    {
        $this->security = self::SECURITY_REALITY;
        $this->reality_settings = [
            'show' => $show,
            'fingerprint' => $fingerprint,
            'serverName' => $server_name,
            'publicKey' => $public_key,
            'shortId' => $short_id,
            'spiderX' => $spider_x,
        ];
        $this->tls_settings = [];
    }

    /**
     * Render the complete `streamSettings` array.
     *
     * @return array
     */
    public function stream_settings(): array
    {
        $return = [
            'network' => $this->network,
            'security' => $this->security,
        ];
        if (!empty($this->tls_settings)) {
            $return['tlsSettings'] = $this->tls_settings;
        }
        if (!empty($this->tcp_settings)) {
            $return['tcpSettings'] = $this->tcp_settings;
        }
        if (!empty($this->kcp_settings)) {
            $return['kcpSettings'] = $this->kcp_settings;
        }
        if (!empty($this->ws_settings)) {
            $return['wsSettings'] = $this->ws_settings;
        }
        if (!empty($this->http_settings)) {
            $return['httpSettings'] = $this->http_settings;
        }
        if (!empty($this->quic_settings)) {
            $return['quicSettings'] = $this->quic_settings;
        }
        if (!empty($this->ds_settings)) {
            $return['dsSettings'] = $this->ds_settings;
        }
        if (!empty($this->grpc_settings)) {
            $return['grpcSettings'] = $this->grpc_settings;
        }
        if (!empty($this->httpupgrade_settings)) {
            $return['httpupgradeSettings'] = $this->httpupgrade_settings;
        }
        if (!empty($this->xhttp_settings)) {
            $return['xhttpSettings'] = $this->xhttp_settings;
        }
        if (!empty($this->sockopt)) {
            $return['sockopt'] = $this->sockopt;
        }
        if (!empty($this->reality_settings)) {
            $return['realitySettings'] = $this->reality_settings;
        }
        return $return;
    }

    /**
     * Clear every transport sub-object except the one selected.
     *
     * @param string $keep Network name to preserve.
     * @return void
     */
    private function reset_transports_except(string $keep): void
    {
        $transports = [
            self::NETWORK_TCP => 'tcp_settings',
            self::NETWORK_KCP => 'kcp_settings',
            self::NETWORK_WS => 'ws_settings',
            self::NETWORK_HTTP => 'http_settings',
            self::NETWORK_QUIC => 'quic_settings',
            self::NETWORK_GRPC => 'grpc_settings',
            self::NETWORK_HTTPUPGRADE => 'httpupgrade_settings',
            self::NETWORK_XHTTP => 'xhttp_settings',
            self::NETWORK_DOMAINSOCKET => 'ds_settings',
        ];
        foreach ($transports as $network => $property) {
            if ($network !== $keep) {
                $this->{$property} = [];
            }
        }
    }
}
