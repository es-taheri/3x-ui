<?php

namespace XUI;

use JSON\json;
use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Inbounds\Protocols\Tunnel\Tunnel;
use XUI\Inbounds\Settings;
use XUI\Inbounds\Sniffing;
use XUI\Inbounds\StreamSettings;
use XUI\Inbounds\Protocols\Http\Http;
use XUI\Inbounds\Protocols\Shadowsocks\Shadowsocks;
use XUI\Inbounds\Protocols\Socks\Socks;
use XUI\Inbounds\Protocols\Trojan\Trojan;
use XUI\Inbounds\Protocols\Vless\Vless;
use XUI\Inbounds\Protocols\Vmess\Vmess;
use XUI\Outbounds\Protocols\Http\Http as ob_Http;
use XUI\Outbounds\Protocols\Shadowsocks\Shadowsocks as ob_Shadowsocks;
use XUI\Outbounds\Protocols\Socks\Socks as ob_Socks;
use XUI\Outbounds\StreamSettings as ob_StreamSettings;
use XUI\Outbounds\Protocols\Trojan\Trojan as ob_Trojan;
use XUI\Outbounds\Protocols\Vless\Vless as ob_Vless;
use XUI\Outbounds\Protocols\Vmess\Vmess as ob_Vmess;

class Inbounds
{
    public function __construct(private readonly Request $request)
    {
    }

    public function list(bool $slim = false): Result
    {
        return $this->_request(Request::get($slim ? 'list/slim' : 'list'));
    }

    public function options(): Result
    {
        return $this->_request(Request::get('options'));
    }

