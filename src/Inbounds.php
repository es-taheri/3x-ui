<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Inbounds\InboundConfig;
use XUI\Inbounds\Protocols\AmneziaWG\AmneziaWG;
use XUI\Inbounds\Protocols\Http\Http;
use XUI\Inbounds\Protocols\Hysteria\Hysteria;
use XUI\Inbounds\Protocols\Mixed\MixedInbound;
use XUI\Inbounds\Protocols\Mtproto\Mtproto;
use XUI\Inbounds\Protocols\Shadowsocks\Shadowsocks;
use XUI\Inbounds\Protocols\Socks\Socks;
use XUI\Inbounds\Protocols\Trojan\Trojan;
use XUI\Inbounds\Protocols\Tunnel\Tunnel;
use XUI\Inbounds\Protocols\Vless\Vless;
use XUI\Inbounds\Protocols\Vmess\Vmess;
use XUI\Inbounds\Protocols\WireGuard\WireGuard;
use XUI\Inbounds\StreamSettings as InboundStreamSettings;
use XUI\Outbounds\Protocols\Http\Http as OutboundHttp;
use XUI\Outbounds\Protocols\Shadowsocks\Shadowsocks as OutboundShadowsocks;
use XUI\Outbounds\Protocols\Socks\Socks as OutboundSocks;
use XUI\Outbounds\Protocols\Trojan\Trojan as OutboundTrojan;
use XUI\Outbounds\Protocols\Vless\Vless as OutboundVless;
use XUI\Outbounds\Protocols\Vmess\Vmess as OutboundVmess;
use XUI\Outbounds\StreamSettings as OutboundStreamSettings;

/**
 * Inbound endpoints of the 3x-ui panel API.
 *
 * ```php
 * $xui->inbounds->list();
 * $xui->inbounds->add($vless, 'VLESS-443');
 * $xui->inbounds->set_enable(3, false);
 * ```
 */
class Inbounds
{
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
     * List every inbound owned by the authenticated user.
     *
     * @param bool $slim Strip full client records for lighter list pages.
     * @return Result
     */
    public function list(bool $slim = false): Result
    {
        return $this->_request(Request::get($slim ? 'list/slim' : 'list'));
    }

    /**
     * Lightweight picker projection of the inbounds (id, remark, protocol, ...).
     *
     * @return Result
     */
    public function options(): Result
    {
        return $this->_request(Request::get('options'));
    }

    /**
     * Whether this host can run an AmneziaWG inbound on the kernel module.
     *
     * @return Result
     */
    public function amneziawg_kernel_readiness(): Result
    {
        return $this->_request(Request::get('amneziawgKernelReadiness'));
    }

    /**
     * Every share link of every protocol across all inbounds and clients.
     *
     * @return Result
     */
    public function all_links(): Result
    {
        return $this->_request(Request::get('allLinks'));
    }

