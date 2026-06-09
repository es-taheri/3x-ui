<?php

namespace XUI\Inbounds\Protocols\Shadowsocks;

class Settings
{
    public array $clients;
    public string $password;
    public string $method;
    public string $email;
    public string $network;
    public const NETWORK_TCP_UDP = 'tcp,udp';
    public const NETWORK_TCP = 'tcp';
    public const NETWORK_UDP = 'udp';
    public const METHOD_2022_blake3_aes_128_gcm = '2022-blake3-aes-128-gcm';
    public const METHOD_2022_blake3_aes_256_gcm = '2022-blake3-aes-256-gcm';
    public const METHOD_2022_blake3_chacha20_poly1305 = '2022-blake3-chacha20-poly1305';
    public const METHOD_aes_256_gcm = 'aes-256-gcm';
    public const METHOD_aes_128_gcm = 'aes-128-gcm';
    public const METHOD_chacha20_poly1305 = 'chacha20-poly1305';
    public const METHOD_chacha20_ietf_poly1305 = 'chacha20-ietf-poly1305';
    public const METHOD_xchacha20_poly1305 = 'xchacha20-poly1305';
    public const METHOD_xchacha20_ietf_poly1305 = 'xchacha20-ietf-poly1305';
    public const METHOD_none = 'none';
    public const METHOD_plain = 'plain';

    public function __construct(array $clients = [], string $password = null, string $method = self::METHOD_aes_128_gcm,string $email=null, string $network = self::NETWORK_TCP_UDP)
    {
        $password = (is_null($password)) ? self::random() : $password;
        $this->clients = $clients;
        $this->password = $password;
        $this->method = $method;
        $this->email = (is_null($email)) ? self::random(8) : $email;
        $this->network = $network;
    }

    public function add_client(
        bool $enable = true, string $method = null, string $password = null, string $email = null, int $total_traffic = 0, int $expiry_time = 0,
        int  $limit_ip = 0, int $tgid = null, string $subid = null, int $reset = 0
    ): string
    {
        $method = (is_null($method)) ? self::METHOD_aes_128_gcm : $method;
        $password = (is_null($password)) ? self::random(12) : $password;
        $email = (is_null($email)) ? self::random(8) : $email;
        $subid = (is_null($subid)) ? self::random(20) : $subid;
        $this->clients[] = [
            'method' => $method,
            'email' => $email,
            'password' => $password,
            'limitIp' => $limit_ip,
            'totalGB' => $total_traffic,
            'expiryTime' => $expiry_time * 1000,
            'enable' => $enable,
            'tgId' => $tgid,
            'subId' => $subid,
            'reset' => $reset
        ];
        return $email;
    }

    public function update_client(
        string $client_email, bool $enable = null, string $method = null, string $password = null, string $email = null, int $total_traffic = null,
        int    $expiry_time = null, int $limit_ip = null, int $tgid = null, string $subid = null, int $reset = null
    ): bool
    {
        $updated = false;
        foreach ($this->clients as $key => $client):
            if ($client['email'] == $client_email):
                if (!is_null($method)) $client['method'] = $method;
                if (!is_null($password)) $client['password'] = $password;
                if (!is_null($email)) $client['email'] = $email;
                if (!is_null($enable)) $client['enable'] = $enable;
                if (!is_null($total_traffic)) $client['totalGB'] = $total_traffic;
                if (!is_null($expiry_time)) $client['expiryTime'] = $expiry_time * 1000;
                if (!is_null($limit_ip)) $client['limitIp'] = $limit_ip;
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
            'password' => $this->password,
            'method' => $this->method,
            'network' => $this->network,
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