    public function get(int|array $id_or_ids): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($ids = $id_or_ids as $id):
                $requests[] = Request::get("get/$id");
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::get("get/$id_or_ids"));
        }
    }

    public function add(
        Settings       $settings,
        StreamSettings $stream_settings,
        Sniffing       $sniffing,
        string         $remark,
        string         $listen = '',
        int            $port = null,
        int            $expiry_time = 0,
        int            $total = 0,
        int            $up = 0,
        int            $down = 0,
        bool           $enable = true): Result
    {
        if (is_null($port)) $port = rand(1, 65535);
        $body = [
            'enable' => $enable,
            'remark' => $remark,
            'listen' => $listen,
            'port' => $port,
            'protocol' => $protocol = $settings->protocol,
            'expiryTime' => $expiry_time,
            'total' => $total,
            'up' => $up,
            'down' => $down,
        ];
        switch ($protocol):
            case 'vmess':
            case 'vless':
            case 'trojan':
            case 'shadowsocks':
                $body['settings'] = $settings->settings;
                $body['streamSettings'] = $stream_settings->stream_settings();
                $body['sniffing'] = $sniffing->sniffing();
            break;
            case 'socks':
            case 'http':
            case 'tunnel':
                $body['settings'] = $settings->settings;
            break;
        endswitch;
        return $this->_request(Request::post('add', $body));
    }

    public function bulk_add(array $inbounds): array
    {
        $requests = [];
        foreach ($inbounds as $inbound):
            [$settings, $stream_settings, $sniffing, $remark, $listen, $port, $expiry_time, $total, $enable] = $inbound;
            if (is_null($port)) $port = rand(1, 65535);
            $body = [
                'enable' => $enable,
                'remark' => $remark,
                'listen' => $listen,
                'port' => $port,
                'protocol' => $protocol = $settings->protocol,
                'expiryTime' => $expiry_time,
                'total' => $total,
            ];
            switch ($protocol):
                case 'vmess':
                case 'vless':
                case 'trojan':
                case 'shadowsocks':
                    $body['settings'] = $settings->settings;
                    $body['streamSettings'] = $stream_settings->stream_settings();
                    $body['sniffing'] = $sniffing->sniffing();
                break;
                case 'socks':
                case 'http':
                case 'tunnel':
                    $body['settings'] = $settings->settings;
                break;
            endswitch;
            $requests[] = Request::post('add', $body);
        endforeach;
        return $this->_requests($requests);
    }

    public function delete(int|array $id_or_ids): Result
    {
        $request = is_array($id_or_ids) ? Request::post('bulkDel', ['ids' => $id_or_ids]) : Request::post("del/$id_or_ids");
        return $this->_request($request);
    }

    public function update(int|array $id_or_ids, array $inbound_or_inbounds): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($ids = $id_or_ids as $key => $id):
                $requests[] = Request::post("update/$id", $inbound_or_inbounds[$key]);
            endforeach;
            return $this->_requests($requests);
        } else {
            $id = $id_or_ids;
            return $this->_request(Request::post("update/$id", $inbound_or_inbounds));
        }
    }

    public function set_enable(int|array $id_or_ids, bool|array $enable): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($ids = $id_or_ids as $key => $id):
                $enable2 = is_array($enable) ? $enable[$key] : $enable;
                $requests[] = Request::post("setEnable/$id", ['enable' => $enable2]);
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::post("setEnable/$id_or_ids", ['enable' => $enable]));
        }
    }

    public function reset_traffic(int|array $id_or_ids): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($ids = $id_or_ids as $id):
                $requests[] = Request::post("$id/resetTraffic");
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::post("$id_or_ids/resetTraffic"));
        }

    }

    public function delete_all_clients(int|array $id_or_ids): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($ids = $id_or_ids as $id):
                $requests[] = Request::post("$id/delAllClients");
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::post("$id_or_ids/delAllClients"));
        }

    }

    public function reset_all_traffics(): Result
    {
        return $this->_request(Request::post('resetAllTraffics'));
    }

    public function import(string|array $exported_inbound_inbounds): Result|array
    {
        if (is_array($exported_inbound_inbounds)) {
            $requests = [];
            foreach ($exported_inbound_inbounds as $exported_inbound):
                $requests[] = Request::post("import", ['data' => $exported_inbound]);
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::post("import", ['data' => $exported_inbound_inbounds]));
        }
    }

    public function fallbacks(int|array $id_or_ids, array $replace = null): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($ids = $id_or_ids as $key => $id):
                $requests[] = isset($replace) ?
                    Request::post("$id/fallbacks", ['fallbacks' => $replace[$key]]) :
                    Request::get("$id/fallbacks");
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(isset($replace) ?
                Request::post("$id_or_ids/fallbacks", ['fallbacks' => $replace]) :
                Request::get("$id_or_ids/fallbacks"));
        }
    }

    /**
     * Read inbound config exported or got from server.
     * @param array|string|object $inbound
     * @return Http|Socks|Vless|false|Trojan|Shadowsocks|Vmess|Tunnel
     */
    public static function read(array|string|object $inbound): Http|Socks|Vless|false|Trojan|Shadowsocks|Vmess|Tunnel
    {
        $inbound = json::to_object($inbound);
        if (is_object($inbound)) {
            switch ($inbound->protocol):
                case 'vmess':
                    $settings = $inbound->settings;
                    $stream = $inbound->streamSettings;
                    $sniffing = $inbound->sniffing;
                    $config = new Vmess($inbound->listen, $inbound->port, $settings, $stream, $sniffing);
                break;
                case 'vless':
                    $settings = $inbound->settings;
                    $stream = $inbound->streamSettings;
                    $sniffing = $inbound->sniffing;
                    $config = new Vless($inbound->listen, $inbound->port, $settings, $stream, $sniffing);
                break;
                case 'trojan':
                    $settings = $inbound->settings;
                    $stream = $inbound->streamSettings;
                    $sniffing = $inbound->sniffing;
                    $config = new Trojan($inbound->listen, $inbound->port, $settings, $stream, $sniffing);
                break;
                case 'shadowsocks':
                    $settings = $inbound->settings;
                    $stream = $inbound->streamSettings;
                    $sniffing = $inbound->sniffing;
                    $config = new Shadowsocks($inbound->listen, $inbound->port, $settings, $stream, $sniffing);
                break;
                case 'dokodemo-door':
                    $settings = $inbound->settings;
                    $config = new Tunnel($inbound->listen, $inbound->port, $settings);
                break;
                case 'socks':
                    $settings = $inbound->settings;
                    $config = new Socks($inbound->listen, $inbound->port, $settings);
                break;
                case 'http':
                    $settings = $inbound->settings;
                    $config = new Http($inbound->listen, $inbound->port, $settings);
                break;
            endswitch;
            return $config ?? false;
        } else {
            return false;
        }
    }

    /**
     * Convert inbound config to outbound config
     * @param string $address
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http $inbound_config
     * @param int|null $port
     * @return ob_Trojan|ob_Shadowsocks|ob_Vmess|ob_Vless|ob_Socks|ob_Http|false
     */
    public static function to_outbound(
        string $address, Vmess|Vless|Trojan|Shadowsocks|Socks|Http $inbound_config, int|null $port = null
    ): ob_Trojan|ob_Shadowsocks|ob_Vmess|ob_Vless|ob_Socks|ob_Http|false
    {
        $port = $port ?? $inbound_config->port;
        switch ($inbound_config->protocol):
            case 'vmess':
                $settings = $inbound_config->settings;
                $stream = $inbound_config->stream_settings;
                $config = new ob_Vmess();
                $config_settings = new Outbounds\Protocols\Vmess\Settings($address, $port);
                $config_settings->add_user($settings->clients[0]['id']);
            break;
            case 'vless':
                $settings = $inbound_config->settings;
                $stream = $inbound_config->stream_settings;
                $config = new ob_Vless();
                $config_settings = new Outbounds\Protocols\Vless\Settings($address, $port);
                $config_settings->add_user($settings->clients[0]['id']);
            break;
            case 'trojan':
                $settings = $inbound_config->settings;
                $stream = $inbound_config->stream_settings;
                $config = new ob_Trojan();
                $config_settings = new Outbounds\Protocols\Trojan\Settings(
                    $address, $port, $settings->clients[0]['password'], $settings->clients[0]['email']
                );
            break;
            case 'shadowsocks':
                $settings = $inbound_config->settings;
                $stream = $inbound_config->stream_settings;
                $config = new ob_Shadowsocks();
                $config_settings = new Outbounds\Protocols\Shadowsocks\Settings(
                    $address, $port, $settings->password, $settings->method, $settings->email
                );
            break;
            case 'socks':
                $settings = $inbound_config->settings;
                $config = new ob_Socks();
                $config_settings = new Outbounds\Protocols\Socks\Settings($address, $port);
                $config_settings->add_user($settings->accounts[0]['username'], $settings->accounts[0]['password']);
            break;
            case 'http':
                $settings = $inbound_config->settings;
                $config = new ob_Http();
                $config_settings = new Outbounds\Protocols\Http\Settings($address, $port);
                $config_settings->add_user($settings->accounts[0]['username'], $settings->accounts[0]['password']);
            break;
        endswitch;
        if (isset($config_settings))
            $config->settings = $config_settings;
        if (isset($stream)):
            $config_stream = new ob_StreamSettings($stream->network, $stream->security);
            switch ($stream->network):
                case 'tcp':
                    $header_type = $stream->tcp_settings['header']['type'];
                    switch ($header_type):
                        case 'http':
                            $request_headers = $stream->tcp_settings['header']['request']['headers'];
                            $request_headers = (empty($request_headers)) ? ['Host' => []] : $request_headers;
                            $header_request = [
                                'version' => $stream->tcp_settings['header']['response']['version'],
                                'method' => $stream->tcp_settings['header']['request']['method'],
                                'path' => $stream->tcp_settings['header']['request']['path'],
                                'headers' => $request_headers
                            ];
                        break;
                        case 'none':
                            $header_request = [];
                        break;
                    endswitch;
                    $config_stream->tcp_settings($header_type, $header_request);
                break;
                case 'ws':
                    $config_stream->ws_settings($stream->ws_settings['acceptProxyProtocol'], $stream->ws_settings['path']);
                break;
                case 'kcp':
                    $config_stream->kcp_settings(
                        $stream->kcp_settings['header']['type'],
                        $stream->kcp_settings['seed'],
                        $stream->kcp_settings['mtu'],
                        $stream->kcp_settings['tti'],
                        $stream->kcp_settings['uplinkCapacity'],
                        $stream->kcp_settings['downLinkCapacity'],
                        $stream->kcp_settings['congestion'],
                        $stream->kcp_settings['readBufferSize'],
                        $stream->kcp_settings['writeBufferSize'],
                    );
                break;
                case 'http':
                    $config_stream->http_settings(
                        $stream->http_settings['method'],
                        $stream->http_settings['path'],
                        $stream->http_settings['host'],
                        $stream->http_settings['read_idle_timeout'],
                        $stream->http_settings['health_check_timeout']
                    );
                break;
                case 'domainsocket':
                    $config_stream->ds_settings($stream->ds_settings['path'], $stream->ds_settings['abstract'], $stream->ds_settings['padding']);
                break;
                case 'quic':
                    $config_stream->quic_settings($stream->quic_settings['security'], $stream->quic_settings['key'], $stream->quic_settings['header']['type']);
                break;
                case 'grpc':
                    $config_stream->grpc_settings(
                        $stream->grpc_settings['serviceName'],
                        $stream->grpc_settings['multiMode'],
                        $stream->grpc_settings['idle_timeout'],
                        $stream->grpc_settings['health_check_timeout'],
                        $stream->grpc_settings['permit_without_stream'],
                        $stream->grpc_settings['initial_windows_size']
                    );
                break;
            endswitch;
            switch ($stream->security):
                case 'none':
                    $config_stream->security = 'none';
                break;
                case 'tls':
                    $config_stream->tls_settings(
                        $stream->tls_settings['serverName'],
                        $stream->tls_settings['allowInsecure'],
                        $stream->tls_settings['alpn'],
                        $stream->tls_settings['fingerprint'],
                    );
                break;
                case 'reality':
                    $config_stream->reality_settings(
                        $stream->reality_settings['show'],
                        $stream->reality_settings['settings']['fingerprint'],
                        $stream->reality_settings['serverNames'][0],
                        $stream->reality_settings['settings']['publicKey'],
                        $stream->reality_settings['shortIds'][0],
                        $stream->reality_settings['settings']['spiderX']
                    );
                break;
            endswitch;
            $config->stream_settings = $config_stream;
        endif;
        return $config ?? false;
    }

    private function _request(array $request): Result
    {
        return $this->request->inbounds($request);
    }

    private function _requests(array $requests): array
    {
        return $this->request->inbounds(requests: $requests);
    }
}