<?php

namespace XUI\Inbounds;

use JSON\json;

class StreamSettings
{
    public string $network;
    public string $security;
    public array $external_proxy;
    public array $tls_settings = [];
    public array $tcp_settings = [];
    public array $kcp_settings = [];
    public array $ws_settings = [];
    public array $http_settings = [];
    public array $quic_settings = [];
    public array $ds_settings = [];
    public array $grpc_settings = [];
    public array $sockopt = [];
    public array $reality_settings = [];

    public function __construct(string $network, string $security, array $external_proxy = [])
    {
        $this->network = $network;
        $this->security = $security;
        $this->external_proxy = $external_proxy;
    }

    /**
     * @param string $server_name
     * @param bool $reject_unknown_sni
     * @param bool $allow_insecure
     * @param array $alpn
     * @param string $min_version
     * @param string $max_version
     * @param string $cipher_suites
     * @param array $certificates
     * @param bool $disable_system_root
     * @param bool $enable_session_resumption
     * @param string $fingerprint
     * @param array $pinned_peer_certificate_chain_sha256
     * @return void
     * @link https://xtls.github.io/en/config/transport.html#tlsobject
     */
    public function tls_settings(
        string $server_name, bool $reject_unknown_sni = false, bool $allow_insecure = false, array $alpn = ['h2', 'http/1.1'],
        string $min_version = '1.2', string $max_version = '1.3', string $cipher_suites = '', array $certificates = [],
        bool   $disable_system_root = false, bool $enable_session_resumption = false, string $fingerprint = '',
        array  $pinned_peer_certificate_chain_sha256 = ['']
    ): void
    {
        $this->security = 'tls';
        $this->tls_settings = [
            'serverName' => $server_name,
            'rejectUnknownSni' => $reject_unknown_sni,
            'allowInsecure' => $allow_insecure,
            'alpn' => $alpn,
            'minVersion' => $min_version,
            'maxVersion' => $max_version,
            'cipherSuites' => $cipher_suites,
            'certificates' => json::_out($certificates),
            'disableSystemRoot' => $disable_system_root,
            'enableSessionResumption' => $enable_session_resumption,
            'fingerprint' => $fingerprint,
            'pinnedPeerCertificateChainSha256' => $pinned_peer_certificate_chain_sha256
        ];
        $this->reality_settings = [];
    }

    /**
     * TCP (Transmission Control Protocol) is currently one of the recommended transport protocols.\
     * It can be combined with various protocols in multiple ways.
     *
     * @param bool $accept_proxy_protocol
     * @param string $header_type
     * @return void
     * @link https://xtls.github.io/en/config/transports/tcp.html#tcpobject
     */
    public function tcp_settings(bool $accept_proxy_protocol, string $header_type, array $header_request = [], array $header_response = []): void
    {
        $this->network = 'tcp';
        $this->tcp_settings = [
            'acceptProxyProtocol' => $accept_proxy_protocol,
            'header' => [
                'type' => $header_type,
            ]
        ];
        if (!empty($header_request) && !empty($header_response)) :
            $this->tcp_settings['header']['request'] = $header_request;
            $this->tcp_settings['header']['response'] = $header_response;
        endif;
        $this->kcp_settings = [];
        $this->ws_settings = [];
        $this->http_settings = [];
        $this->grpc_settings = [];
        $this->quic_settings = [];
    }

    /**
     * mKCP uses UDP to emulate TCP connections.\
     * mKCP sacrifices bandwidth to reduce latency.\
     * To transmit the same content, mKCP generally consumes more data than TCP.
     *
     * @param int $mtu
     * @param int $tti
     * @param int $uplink_capacity
     * @param int $down_link_capacity
     * @param bool $congestion
     * @param int $read_buffer_size
     * @param int $write_buffer_size
     * @param string $header_type
     * @param string $seed
     * @return void
     * @link https://xtls.github.io/en/config/transports/mkcp.html#kcpobject
     */
    public function kcp_settings(
        string $header_type = 'none', string $seed = null, int $mtu = 1350, int $tti = 50, int $uplink_capacity = 5,
        int    $down_link_capacity = 20, bool $congestion = false, int $read_buffer_size = 2, int $write_buffer_size = 5
    ): void
    {
        $this->network = 'kcp';
        $this->security = 'none';
        $this->kcp_settings = [
            'mtu' => $mtu,
            'tti' => $tti,
            'uplinkCapacity' => $uplink_capacity,
            'downlinkCapacity' => $down_link_capacity,
            'congestion' => $congestion,
            'readBufferSize' => $read_buffer_size,
            'writeBufferSize' => $write_buffer_size,
            'header' => [
                'type' => $header_type
            ],
            'seed' => $seed,
        ];
        $this->tcp_settings = [];
        $this->ws_settings = [];
        $this->http_settings = [];
        $this->grpc_settings = [];
        $this->quic_settings = [];
        $this->tls_settings = [];
        $this->reality_settings = [];
    }

