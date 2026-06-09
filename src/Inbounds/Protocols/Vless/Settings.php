<?php

namespace XUI\Inbounds\Protocols\Vless;

class Settings
{
    public array $clients;
    public array $fallbacks;
    public string $decryption;

    public function __construct(array $clients = [], array $fallbacks = [], string $decryption = 'none')
    {
        $this->clients = $clients;
        $this->fallbacks = $fallbacks;
        $this->decryption = $decryption;
    }

    public function add_client(
        bool $enable = true, string $uuid = null, string $email = null, int $total_traffic = 0, int $expiry_time = 0,
        int  $limit_ip = 0, string $flow = null, int $tgid = null, string $subid = null, int $reset = 0
    ): string
    {
        $uuid = (is_null($uuid)) ? self::uuid() : $uuid;
        $email = (is_null($email)) ? self::random(8) : $email;
        $subid = (is_null($subid)) ? self::random(20) : $subid;
        $this->clients[] = [
            'id' => $uuid,
            'email' => $email,
            'flow' => $flow,
            'limitIp' => $limit_ip,
            'totalGB' => $total_traffic,
            'expiryTime' => $expiry_time * 1000,
            'enable' => $enable,
            'tgId' => $tgid,
            'subId' => $subid,
            'reset' => $reset
        ];
        return $uuid;
    }

    public function update_client(
        string $client_email, bool $enable = null, string $uuid = null, string $email = null, int $total_traffic = null,
        int    $expiry_time = null, string $flow = null, int $limit_ip = null, int $tgid = null, string $subid = null, int $reset = null
    ): bool
    {
        $updated = false;
        foreach ($this->clients as $key => $client):
            if ($client['email'] == $client_email):
                if (!is_null($uuid)) $client['id'] = $uuid;
                if (!is_null($email)) $client['email'] = $email;
                if (!is_null($enable)) $client['enable'] = $enable;
                if (!is_null($total_traffic)) $client['totalGB'] = $total_traffic;
                if (!is_null($expiry_time)) $client['expiryTime'] = $expiry_time * 1000;
                if (!is_null($limit_ip)) $client['limitIp'] = $limit_ip;
                if (!is_null($flow)) $client['flow'] = $flow;
                if (!is_null($tgid)) $client['tgId'] = $tgid;
                if (!is_null($subid)) $client['subId'] = $subid;
                if (!is_null($reset)) $client['reset'] = $reset;
                $this->clients[$key] = $client;
                $updated = true;
                break;
            endif;
        endforeach;
        return $updated;
    }

    public function get_client(string $client_email): array|false
    {
        $return = false;
        foreach ($this->clients as $key => $client):
            if ($client['email'] == $client_email):
                $return = $this->clients[$key];
                break;
            endif;
        endforeach;
        return $return;
    }

    public function has_client(string $client_email): bool
    {
        $return = false;
        foreach ($this->clients as $client):
            if ($client['email'] == $client_email):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    public function remove_client(string $client_email): bool
    {
        $removed = false;
        foreach ($this->clients as $key => $client):
            if ($client['email'] == $client_email):
                unset($this->clients[$key]);
                $removed = true;
                break;
            endif;
        endforeach;
        $this->clients = array_values($this->clients);
        return $removed;
    }

    /**
     * Returns fully structured settings for xray json config
     * @return array
     */
    public function settings(): array
    {
        return [
            'clients' => $this->clients,
            'fallbacks' => $this->fallbacks,
            'decryption' => $this->decryption
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