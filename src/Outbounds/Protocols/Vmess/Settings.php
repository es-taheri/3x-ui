<?php

namespace XUI\Outbounds\Protocols\Vmess;

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

    public function add_user(
        string $uuid, string $security = 'auto'
    ): string
    {
        $this->users[] = [
            'id' => $uuid,
            'security' => $security
        ];
        return $uuid;
    }

    public function update_user(
        string $user_uuid, string $uuid = null, string $security = null
    ): bool
    {
        $updated = false;
        foreach ($this->users as $key => $user):
            if ($user['id'] == $user_uuid):
                if (!is_null($uuid)) $user['id'] = $uuid;
                if (!is_null($security)) $user['security'] = $security;
                $this->users[$key] = $user;
                $updated = true;
                break;
            endif;
        endforeach;
        return $updated;
    }

    public function get_user(string $user_uuid): array|false
    {
        $return = false;
        foreach ($this->users as $key => $user):
            if ($user['id'] == $user_uuid):
                $return = $this->users[$key];
                break;
            endif;
        endforeach;
        return $return;
    }
    public function has_user(string $user_uuid): bool
    {
        $return = false;
        foreach ($this->users as $user):
            if ($user['id'] == $user_uuid):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }
    public function remove_user(string $user_uuid): bool
    {
        $removed = false;
        foreach ($this->users as $key => $user):
            if ($user['id'] == $user_uuid):
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
            'vnext' => [
                [
                    'address' => $this->address,
                    'port' => $this->port,
                    'users' => $this->users,
                ]
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

    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}