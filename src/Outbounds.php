<?php

namespace XUI;

use JSON\json;
use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Inbounds\Protocols\Http\Http as ib_Http;
use XUI\Inbounds\Protocols\Shadowsocks\Shadowsocks as ib_Shadowsocks;
use XUI\Inbounds\Protocols\Socks\Socks as ib_Socks;
use XUI\Inbounds\Protocols\Trojan\Trojan as ib_Trojan;
use XUI\Inbounds\Protocols\Vless\Vless as ib_Vless;
use XUI\Inbounds\Protocols\Vmess\Vmess as ib_Vmess;
use XUI\Inbounds\StreamSettings as ib_StreamSettings;
use XUI\Outbounds\Protocols\Blackhole\Blackhole;
use XUI\Outbounds\Protocols\Dns\Dns;
use XUI\Outbounds\Protocols\Freedom\Freedom;
use XUI\Outbounds\Protocols\Http\Http;
use XUI\Outbounds\Protocols\Shadowsocks\Shadowsocks;
use XUI\Outbounds\Protocols\Socks\Socks;
use XUI\Outbounds\Protocols\Trojan\Trojan;
use XUI\Outbounds\Protocols\Vless\Vless;
use XUI\Outbounds\Protocols\Vmess\Vmess;

class Outbounds
{
    private Xray $xray;

    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Add outbound
     * @param string $tag
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config
     * @param array|null $proxy_settings
     * @param string $send_through
     * @param array $mux
     * @return Result
     */
    public function add(
        string $tag, Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config, array $proxy_settings = null,
        string $send_through = '0.0.0.0', array $mux = []
    ): Result
    {
        $protocol = $config->protocol;
        if (!isset($this->xray)) $this->xray = new Xray($this->request);
        $result = $this->xray->get('outbounds');
        if ($result->ok && $result->success) {
            $xray_outbounds = $result->response(Statics::OUTPUT_ARRAY);
            if (isset($config->stream_settings))
                $stream_settings = $config->stream_settings->stream_settings();
            else
                $stream_settings = [];
            $outbound = [
                'sendThrough' => $send_through,
                'protocol' => $protocol,
                'settings' => $config->settings->settings(),
                'tag' => $tag,
                'streamSettings' => $stream_settings,
            ];
            if (!empty($proxy_settings)) $outbound['proxySettings'] = $proxy_settings;
            if (!empty($mux)) $outbound['mux'] = $mux;
            $xray_outbounds[] = $outbound;
            return $this->xray->update(['outbounds' => $xray_outbounds]);
        } else {
            return $result;
        }
    }

    /**
     * List all outbounds from xray config.\
     * Similar $xray->get_config('outbounds')
     * @param int $output
     * @return Result|string|array
     */
    public function list(int $output = Statics::OUTPUT_OBJECT): Result|string|array
    {
        if (!isset($this->xray)) $this->xray = new Xray($this->request);
        return $this->xray->get('outbounds', output: $output);
    }

    /**
     * Get an outbound
     * @param string $outbound_tag
     * @return Result
     */
    public function get(string $outbound_tag): Result
    {
        if (!isset($this->xray)) $this->xray = new Xray($this->request);
        $result = $this->xray->get('outbounds');
        if ($result->ok && $result->success) {
            $xray_outbounds = $result->response(Statics::OUTPUT_ARRAY);
            foreach ($xray_outbounds as $outbound):
                if ($outbound['tag'] == $outbound_tag)
                    return Result::make_ok(Result::make_response(true, $outbound));
            endforeach;
            return Result::make_fail(404, 'Outbound tag not found');
        } else {
            return $result;
        }
    }

    /**
     * Check outbound availability.
     * @param string $outbound_tag
     * @return bool
     */
    public function exist(string $outbound_tag): bool
    {
        if (!isset($this->xray)) $this->xray = new Xray($this->request);
        $result = $this->xray->get('outbounds');
        if ($result->ok && $result->success):
            $xray_outbounds = $result->response(Statics::OUTPUT_ARRAY);
            foreach ($xray_outbounds as $outbound):
                if ($outbound['tag'] == $outbound_tag)
                    return true;
            endforeach;
        endif;
        return false;
    }