    /**
     * Fetch one or many inbounds by id.
     *
     * @param int|array $id_or_ids Inbound id, or a list of ids for parallel requests.
     * @return Result|Result[]
     */
    public function get(int|array $id_or_ids): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($id_or_ids as $id) {
                $requests[] = Request::get("get/$id");
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::get("get/$id_or_ids"));
    }

    /**
     * Create an inbound.
     *
     * @param InboundConfig $config Protocol configuration.
     * @param string $remark Human readable remark.
     * @param string $listen Listen address, empty for every interface.
     * @param int|null $port Listen port, random when omitted.
     * @param int $expiry_time Expiry in Unix seconds.
     * @param int $total Total traffic limit in bytes.
     * @param bool $enable Whether the inbound is enabled.
     * @param int $sub_sort_index Sort order inside subscription output.
     * @param string $traffic_reset Traffic reset schedule (`never`, `hourly`, `daily`, `weekly`, `monthly`).
     * @param int $traffic_reset_day Day of month for monthly resets.
     * @param string $share_addr Share address override.
     * @param string $share_addr_strategy Share address strategy (`node`, `listen`, `custom`).
     * @param bool $exclude_from_sub Omit this inbound from subscriptions.
     * @param int|null $node_id Hosting node id.
     * @return Result
     */
    public function add(
        InboundConfig $config,
        string $remark,
        string $listen = '',
        ?int $port = null,
        int $expiry_time = 0,
        int $total = 0,
        bool $enable = true,
        int $sub_sort_index = 0,
        string $traffic_reset = 'never',
        int $traffic_reset_day = 1,
        string $share_addr = '',
        string $share_addr_strategy = 'node',
        bool $exclude_from_sub = false,
        ?int $node_id = null
    ): Result
    {
        return $this->_request(Request::post('add', $this->payload(
            $config, $remark, $listen, $port, $expiry_time, $total, $enable,
            $sub_sort_index, $traffic_reset, $traffic_reset_day, $share_addr,
            $share_addr_strategy, $exclude_from_sub, $node_id
        )));
    }

    /**
     * Create many inbounds in parallel.
     *
     * Every item is an associative array of named arguments accepted by {@see add()}.
     *
     * ```php
     * $xui->inbounds->bulk_add([
     *     ['config' => $vless, 'remark' => 'VLESS-443', 'port' => 443],
     *     ['config' => $trojan, 'remark' => 'TROJAN-8443', 'port' => 8443],
     * ]);
     * ```
     *
     * @param array $inbounds List of argument arrays.
     * @return Result[]
     */
    public function bulk_add(array $inbounds): array
    {
        $requests = [];
        foreach ($inbounds as $arguments) {
            $config = $arguments['config'] ?? null;
            if (!$config instanceof InboundConfig) {
                continue;
            }
            unset($arguments['config']);
            $arguments['remark'] ??= $config->protocol();
            $requests[] = Request::post('add', $this->payload($config, ...$arguments));
        }
        return $this->_requests($requests);
    }

    /**
     * Delete one inbound or many in one call.
     *
     * @param int|array $id_or_ids Inbound id or list of ids.
     * @return Result
     */
    public function delete(int|array $id_or_ids): Result
    {
        $request = is_array($id_or_ids)
            ? Request::post('bulkDel', ['ids' => array_values($id_or_ids)])
            : Request::post("del/$id_or_ids");
        return $this->_request($request);
    }

    /**
     * Replace an inbound configuration.
     *
     * The body mirrors {@see add()}; the panel keeps the stored client list and
     * the enable flag.
     *
     * @param int|array $id_or_ids Inbound id, or list of ids for parallel requests.
     * @param array $inbound_or_inbounds Body for one id, or a list keyed like the ids.
     * @return Result|Result[]
     */
    public function update(int|array $id_or_ids, array $inbound_or_inbounds): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($id_or_ids as $key => $id) {
                $requests[] = Request::post("update/$id", $inbound_or_inbounds[$key] ?? []);
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::post("update/$id_or_ids", $inbound_or_inbounds));
    }

    /**
     * Toggle the enable flag without serialising the whole settings JSON.
     *
     * @param int|array $id_or_ids Inbound id or list of ids.
     * @param bool|array $enable New flag, or a list keyed like the ids.
     * @return Result|Result[]
     */
    public function set_enable(int|array $id_or_ids, bool|array $enable): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($id_or_ids as $key => $id) {
                $requests[] = Request::post("setEnable/$id", ['enable' => is_array($enable) ? ($enable[$key] ?? false) : $enable]);
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::post("setEnable/$id_or_ids", ['enable' => $enable]));
    }

    /**
     * Set the subscription sort order of an inbound.
     *
     * @param int|array $id_or_ids Inbound id or list of ids.
     * @param int|array $sub_sort_index New index, or a list keyed like the ids.
     * @return Result|Result[]
     */
    public function set_sub_sort_index(int|array $id_or_ids, int|array $sub_sort_index): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($id_or_ids as $key => $id) {
                $requests[] = Request::post("$id/subSortIndex", ['subSortIndex' => is_array($sub_sort_index) ? ($sub_sort_index[$key] ?? 0) : $sub_sort_index]);
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::post("$id_or_ids/subSortIndex", ['subSortIndex' => $sub_sort_index]));
    }

    /**
     * Zero the inbound upload/download counters (clients are untouched).
     *
     * @param int|array $id_or_ids Inbound id or list of ids.
     * @return Result|Result[]
     */
    public function reset_traffic(int|array $id_or_ids): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($id_or_ids as $id) {
                $requests[] = Request::post("$id/resetTraffic");
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::post("$id_or_ids/resetTraffic"));
    }

    /**
     * Remove every client of an inbound while keeping the inbound.
     *
     * @param int|array $id_or_ids Inbound id or list of ids.
     * @return Result|Result[]
     */
    public function delete_all_clients(int|array $id_or_ids): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($id_or_ids as $id) {
                $requests[] = Request::post("$id/delAllClients");
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::post("$id_or_ids/delAllClients"));
    }

    /**
     * Reset the traffic counters of every inbound.
     *
     * @return Result
     */
    public function reset_all_traffics(): Result
    {
        return $this->_request(Request::post('resetAllTraffics'));
    }

    /**
     * Import one or many inbounds from exported JSON.
     *
     * @param string|array $exported_inbound_inbounds JSON string or list of JSON strings.
     * @return Result|Result[]
     */
    public function import(string|array $exported_inbound_inbounds): Result|array
    {
        if (is_array($exported_inbound_inbounds)) {
            $requests = [];
            foreach ($exported_inbound_inbounds as $exported) {
                $requests[] = Request::post('import', ['data' => $exported]);
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::post('import', ['data' => $exported_inbound_inbounds]));
    }

    /**
     * Read or replace the fallback rules of a VLESS/Trojan master inbound.
     *
     * @param int|array $id_or_ids Master inbound id or list of ids.
     * @param array|null $replace Full replacement list; omit to read.
     * @return Result|Result[]
     */
    public function fallbacks(int|array $id_or_ids, ?array $replace = null): Result|array
    {
        if (is_array($id_or_ids)) {
            $requests = [];
            foreach ($id_or_ids as $key => $id) {
                $requests[] = is_null($replace)
                    ? Request::get("$id/fallbacks")
                    : Request::post("$id/fallbacks", ['fallbacks' => $replace[$key] ?? []]);
            }
            return $this->_requests($requests);
        }
        return $this->_request(
            is_null($replace)
                ? Request::get("$id_or_ids/fallbacks")
                : Request::post("$id_or_ids/fallbacks", ['fallbacks' => $replace])
        );
    }

    /**
     * Receive a master panel's aggregated per-client usage.
     *
     * @param string $master_guid Stable GUID of the pushing master panel.
     * @param array $traffics Client traffic rows (only email/up/down are read).
     * @return Result
     */
    public function push_client_traffics(string $master_guid, array $traffics): Result
    {
        return $this->_request(Request::post('pushClientTraffics', [
            'masterGuid' => $master_guid,
            'traffics' => $traffics,
        ]));
    }

    /**
     * Build the request body for an inbound creation/update call.
     *
     * @param InboundConfig $config Protocol configuration.
     * @param string $remark Remark.
     * @param string $listen Listen address.
     * @param int|null $port Listen port.
     * @param int $expiry_time Expiry in Unix seconds.
     * @param int $total Total traffic limit in bytes.
     * @param bool $enable Enabled flag.
     * @param int $sub_sort_index Subscription sort index.
     * @param string $traffic_reset Traffic reset schedule.
     * @param int $traffic_reset_day Day of month for monthly resets.
     * @param string $share_addr Share address override.
     * @param string $share_addr_strategy Share address strategy.
     * @param bool $exclude_from_sub Omit from subscriptions.
     * @param int|null $node_id Hosting node id.
     * @return array
     */
    private function payload(
        InboundConfig $config,
        string $remark,
        string $listen = '',
        ?int $port = null,
        int $expiry_time = 0,
        int $total = 0,
        bool $enable = true,
        int $sub_sort_index = 0,
        string $traffic_reset = 'never',
        int $traffic_reset_day = 1,
        string $share_addr = '',
        string $share_addr_strategy = 'node',
        bool $exclude_from_sub = false,
        ?int $node_id = null
    ): array
    {
        $body = [
            'enable' => $enable,
            'remark' => $remark,
            'listen' => $listen,
            'port' => $port ?? random_int(1024, 65535),
            'protocol' => $config->protocol(),
            'expiryTime' => $expiry_time,
            'total' => $total,
            'up' => 0,
            'down' => 0,
            'subSortIndex' => $sub_sort_index,
            'trafficReset' => $traffic_reset,
            'trafficResetDay' => $traffic_reset_day,
            'shareAddr' => $share_addr,
            'shareAddrStrategy' => $share_addr_strategy,
            'excludeFromSub' => $exclude_from_sub,
        ];

        if (!empty($settings = $config->settings_payload())) {
            $body['settings'] = $settings;
        }
        if (!empty($stream = $config->stream_settings_payload())) {
            $body['streamSettings'] = $stream;
        }
        if (!empty($sniffing = $config->sniffing_payload())) {
            $body['sniffing'] = $sniffing;
        }
        if (!is_null($node_id)) {
            $body['nodeId'] = $node_id;
        }

        return $body;
    }

    /**
     * Rebuild a typed configuration object from an inbound payload.
     *
     * Accepts the array returned by the API, its `obj`, or the raw JSON string.
     *
     * @param array|string|object $inbound Inbound payload.
     * @return Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Tunnel|WireGuard|Hysteria|Mtproto|MixedInbound|AmneziaWG|false
     */
    public static function read(
        array|string|object $inbound
    ): Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Tunnel|WireGuard|Hysteria|Mtproto|MixedInbound|AmneziaWG|false
    {
        $data = match (true) {
            is_array($inbound) => $inbound,
            is_object($inbound) => (array)$inbound,
            default => (array)(json_decode($inbound, true) ?: []),
        };
        if ($data === []) {
            return false;
        }

        $protocol = (string)($data['protocol'] ?? '');
        $listen = (string)($data['listen'] ?? '');
        $port = (int)($data['port'] ?? 0);
        $settings = self::json($data['settings'] ?? []);
        $stream = self::json($data['streamSettings'] ?? []);
        $sniffing = self::json($data['sniffing'] ?? []);

        return match ($protocol) {
            'vmess' => new Vmess($listen, $port, $settings, $stream, $sniffing),
            'vless' => new Vless($listen, $port, $settings, $stream, $sniffing),
            'trojan' => new Trojan($listen, $port, $settings, $stream, $sniffing),
            'shadowsocks' => new Shadowsocks($listen, $port, $settings, $stream, $sniffing),
            'socks' => new Socks($listen, $port, $settings),
            'http' => new Http($listen, $port, $settings),
            'dokodemo-door', 'tunnel' => new Tunnel($listen, $port, $settings),
            'wireguard' => new WireGuard($listen, $port, $settings, $stream, $sniffing),
            'hysteria' => new Hysteria($listen, $port, $settings, $stream, $sniffing),
            'mtproto' => new Mtproto($listen, $port, $settings, $stream, $sniffing),
            'mixed' => new MixedInbound($listen, $port, $settings, $stream, $sniffing),
            'amneziawg' => new AmneziaWG($listen, $port, $settings, $stream, $sniffing),
            default => false,
        };
    }

    /**
     * Convert a supported inbound configuration into an outbound configuration.
     *
     * @param string $address Remote server address.
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|MixedInbound $inbound_config Source inbound.
     * @param int|null $port Remote port, defaults to the inbound port.
     * @return OutboundTrojan|OutboundShadowsocks|OutboundVmess|OutboundVless|OutboundSocks|OutboundHttp|false
     */
    public static function to_outbound(
        string $address,
        Vmess|Vless|Trojan|Shadowsocks|Socks|Http|MixedInbound $inbound_config,
        ?int $port = null
    ): OutboundTrojan|OutboundShadowsocks|OutboundVmess|OutboundVless|OutboundSocks|OutboundHttp|false
    {
        $port ??= $inbound_config->port;
        $config = null;
        $config_settings = null;
        $stream = null;

        switch ($inbound_config->protocol) {
            case 'vmess':
                $stream = $inbound_config->stream_settings;
                $config = new OutboundVmess();
                $config_settings = new \XUI\Outbounds\Protocols\Vmess\Settings($address, $port);
                $config_settings->add_user((string)$inbound_config->settings->clients[0]['id']);
                break;
            case 'vless':
                $stream = $inbound_config->stream_settings;
                $config = new OutboundVless();
                $config_settings = new \XUI\Outbounds\Protocols\Vless\Settings($address, $port);
                $client = $inbound_config->settings->clients[0];
                $config_settings->add_user(
                    (string)$client['id'],
                    (string)($client['encryption'] ?? 'none'),
                    (string)($client['flow'] ?? '')
                );
                break;
            case 'trojan':
                $stream = $inbound_config->stream_settings;
                $config = new OutboundTrojan();
                $client = $inbound_config->settings->clients[0];
                $config_settings = new \XUI\Outbounds\Protocols\Trojan\Settings(
                    $address,
                    $port,
                    (string)$client['password'],
                    $client['email'] ?? null
                );
                break;
            case 'shadowsocks':
                $stream = $inbound_config->stream_settings;
                $config = new OutboundShadowsocks();
                $client = $inbound_config->settings->clients[0] ?? [];
                $config_settings = new \XUI\Outbounds\Protocols\Shadowsocks\Settings(
                    $address,
                    $port,
                    (string)($client['password'] ?? $inbound_config->settings->password),
                    (string)($client['method'] ?? $inbound_config->settings->method),
                    (string)($client['email'] ?? '')
                );
                break;
            case 'socks':
            case 'mixed':
                $config = new OutboundSocks();
                $config_settings = new \XUI\Outbounds\Protocols\Socks\Settings($address, $port);
                $account = $inbound_config->settings->accounts[0] ?? null;
                if ($account !== null) {
                    $config_settings->add_user((string)$account['username'], (string)$account['password']);
                }
                break;
            case 'http':
                $config = new OutboundHttp();
                $config_settings = new \XUI\Outbounds\Protocols\Http\Settings($address, $port);
                $account = $inbound_config->settings->accounts[0] ?? null;
                if ($account !== null) {
                    $config_settings->add_user((string)$account['username'], (string)$account['password']);
                }
                break;
        }

        if (is_null($config)) {
            return false;
        }
        if (!is_null($config_settings)) {
            $config->settings = $config_settings;
        }
        if (!is_null($stream)) {
            $config->stream_settings = self::stream_to_outbound($stream);
        }
        return $config;
    }

    /**
     * Convert inbound stream settings into outbound stream settings.
     *
     * @param InboundStreamSettings $stream Inbound stream settings.
     * @return OutboundStreamSettings
     */
    private static function stream_to_outbound(InboundStreamSettings $stream): OutboundStreamSettings
    {
        $out = new OutboundStreamSettings($stream->network, $stream->security);

        switch ($stream->network) {
            case InboundStreamSettings::NETWORK_TCP:
                $header = $stream->tcp_settings['header'] ?? [];
                $header_type = (string)($header['type'] ?? 'none');
                $request = $header['request'] ?? [];
                $out->tcp_settings(
                    $header_type,
                    $header_type === 'http' ? [
                        'version' => (string)($request['version'] ?? '1.1'),
                        'method' => (string)($request['method'] ?? 'GET'),
                        'path' => (array)($request['path'] ?? ['/']),
                        'headers' => (array)($request['headers'] ?? []),
                    ] : []
                );
                break;
            case InboundStreamSettings::NETWORK_WS:
                $out->ws_settings(
                    (bool)($stream->ws_settings['acceptProxyProtocol'] ?? false),
                    (string)($stream->ws_settings['path'] ?? '/'),
                    (string)($stream->ws_settings['host'] ?? ''),
                    (array)($stream->ws_settings['headers'] ?? [])
                );
                break;
            case InboundStreamSettings::NETWORK_KCP:
                $kcp = $stream->kcp_settings;
                $out->kcp_settings(
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
            case InboundStreamSettings::NETWORK_HTTP:
                $http = $stream->http_settings;
                $out->http_settings(
                    (string)($http['method'] ?? 'PUT'),
                    (string)($http['path'] ?? '/'),
                    (array)($http['host'] ?? []),
                    isset($http['read_idle_timeout']) ? (int)$http['read_idle_timeout'] : null,
                    (int)($http['health_check_timeout'] ?? 15)
                );
                break;
            case InboundStreamSettings::NETWORK_GRPC:
                $grpc = $stream->grpc_settings;
                $out->grpc_settings(
                    isset($grpc['serviceName']) ? (string)$grpc['serviceName'] : null,
                    (bool)($grpc['multiMode'] ?? false),
                    (int)($grpc['idle_timeout'] ?? 60),
                    (int)($grpc['health_check_timeout'] ?? 20),
                    (bool)($grpc['permit_without_stream'] ?? false),
                    (int)($grpc['initial_windows_size'] ?? 0)
                );
                break;
            case InboundStreamSettings::NETWORK_QUIC:
                $quic = $stream->quic_settings;
                $out->quic_settings(
                    (string)($quic['security'] ?? 'none'),
                    isset($quic['key']) ? (string)$quic['key'] : null,
                    (string)($quic['header']['type'] ?? 'none')
                );
                break;
            case InboundStreamSettings::NETWORK_HTTPUPGRADE:
                $upgrade = $stream->httpupgrade_settings;
                $out->httpupgrade_settings(
                    (bool)($upgrade['acceptProxyProtocol'] ?? false),
                    (string)($upgrade['host'] ?? ''),
                    (string)($upgrade['path'] ?? '/'),
                    (array)($upgrade['headers'] ?? [])
                );
                break;
            case InboundStreamSettings::NETWORK_XHTTP:
                $xhttp = $stream->xhttp_settings;
                $out->xhttp_settings(
                    (string)($xhttp['path'] ?? '/'),
                    (string)($xhttp['host'] ?? ''),
                    (string)($xhttp['mode'] ?? 'auto'),
                    (array)($xhttp['headers'] ?? []),
                    (array)($xhttp['extra'] ?? [])
                );
                break;
            case InboundStreamSettings::NETWORK_DOMAINSOCKET:
                $ds = $stream->ds_settings;
                $out->ds_settings(
                    (string)($ds['path'] ?? '/'),
                    (bool)($ds['abstract'] ?? false),
                    (bool)($ds['padding'] ?? false)
                );
                break;
        }

        if ($stream->security === InboundStreamSettings::SECURITY_TLS) {
            $tls = $stream->tls_settings;
            $out->tls_settings(
                (string)($tls['serverName'] ?? ''),
                (bool)($tls['allowInsecure'] ?? false),
                (array)($tls['alpn'] ?? ['h2', 'http/1.1']),
                (string)($tls['fingerprint'] ?? 'chrome')
            );
        } elseif ($stream->security === InboundStreamSettings::SECURITY_REALITY) {
            $reality = $stream->reality_settings;
            $out->reality_settings(
                (bool)($reality['show'] ?? false),
                (string)($reality['settings']['fingerprint'] ?? $reality['fingerprint'] ?? 'chrome'),
                (string)($reality['serverNames'][0] ?? ''),
                (string)($reality['settings']['publicKey'] ?? $reality['publicKey'] ?? ''),
                (string)($reality['shortIds'][0] ?? ''),
                (string)($reality['settings']['spiderX'] ?? '')
            );
        }

        if (!empty($stream->sockopt)) {
            $sockopt = $stream->sockopt;
            $out->sockopt(
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

        return $out;
    }

    /**
     * Encode a settings member to the JSON string expected by configuration classes.
     *
     * @param mixed $value Raw value (array, object, JSON string).
     * @return string
     */
    private static function json(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        return json_encode($value) ?: '{}';
    }

    /**
     * Send one inbound request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->inbounds($request);
        return $result;
    }

    /**
     * Send many inbound requests in parallel.
     *
     * @param array $requests Prepared requests.
     * @return Result[]
     */
    private function _requests(array $requests): array
    {
        /** @var Result[] $results */
        $results = $this->request->inbounds(requests: $requests);
        return $results;
    }
}
