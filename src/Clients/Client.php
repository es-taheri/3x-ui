<?php

namespace XUI\Clients;

use XUI\Helper\Statics;

/**
 * Typed representation of the client payload accepted by the clients API.
 *
 * Every property maps 1:1 to a field of the panel `Client` schema, so IDE
 * completion works while the object is serialised by {@see Client::to_array()}.
 *
 * ```php
 * $client = new Client('alice@example.com');
 * $client->total_gb = 50 * Statics::UNIT_GIGABYTE;
 * $client->expiry_time = Statics::days_from_now(30);
 * $xui->clients->create($client, [3, 5]);
 * ```
 */
class Client
{
    /** Traffic reset: never. */
    public const RESET_NEVER = 'never';

    /** Traffic reset: hourly. */
    public const RESET_HOURLY = 'hourly';

    /** Traffic reset: daily. */
    public const RESET_DAILY = 'daily';

    /** Traffic reset: weekly. */
    public const RESET_WEEKLY = 'weekly';

    /** Traffic reset: monthly. */
    public const RESET_MONTHLY = 'monthly';

    /** Unique client email. */
    public string $email;

    /** Subscription id shared by clients that subscribe together. */
    public ?string $sub_id = null;

    /** UUID used by VLESS/VMess and TUIC clients. */
    public ?string $uuid = null;

    /** Password used by Trojan, Shadowsocks and TUIC clients. */
    public ?string $password = null;

    /** Authentication secret used by Hysteria clients. */
    public ?string $auth = null;

    /** FakeTLS secret used by MTProto clients. */
    public ?string $secret = null;

    /** Telegram sponsor channel tag used by MTProto clients. */
    public ?string $ad_tag = null;

    /** XTLS flow, e.g. `xtls-rprx-vision`. */
    public ?string $flow = null;

    /** Traffic quota in bytes, 0 for unlimited. */
    public int $total_gb = 0;

    /** Expiry timestamp in Unix milliseconds, 0 for never. */
    public int $expiry_time = 0;

    /** Maximum concurrent IPs, 0 for unlimited. */
    public int $limit_ip = 0;

    /** Maximum registered devices, 0 for unlimited. */
    public int $limit_hwid = 0;

    /** Telegram user id for notifications. */
    public int $tg_id = 0;

    /** Free-form comment. */
    public ?string $comment = null;

    /** Whether the client is enabled. */
    public bool $enable = true;

    /** Logical group label. */
    public ?string $group = null;

    /** Traffic reset interval in days, 0 disables interval reset. */
    public int $reset = 0;

    /** Calendar renewal day 1-31, 0 disables monthly renewal. */
    public int $reset_day = 0;

    /** Maximum auto-renew count, 0 for unlimited. */
    public int $reset_max = 0;

    /** Calendar renewal weekday 1-7, 0 disables weekly renewal. */
    public int $reset_weekday = 0;

    /** VMess/Shadowsocks security method. */
    public ?string $security = null;

    /** WireGuard/AmneziaWG private key. */
    public ?string $private_key = null;

    /** WireGuard/AmneziaWG public key. */
    public ?string $public_key = null;

    /** WireGuard/AmneziaWG pre-shared key. */
    public ?string $pre_shared_key = null;

    /** WireGuard/AmneziaWG addresses routed to the client. */
    public array $allowed_ips = [];

    /** Per-inbound overrides of {@see Client::$allowed_ips}, keyed by inbound id. */
    public array $allowed_ips_by_inbound = [];

    /** WireGuard keep-alive interval, 0 disables it. */
    public int|string $keep_alive = 0;

    /** AmneziaWG per-client port-forwarding spec. */
    public ?string $forwarded_ports = null;

    /** Per-client traffic reset cycle. */
    public ?string $traffic_reset = null;

    /** Day of month for per-client monthly resets. */
    public ?int $traffic_reset_day = null;

    /** Host rule ids bound to the client. */
    public array $client_host_rule_ids = [];

    /** VLESS simple reverse proxy settings. */
    public ?array $reverse = null;

    /**
     * @param string $email Unique client email.
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * Set the expiry from a number of days from now.
     *
     * @param int $days Days from now, 0 for never.
     * @return self
     */
    public function expiry_in_days(int $days): self
    {
        $this->expiry_time = $days > 0 ? Statics::days_from_now($days) : 0;
        return $this;
    }

