<?php

namespace XUI\Inbounds\Protocols\Socks;

use XUI\Helper\Statics;

/**
 * `settings` object of a SOCKS inbound.
 *
 * @link https://xtls.github.io/en/config/inbounds/socks.html
 */
class Settings
{
    /** Authentication mode: no authentication. */
    public const AUTH_noauth = 'noauth';

    /** Authentication mode: username/password. */
    public const AUTH_password = 'password';

    /** Accounts allowed to connect. */
    public array $accounts;

    /** Whether UDP relay is enabled. */
    public bool $udp;

    /** Source IP used for outgoing UDP packets. */
    public string $ip;

    /**
     * @param array $accounts Initial account list.
     * @param bool $udp Enable UDP relay.
     * @param string $ip Source IP for UDP.
     */
    public function __construct(array $accounts = [], bool $udp = false, string $ip = '127.0.0.1')
    {
        $this->accounts = $accounts;
        $this->udp = $udp;
        $this->ip = $ip;
    }

    /**
     * Add an account.
     *
     * @param string|null $username Username, generated when omitted.
     * @param string|null $password Password, generated when omitted.
     * @return string The username.
     */
    public function add_account(?string $username = null, ?string $password = null): string
    {
        $username ??= Statics::random(6);
        $password ??= Statics::random(12);
        $this->accounts[] = ['username' => $username, 'password' => $password];
        return $username;
    }

    /**
     * Update an account in place.
     *
     * @param string $account_username Current username.
     * @param string|null $username New username.
     * @param string|null $password New password.
     * @return bool True when the account was found and updated.
     */
    public function update_account(string $account_username, ?string $username = null, ?string $password = null): bool
    {
        foreach ($this->accounts as $key => $account) {
            if ($account['username'] !== $account_username) {
                continue;
            }
            if (!is_null($username)) $account['username'] = $username;
            if (!is_null($password)) $account['password'] = $password;
            $this->accounts[$key] = $account;
            return true;
        }
        return false;
    }

    /**
     * Fetch one account.
     *
     * @param string $account_username Username.
     * @return array|false
     */
    public function get_account(string $account_username): array|false
    {
        foreach ($this->accounts as $account) {
            if ($account['username'] === $account_username) {
                return $account;
            }
        }
        return false;
    }

    /**
     * Check whether an account exists.
     *
     * @param string $account_username Username.
     * @return bool
     */
    public function has_account(string $account_username): bool
    {
        return $this->get_account($account_username) !== false;
    }

    /**
     * Remove one account.
     *
     * @param string $account_username Username.
     * @return bool True when an account was removed.
     */
    public function remove_account(string $account_username): bool
    {
        foreach ($this->accounts as $key => $account) {
            if ($account['username'] === $account_username) {
                unset($this->accounts[$key]);
                $this->accounts = array_values($this->accounts);
                return true;
            }
        }
        return false;
    }

    /**
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        if (empty($this->accounts)) {
            return [
                'auth' => self::AUTH_noauth,
                'udp' => $this->udp,
                'ip' => $this->ip,
            ];
        }
        return [
            'auth' => self::AUTH_password,
            'accounts' => $this->accounts,
            'udp' => $this->udp,
            'ip' => $this->ip,
        ];
    }
}
