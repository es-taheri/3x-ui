<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;

/**
 * Entry point of the library.
 *
 * ```php
 * $request = new Request(Request::config('panel.example.com', 2053, 'ctrladmin', true));
 * $xui = new Xui($request);
 * $xui->login('admin', 'secret');
 * $xui->inbounds->list();
 * ```
 *
 * Every panel area is exposed as a public property.
 *
 * @property-read Inbounds $inbounds Inbound management.
 * @property-read Outbounds $outbounds Typed outbound helpers.
 * @property-read Xray $xray Xray config and integrations.
 * @property-read Server $server Server status, logs and backups.
 * @property-read Settings $settings Panel settings and API tokens.
 * @property-read Clients $clients Client management.
 * @property-read Hosts $hosts Host override groups.
 * @property-read Nodes $nodes Remote node management.
 * @property-read SubBalancers $sub_balancers Subscription balancers.
 */
class Xui
{
    /** Inbound management. */
    public Inbounds $inbounds;

    /** Typed outbound helpers. */
    public Outbounds $outbounds;

    /** Xray config and integrations. */
    public Xray $xray;

    /** Server status, logs and backups. */
    public Server $server;

    /** Panel settings and API tokens. */
    public Settings $settings;

    /** Client management. */
    public Clients $clients;

    /** Host override groups. */
    public Hosts $hosts;

    /** Remote node management. */
    public Nodes $nodes;

    /** Subscription balancers. */
    public SubBalancers $sub_balancers;

    /**
     * @param Request $request Shared HTTP transport.
     */
    public function __construct(
        /** @var Request Shared HTTP transport. */
        private readonly Request $request
    )
    {
        $this->inbounds = new Inbounds($this->request);
        $this->outbounds = new Outbounds($this->request);
        $this->xray = new Xray($this->request);
        $this->server = new Server($this->request);
        $this->settings = new Settings($this->request);
        $this->clients = new Clients($this->request);
        $this->hosts = new Hosts($this->request);
        $this->nodes = new Nodes($this->request);
        $this->sub_balancers = new SubBalancers($this->request);
    }

    /**
     * Log in with a username and password.
     *
     * When a cookie session is already valid the call is a no-op. On success the
     * CSRF token used by cookie sessions is refreshed automatically.
     *
     * @param string $username Panel admin username.
     * @param string $password Panel admin password.
     * @param string|null $two_factor OTP code when 2FA is enabled.
     * @return true|Result True when already logged in, otherwise the login result.
     */
    public function login(string $username, string $password, ?string $two_factor = null): true|Result
    {
        if ($this->is_login()) {
            return true;
        }

        $body = [
            'username' => $username,
            'password' => $password,
        ];
        if (!is_null($two_factor)) {
            $body['twoFactorCode'] = $two_factor;
        }

        $result = $this->request->authentication(Request::post('login', $body));
        if ($result->ok && $result->success) {
            $this->request->refresh_csrf_token();
        }
        return $result;
    }

    /**
     * Clear the session cookie.
     *
     * @return Result
     */
    public function logout(): Result
    {
        return $this->request->authentication(Request::post('logout'));
    }

    /**
     * Mint a fresh CSRF token for the current session.
     *
     * Bearer-token callers do not need this.
     *
     * @return Result
     */
    public function csrf_token(): Result
    {
        return $this->request->authentication(Request::get('csrf-token'));
    }

    /**
     * Whether two-factor authentication is enabled on the panel.
     *
     * @return Result
     */
    public function get_two_factor_enabled(): Result
    {
        return $this->request->authentication(Request::post('getTwoFactorEnable'));
    }

    /**
     * Public sponsor placements shown on the login page and panel slots.
     *
     * @return Result
     */
    public function sponsors(): Result
    {
        return $this->request->authentication(Request::get('sponsors'));
    }

    /**
     * WebSocket URL of the panel event stream (`/ws`).
     *
     * The stream requires an authenticated session cookie and is consumed by a
     * WebSocket client; this library only exposes the URL and the event
     * constants in {@see \XUI\Handler\WebSocketEvent}.
     *
     * @return string
     */
    public function websocket_url(): string
    {
        return $this->request->websocket_url();
    }

    /**
     * Send a fresh database backup to every configured Telegram recipient.
     *
     * @return Result
     */
    public function send_backup_to_tg_bot(): Result
    {
        return $this->request->backup(Request::post('backuptotgbot'));
    }

    /**
     * Check whether the current cookie session is authenticated.
     *
     * @return bool
     */
    public function is_login(): bool
    {
        $result = $this->request->server(Request::get('getNewUUID'));
        return $result->ok && $result->success;
    }
}