    /**
     * Set the traffic quota from a number of gigabytes.
     *
     * @param int|float $gigabytes Quota in GB, 0 for unlimited.
     * @return self
     */
    public function quota_gb(int|float $gigabytes): self
    {
        $this->total_gb = (int)round($gigabytes * Statics::UNIT_GIGABYTE);
        return $this;
    }

    /**
     * Render the `client` object expected by the API.
     *
     * Null values are omitted so the panel applies its own defaults.
     *
     * @return array
     */
    public function to_array(): array
    {
        $data = [
            'email' => $this->email,
            'subId' => $this->sub_id,
            'id' => $this->uuid,
            'password' => $this->password,
            'auth' => $this->auth,
            'secret' => $this->secret,
            'adTag' => $this->ad_tag,
            'flow' => $this->flow,
            'totalGB' => $this->total_gb,
            'expiryTime' => $this->expiry_time,
            'limitIp' => $this->limit_ip,
            'limitHwid' => $this->limit_hwid,
            'tgId' => $this->tg_id,
            'comment' => $this->comment,
            'enable' => $this->enable,
            'group' => $this->group,
            'reset' => $this->reset,
            'resetDay' => $this->reset_day,
            'resetMax' => $this->reset_max,
            'resetWeekday' => $this->reset_weekday,
            'security' => $this->security,
            'privateKey' => $this->private_key,
            'publicKey' => $this->public_key,
            'preSharedKey' => $this->pre_shared_key,
            'allowedIPs' => $this->allowed_ips,
            'allowedIPsByInbound' => $this->allowed_ips_by_inbound,
            'keepAlive' => $this->keep_alive,
            'forwardedPorts' => $this->forwarded_ports,
            'trafficReset' => $this->traffic_reset,
            'trafficResetDay' => $this->traffic_reset_day,
            'clientHostRuleIds' => $this->client_host_rule_ids,
            'reverse' => $this->reverse,
        ];

        return array_filter($data, static fn(mixed $value): bool => !is_null($value));
    }

    /**
     * Build a client from a decoded payload.
     *
     * @param array|object|string $client Client payload.
     * @return self
     */
    public static function read(array|object|string $client): self
    {
        $data = match (true) {
            is_array($client) => $client,
            is_object($client) => (array)$client,
            default => (array)(json_decode($client, true) ?: []),
        };

        $instance = new self((string)($data['email'] ?? ''));
        $instance->sub_id = $data['subId'] ?? null;
        $instance->uuid = $data['id'] ?? $data['uuid'] ?? null;
        $instance->password = $data['password'] ?? null;
        $instance->auth = $data['auth'] ?? null;
        $instance->secret = $data['secret'] ?? null;
        $instance->ad_tag = $data['adTag'] ?? null;
        $instance->flow = $data['flow'] ?? null;
        $instance->total_gb = (int)($data['totalGB'] ?? 0);
        $instance->expiry_time = (int)($data['expiryTime'] ?? 0);
        $instance->limit_ip = (int)($data['limitIp'] ?? 0);
        $instance->limit_hwid = (int)($data['limitHwid'] ?? 0);
        $instance->tg_id = (int)($data['tgId'] ?? 0);
        $instance->comment = $data['comment'] ?? null;
        $instance->enable = (bool)($data['enable'] ?? true);
        $instance->group = $data['group'] ?? null;
        $instance->reset = (int)($data['reset'] ?? 0);
        $instance->reset_day = (int)($data['resetDay'] ?? 0);
        $instance->reset_max = (int)($data['resetMax'] ?? 0);
        $instance->reset_weekday = (int)($data['resetWeekday'] ?? 0);
        $instance->security = $data['security'] ?? null;
        $instance->private_key = $data['privateKey'] ?? null;
        $instance->public_key = $data['publicKey'] ?? null;
        $instance->pre_shared_key = $data['preSharedKey'] ?? null;
        $instance->allowed_ips = (array)($data['allowedIPs'] ?? []);
        $instance->allowed_ips_by_inbound = (array)($data['allowedIPsByInbound'] ?? []);
        $instance->keep_alive = $data['keepAlive'] ?? 0;
        $instance->forwarded_ports = $data['forwardedPorts'] ?? null;
        $instance->traffic_reset = $data['trafficReset'] ?? null;
        $instance->traffic_reset_day = isset($data['trafficResetDay']) ? (int)$data['trafficResetDay'] : null;
        $instance->client_host_rule_ids = (array)($data['clientHostRuleIds'] ?? []);
        $instance->reverse = isset($data['reverse']) && is_array($data['reverse']) ? $data['reverse'] : null;

        return $instance;
    }
}
