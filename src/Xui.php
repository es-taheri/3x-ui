<?php

namespace XUI;

use JSON\json;
use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;

class Xui
{
    public Inbounds $inbounds;
    public Outbounds $outbounds;
    public Xray $xray;
    public Server $server;
    public Settings $settings;
    public Clients $clients;

    public function __construct(private readonly Request $request)
    {
        $this->inbounds = new Inbounds($this->request);
        $this->outbounds = new Outbounds($this->request);
        $this->xray = new Xray($this->request);
        $this->server = new Server($this->request);
        $this->settings = new Settings($this->request);
        $this->clients = new Clients($this->request);
    }

    /**
     * Login to xui panel using username & password\
     * Uses cookie session if logged in before.
     * @param string $username
     * @param string $password
     * @param int|null $two_factor
     * @return true|Result
     */
    public function login(string $username, string $password, int $two_factor = null): true|Result
    {
        if ($this->is_login()) {
            return true;
        } else {
            return $this->request->authentication(Request::post('login', form_params: [
                'username' => $username,
                'password' => $password,
                'twoFactorCode' => $two_factor
            ]));
        }
    }

    /**
     * Logout from xui panel
     * @return Result
     */
    public function logout(): Result
    {
        return $this->request->authentication(Request::post('logout'));
    }

    /**
     * Mint a CSRF token for the current session. The SPA replays it in the X-CSRF-Token header on unsafe requests.
     * Bearer-token callers can skip this — the middleware short-circuits CSRF for authenticated API requests.
     * @return Result
     */
    public function csrf_token(): Result
    {
        return $this->request->authentication(Request::get('csrf-token'));
    }

    /**
     * Returns whether 2FA is enabled on the panel — used by the login page to decide whether to show the OTP field.
     * @return Result
     */
    public function get_two_factor_enabled(): Result
    {
        return $this->request->authentication(Request::post('getTwoFactorEnabled'));
    }

    private function is_login(): bool
    {
        $result = $this->request->server(Request::get('getNewUUID'));
        return $result->ok && $result->success;
    }
}