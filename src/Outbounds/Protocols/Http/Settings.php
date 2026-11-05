<?php

namespace XUI\Outbounds\Protocols\Http;

use XUI\Helper\Statics;

/**
 * `settings` object of an HTTP proxy outbound.
 *
 * @link https://xtls.github.io/en/config/outbounds/http.html
 */
class Settings
{
    /** Remote server address. */
    public string $address;

    /** Remote server port. */
    public int $port;

    /** Users used by the outbound. */
    public array $users = [];

    /**
     * @param string $address Remote server address.
     * @param int $port Remote server port.
     * @param array $users Initial user list.
     */
    public function __construct(string $address, int $port, array $users = [])
    {
        $this->address = $address;
        $this->port = $port;
        $this->users = $users;
    }

    /**
     * Add a user.
     *
     * @param string|null $username Username, generated when omitted.
     * @param string|null $password Password, generated when omitted.
     * @return string The username.
     */
    public function add_user(?string $username = null, ?string $password = null): string
    {
        $username ??= Statics::random(6);
        $password ??= Statics::random(12);
        $this->users[] = ['username' => $username, 'password' => $password];
        return $username;
    }

    /**
     * Update a user in place.
     *
     * @param string $user_username Current username.
     * @param string|null $username New username.
     * @param string|null $password New password.
     * @return bool True when the user was found and updated.
     */
    public function update_user(string $user_username, ?string $username = null, ?string $password = null): bool
    {
        foreach ($this->users as $key => $user) {
            if ($user['username'] !== $user_username) {
                continue;
            }
            if (!is_null($username)) $user['username'] = $username;
            if (!is_null($password)) $user['password'] = $password;
            $this->users[$key] = $user;
            return true;
        }
        return false;
    }

    /**
     * Fetch one user.
     *
     * @param string $user_username Username.
     * @return array|false
     */
    public function get_user(string $user_username): array|false
    {
        foreach ($this->users as $user) {
            if ($user['username'] === $user_username) {
                return $user;
            }
        }
        return false;
    }

    /**
     * Check whether a user exists.
     *
     * @param string $user_username Username.
     * @return bool
     */
    public function has_user(string $user_username): bool
    {
        return $this->get_user($user_username) !== false;
    }

    /**
     * Remove one user.
     *
     * @param string $user_username Username.
     * @return bool True when a user was removed.
     */
    public function remove_user(string $user_username): bool
    {
        foreach ($this->users as $key => $user) {
            if ($user['username'] === $user_username) {
                unset($this->users[$key]);
                $this->users = array_values($this->users);
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
        return [
            'servers' => [[
                'address' => $this->address,
                'port' => $this->port,
                'users' => $this->users,
            ]],
        ];
    }
}