    /**
     * Update an outbound
     * @param string $outbound_tag
     * @param string|null $tag
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole|null $config
     * @param array|null $proxy_settings
     * @param string|null $send_through
     * @param array|null $mux
     * @return Result
     */
    public function update(
        string $outbound_tag, string $tag = null, Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config = null,
        array  $proxy_settings = null, string $send_through = null, array $mux = null
    ): Result
    {
        if (!isset($this->xray)) $this->xray = new Xray($this->request);
        $result = $this->xray->get('outbounds');
        if ($result->ok && $result->success) {
            $xray_outbounds = $result->response(Statics::OUTPUT_ARRAY);
            $updated = false;
            foreach ($xray_outbounds as $key => $outbound):
                if ($outbound['tag'] == $outbound_tag):
                    $tag = (is_null($tag)) ? $outbound['tag'] : $tag;
                    if (!is_null($config)) {
                        $protocol = $config->protocol;
                        $settings = $config->settings->settings();
                        if (isset($config->stream_settings))
                            $stream_settings = $config->stream_settings->stream_settings();
                        else
                            $stream_settings = [];
                    } else {
                        $protocol = $outbound['protocol'];
                        $settings = $outbound['settings'];
                        $stream_settings = $outbound['streamSettings'];
                    }
                    $outbound = [
                        'sendThrough' => $send_through,
                        'protocol' => $protocol,
                        'settings' => $settings,
                        'tag' => $tag,
                        'streamSettings' => $stream_settings,
                        'proxySettings' => $proxy_settings,
                        'mux' => $mux
                    ];
                    if (!is_null($proxy_settings)) $outbound['proxySettings'] = $proxy_settings;
                    if (!is_null($send_through)) $outbound['sendThrough'] = $send_through;
                    if (!is_null($mux)) $outbound['mux'] = $mux;
                    $xray_outbounds[$key] = $outbound;
                    $updated = true;
                    break;
                endif;
            endforeach;
            if ($updated)
                return $this->xray->update(['outbounds' => $xray_outbounds]);
            else
                return Result::make_fail(404, 'Outbound tag not found');
        } else {
            return $result;
        }
    }

    /**
     * Delete an outbound
     * @param string $outbound_tag
     * @return Result
     */
    public function delete(string $outbound_tag): Result
    {
        if (!isset($this->xray)) $this->xray = new Xray($this->request);
        $result = $this->xray->get('outbounds');
        if ($result->ok && $result->success) {
            $xray_outbounds = $result->response(Statics::OUTPUT_ARRAY);
            $deleted = false;
            foreach ($xray_outbounds as $key => $outbound):
                if ($outbound['tag'] == $outbound_tag):
                    unset($xray_outbounds[$key]);
                    $deleted = true;
                    break;
                endif;
            endforeach;
            if ($deleted)
                return $this->xray->update(['outbounds' => $xray_outbounds]);
            else
                return Result::make_fail(404, 'Outbound tag not found');
        } else {
            return $result;
        }
    }

    /**
     * Read outbound
     * @param array|string|object $inbound
     * @return Http|Socks|Vless|false|Trojan|Shadowsocks|Vmess
     */
    public static function read(array|string|object $outbound): Http|Socks|Vless|false|Trojan|Shadowsocks|Vmess
    {
        $outbound = json::to_object($outbound);
        if (is_object($outbound)) {
            switch ($outbound->protocol):
                case 'vmess':
                    $settings = $outbound->settings;
                    $stream = $outbound->streamSettings;
                    $config = new Vmess(json::_out($settings), json::_out($stream));
                break;
                case 'vless':
                    $settings = $outbound->settings;
                    $stream = $outbound->streamSettings;
                    $config = new Vless(json::_out($settings), json::_out($stream));
                break;
                case 'trojan':
                    $settings = $outbound->settings;
                    $stream = $outbound->streamSettings;
                    $config = new Trojan(json::_out($settings), json::_out($stream));
                break;
                case 'shadowsocks':
                    $settings = $outbound->settings;
                    $stream = $outbound->streamSettings;
                    $config = new Shadowsocks(json::_out($settings), json::_out($stream));
                break;
                case 'socks':
                    $settings = $outbound->settings;
                    $config = new Socks(json::_out($settings));
                break;
                case 'http':
                    $settings = $outbound->settings;
                    $config = new Http(json::_out($settings));
                break;
            endswitch;
            return $config ?? false;
        } else {
            return false;
        }
    }