    /**
     * Use standard WebSocket to transmit data.\
     * WebSocket connections can be peoxied by other HTTP servers (such as Nginx) or by VLESS fallbacks path.
     *
     * @param bool $accept_proxy_protocol
     * @param string $path
     * @param string $header_host
     * @return void
     * @link https://xtls.github.io/en/config/transports/websocket.html#websocketobject
     */
    public function ws_settings(bool $accept_proxy_protocol, string $path, array $headers = []): void
    {
        $this->network = 'ws';
        $this->security = 'none';
        $this->ws_settings = [
            'acceptProxyProtocol' => $accept_proxy_protocol,
            'path' => $path,
        ];
        if (!empty($headers)) $this->ws_settings['headers'] = $headers;
        $this->tcp_settings = [];
        $this->kcp_settings = [];
        $this->http_settings = [];
        $this->grpc_settings = [];
        $this->quic_settings = [];
        $this->reality_settings = [];
    }

    /**
     * The transmission mode based on HTTP/2 fully implements the HTTP/2 standard and can be relayed by other HTTP servers (such as Nginx).\
     * Based on the recommendations of HTTP/2, both the client and server must enable TLS to use this transmission mode normally.\
     * HTTP/2 has built-in multiplexing, so it is not recommended to enable mux.cool when using HTTP/2.
     *
     * @param array $host
     * @param string $path
     * @param int $read_idle_timeout
     * @param int $health_check_timeout
     * @param int $method
     * @param string $headers_header
     * @return void
     * @link https://xtls.github.io/en/config/transports/h2.html#httpobject
     */
    public function http_settings(
        string $method = 'PUT', string $path = '/', array $host = [], int $read_idle_timeout = null,
        int    $health_check_timeout = 15, array $headers_header = []
    ): void
    {
        $this->network = 'http';
        $this->http_settings = [
            'host' => $host,
            'path' => $path,
            'read_idle_timeout' => $read_idle_timeout,
            'health_check_timeout' => $health_check_timeout,
            'method' => $method,
        ];
        if (!empty($headers_header)) $this->http_settings['headers']['header'] = $headers_header;
        $this->tcp_settings = [];
        $this->kcp_settings = [];
        $this->ws_settings = [];
        $this->grpc_settings = [];
        $this->quic_settings = [];
    }

    /**
     * QUIC (Quick UDP Internet Connection) is a protocol proposed by Google for multiplexed and concurrent transmission using UDP.\
     * <h4>Read more on link...</h4>
     * @param string $security
     * @param string $key
     * @param string $header_type
     * @return void
     * @link https://xtls.github.io/en/config/transports/quic.html#quicobject
     */
    public function quic_settings(string $security = 'none', string $key = null, string $header_type = 'none'): void
    {
        if (is_null($key)) $key = self::random(12);
        $this->network = 'quic';
        $this->security = 'none';
        $this->quic_settings = [
            'security' => $security,
            'key' => $key,
            'header' => [
                'type' => $header_type
            ],
        ];
        $this->tcp_settings = [];
        $this->kcp_settings = [];
        $this->ws_settings = [];
        $this->grpc_settings = [];
        $this->http_settings = [];
        $this->reality_settings = [];
    }

    /**
     * An modified transport protocol based on gRPC. \
     * gRPC is based on the HTTP/2 protocol and can theoretically be relayed by other servers that support HTTP/2, such as Nginx.\
     * gRPC and HTTP/2 has built-in multiplexing, so it is not recommended to enable mux.cool when using gRPC or HTTP/2.
     *
     * @param string $service_name
     * @param bool $multi_mode
     * @param int $idle_timeout
     * @param int $health_check_timeout
     * @param bool $permit_without_stream
     * @param int $initial_windows_size
     * @return void
     * @link https://xtls.github.io/en/config/transports/grpc.html#grpcobject
     */
    public function grpc_settings(
        string $service_name = null, bool $multi_mode = false, int $idle_timeout = 60, int $health_check_timeout = 20,
        bool   $permit_without_stream = false, int $initial_windows_size = 0
    ): void
    {
        if (is_null($service_name)) $service_name = self::random(12);
        $this->network = 'grpc';
        $this->grpc_settings = [
            'serviceName' => $service_name,
            'multiMode' => $multi_mode,
            'idle_timeout' => $idle_timeout,
            'health_check_timeout' => $health_check_timeout,
            'permit_without_stream' => $permit_without_stream,
            'initial_windows_size' => $initial_windows_size,
        ];
        $this->tcp_settings = [];
        $this->kcp_settings = [];
        $this->ws_settings = [];
        $this->quic_settings = [];
        $this->http_settings = [];
    }

