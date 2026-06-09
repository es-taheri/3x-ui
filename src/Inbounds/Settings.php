<?php

namespace XUI\Inbounds;

class Settings
{
    public array $settings;
    public string $protocol;

    public static function make(string $protocol = null, array $settings = []): Settings
    {
        return new self();
    }

    public function vless(array $clients = [], string $encryption = 'none', string $decryption = 'none', array $fallbacks = []): Settings
    {
        $this->protocol = 'vless';
        $this->settings = [
            'clients' => $clients,
            'encryption' => $encryption,
            'decryption' => $decryption,
            'fallbacks' => $fallbacks,
        ];
        return $this;
    }

    public function vmess(array $users = [], int $default_level = 0): Settings
    {
        $this->protocol = 'vmess';
        $this->settings = [
            'users' => $users,
            'default' => [
                'level' => $default_level
            ],
        ];
        return $this;
    }
}