    /**
     * Convert outbound to inbound
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http $outbound_config
     * @param string $listen
     * @param int|null $port
     * @return ib_Vmess|ib_Vless|ib_Trojan|ib_Shadowsocks|ib_Socks|ib_Http|false
     */
    public static function to_inbound(
        Vmess|Vless|Trojan|Shadowsocks|Socks|Http $outbound_config, string $listen = '', int|null $port = null
    ): ib_Vmess|ib_Vless|ib_Trojan|ib_Shadowsocks|ib_Socks|ib_Http|false
    {
        $port = $port ?? $outbound_config->settings->port;
        switch ($outbound_config->protocol):
            case 'vmess':
                $settings = $outbound_config->settings;
                $stream = $outbound_config->stream_settings;
                $config = new ib_Vmess($listen, $port);
                $config_settings = new \XUI\Inbounds\Protocols\Vmess\Settings();
                $config_settings->add_client(true, $settings->users[0]['id']);
            break;
            case 'vless':
                $settings = $outbound_config->settings;
                $stream = $outbound_config->stream_settings;
                $config = new ib_Vless($listen, $port);
                $config_settings = new \XUI\Inbounds\Protocols\Vless\Settings();
                $config_settings->add_client(true, $settings->users[0]['id']);
            break;
            case 'trojan':
                $settings = $outbound_config->settings;
                $stream = $outbound_config->stream_settings;
                $config = new ib_Trojan($listen, $port);
                $config_settings = new \XUI\Inbounds\Protocols\Trojan\Settings();
                $config_settings->add_client(true, $settings->users[0]['email'], $settings->users[0]['password']);
            break;
            case 'shadowsocks':
                $settings = $outbound_config->settings;
                $stream = $outbound_config->stream_settings;
                $config = new ib_Shadowsocks($listen, $port);
                $config_settings = new \XUI\Inbounds\Protocols\Shadowsocks\Settings(
                    [], $port, $settings->password, $settings->method, $settings->email
                );
                $config_settings->add_client(true, $settings->method, $settings->password, $settings->email);
            break;
            case 'socks':
                $settings = $outbound_config->settings;
                $config = new ib_Socks($listen, $port);
                $config_settings = new \XUI\Inbounds\Protocols\Socks\Settings();
                $config_settings->add_account($settings->users[0]['username'], $settings->users[0]['password']);
            break;
            case 'http':
                $settings = $outbound_config->settings;
                $config = new ib_Http($listen, $port);
                $config_settings = new \XUI\Inbounds\Protocols\Http\Settings();
                $config_settings->add_account($settings->users[0]['username'], $settings->users[0]['password']);
            break;
        endswitch;
        if (isset($config_settings))
            $config->settings = $config_settings;
        if (isset($stream)):
            $config_stream = new ib_StreamSettings($stream->network, $stream->security);
            switch ($stream->network):
                case 'tcp':
                    $accept_proxy_protocol = $stream->tcp_settings['acceptProxyProtocol'] ?? false;
                    $header_type = $stream->tcp_settings['header']['type'];
                    $header_request = ($header_type == 'http') ? [
                        'version' => $stream->tcp_settings['header']['request']['version'],
                        'method' => $stream->tcp_settings['header']['request']['method'],
                        'path' => $stream->tcp_settings['header']['request']['path'],
                    ] : [];
                    $header_response = ($header_type == 'http') ? [
                        'version' => $stream->tcp_settings['header']['request']['version'],
                        'status' => 200,
                        'reason' => 'OK',
                    ] : [];
                    $config_stream->tcp_settings($accept_proxy_protocol, $header_type, $header_request, $header_response);
                break;
                case 'ws':
                    $accept_proxy_protocol = $stream->tcp_settings['acceptProxyProtocol'] ?? false;
                    $config_stream->ws_settings($accept_proxy_protocol, $stream->ws_settings['path']);
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
            endswitch;
            $config->stream_settings = $config_stream;
        endif;
        return $config ?? false;
    }
}