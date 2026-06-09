<?php

namespace XUI\Outbounds\Protocols\Socks;

use JSON\json;
use stdClass;

class Settings

{
    public string $address;
    public int $port;
    public array $users;

    public function __construct(string $address, int $port, array $users = [])
    {
        $this->address = $address;
        $this->port = $port;
        $this->users = $users;
    }

    public function add_user(string $username = null, string $password = null): string
    {
        $username = (is_null($username)) ? self::random(6) : $username;
        $password = (is_null($password)) ? self::random(12) : $password;
        $this->users[] = [
            'username' => $username,
            'password' => $password,
        ];
        return $username;
    }

    public function update_user(string $user_username, string $username = null, string $password = null): bool
    {
        $updated = false;
        foreach ($this->users as $key => $user):
            if ($user['username'] == $user_username):
                if (!is_null($username)) $user['username'] = $username;
                if (!is_null($password)) $user['password'] = $password;
                $this->users[$key] = $user;
                $updated = true;
                break;
            endif;
        endforeach;
        return $updated;
    }

    public function get_user(string $user_username): array|false
    {
        $return = false;
        foreach ($this->users as $key => $user):
            if ($user['username'] == $user_username):
                $return = $this->users[$key];
                break;
            endif;
        endforeach;
        return $return;
    }

    public function has_user(string $user_username): bool
    {
        $return = false;
        foreach ($this->users as $user):
            if ($user['username'] == $user_username):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    public function remove_user(string $user_username): bool
    {
        $removed = false;
        foreach ($this->users as $key => $user):
            if ($user['username'] == $user_username):
                unset($this->users[$key]);
                $removed = true;
                break;
            endif;
        endforeach;
        $this->users = array_values($this->users);
        return $removed;
    }

    /**
     * Returns fully structured settings for xray json config
     * @return array
     */
    public function settings(): array
    {
        return [
            'servers' => [
                'address' => $this->address,
                'port' => $this->port,
                'users' => $this->users
            ]
        ];
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