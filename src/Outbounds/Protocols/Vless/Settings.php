<?php

namespace XUI\Outbounds\Protocols\Vless;

use XUI\Helper\Statics;

/**
 * `settings` object of a VLESS outbound.
 *
 * @link https://xtls.github.io/en/config/outbounds/vless.html
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
     * @param string $uuid User UUID.
     * @param string $encryption Outbound encryption, always `none` for VLESS.
     * @param string $flow XTLS flow.
     * @return string The user UUID.
     */
    public function add_user(string $uuid, string $encryption = 'none', string $flow = ''): string
    {
        $this->users[] = [
            'id' => $uuid,
            'encryption' => $encryption,
            'flow' => $flow,
        ];
        return $uuid;
    }

    /**
     * Update a user in place.
     *
     * @param string $user_uuid Current UUID.
     * @param string|null $uuid New UUID.
     * @param string|null $encryption New encryption.
     * @param string|null $flow New flow.
     * @return bool True when the user was found and updated.
     */
    public function update_user(string $user_uuid, ?string $uuid = null, ?string $encryption = null, ?string $flow = null): bool
    {
        foreach ($this->users as $key => $user) {
            if ($user['id'] !== $user_uuid) {
                continue;
            }
            if (!is_null($uuid)) $user['id'] = $uuid;
            if (!is_null($encryption)) $user['encryption'] = $encryption;
            if (!is_null($flow)) $user['flow'] = $flow;
            $this->users[$key] = $user;
            return true;
        }
        return false;
    }

    /**
     * Fetch one user by UUID.
     *
     * @param string $user_uuid User UUID.
     * @return array|false
     */
    public function get_user(string $user_uuid): array|false
    {
        foreach ($this->users as $user) {
            if ($user['id'] === $user_uuid) {
                return $user;
            }
        }
        return false;
    }

    /**
     * Check whether a user UUID exists.
     *
     * @param string $user_uuid User UUID.
     * @return bool
     */
    public function has_user(string $user_uuid): bool
    {
        return $this->get_user($user_uuid) !== false;
    }

    /**
     * Remove one user by UUID.
     *
     * @param string $user_uuid User UUID.
     * @return bool True when a user was removed.
     */
    public function remove_user(string $user_uuid): bool
    {
        foreach ($this->users as $key => $user) {
            if ($user['id'] === $user_uuid) {
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
            'vnext' => [[
                'address' => $this->address,
                'port' => $this->port,
                'users' => $this->users,
            ]],
        ];
    }
}