    /**
     * Domain Socket uses standard Unix domain sockets to transmit data.\
     * <h4>Read more on link...</h4>
     * @param string $path
     * @param bool $abstract
     * @param bool $padding
     * @return void
     * @link https://xtls.github.io/en/config/transports/domainsocket.html#domainsocketobject
     */
    public function ds_settings(string $path, bool $abstract = false, bool $padding = false): void
    {
        $this->ds_settings = [
            'path' => $path,
            'abstract' => $abstract,
            'padding' => $padding,
        ];
    }

    /**
     * @param int $mark
     * @param bool $tcp_fast_open
     * @param string $tproxy
     * @param string $domain_strategy
     * @param string $dialer_proxy
     * @param bool $accept_proxy_protocol
     * @param int $tcp_keep_alive_interval
     * @param string $tcpcongestion
     * @param string $interface
     * @param bool $tcp_mptcp
     * @param bool $tcp_no_delay
     * @return void
     * @link https://xtls.github.io/en/config/transport.html#sockoptobject
     */
    public function sockopt(
        int $mark = 0, bool $tcp_fast_open = false, string $tproxy = 'off', string $domain_strategy = 'AsIs', string $dialer_proxy = '', bool $accept_proxy_protocol = false,
        int $tcp_keep_alive_interval = 0, string $tcpcongestion = '', string $interface = '', bool $tcp_mptcp = false, bool $tcp_no_delay = false
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
     * REALITY is intented to replace the use of TLS, it can eliminate the detectable TLS fingerprint on the server side, while still maintain the forward secrecy, etc.\
     * Guard against the certificate chain attack, thus its security exceeds conventional TLS REALITY can point to other people's websites, no need to buy domain names, configure TLS server, more convenient to deploy a proxy service.\
     * It achieves full real TLS that is undistingwishable with the specified SNI to the middleman
     *
     * @param bool $show
     * @param string $dest
     * @param int $xver
     * @param array $server_names
     * @param string $private_key
     * @param string $min_client_ver
     * @param string $max_client_ver
     * @param int|string $max_time_diff
     * @param array $short_ids
     * @return void
     * @link https://github.com/XTLS/REALITY/blob/main/README.en.md
     */
    public function reality_settings(
        string $private_key, string $public_key, bool $show = false, string $utls = 'chrome',
        string $dest = 'yahoo.com', int $xver = 0, array $server_names = ['yahoo.com', 'www.yahoo.com'], string $spider_x = '',
        string $min_client_ver = '', string $max_client_ver = '', int $max_time_diff = 0, array $short_ids = null
    ): void
    {
        $short_ids = (is_null($short_ids)) ? [str_shuffle('0123456789abcdef')] : $short_ids;
        $max_client_ver = is_null($max_client_ver) ? '' : $max_client_ver;
        $min_client_ver = is_null($min_client_ver) ? '' : $min_client_ver;
        $max_time_diff = is_null($max_time_diff) ? '' : $max_time_diff;
        $this->security = 'reality';
        $this->reality_settings = [
            'show' => $show,
            'dest' => $dest,
            'xver' => $xver,
            'serverNames' => $server_names,
            'privateKey' => $private_key,
            'maxClientVer' => $max_client_ver,
            'minClientVer' => $min_client_ver,
            'maxTimeDiff' => $max_time_diff,
            'shortIds' => $short_ids,
            'settings' => [
                'publicKey' => $public_key,
                'fingerprint' => $utls,
                'serverName' => '',
                'spiderX' => $spider_x
            ]
        ];
        $this->tls_settings = [];
    }

    /**
     * Returns fully structured stream settings for xray json config
     * @return array
     */
    public function stream_settings(): array
    {
        $return = [
            'network' => $this->network,
            'security' => $this->security,
            'externalProxy' => $this->external_proxy,
        ];
        if (!empty($this->tls_settings)) $return['tlsSettings'] = $this->tls_settings;
        if (!empty($this->tcp_settings)) $return['tcpSettings'] = $this->tcp_settings;
        if (!empty($this->kcp_settings)) $return['kcpSettings'] = $this->kcp_settings;
        if (!empty($this->ws_settings)) $return['wsSettings'] = $this->ws_settings;
        if (!empty($this->http_settings)) $return['httpSettings'] = $this->http_settings;
        if (!empty($this->quic_settings)) $return['quicSettings'] = $this->quic_settings;
        if (!empty($this->ds_settings)) $return['dsSettings'] = $this->ds_settings;
        if (!empty($this->grpc_settings)) $return['grpcSettings'] = $this->grpc_settings;
        if (!empty($this->sockopt)) $return['sockopt'] = $this->sockopt;
        if (!empty($this->reality_settings)) $return['realitySettings'] = $this->reality_settings;
        return $return;
    }

    public static function random(int $length = 32): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 1; $i <= $length; $i++) :
            $out .= $chars[rand(0, strlen($chars) - 1)];
        endfor;
        return $out;
    }
}