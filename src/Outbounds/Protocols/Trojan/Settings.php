<?php

namespace XUI\Outbounds\Protocols\Trojan;

/**
 * `settings` object of a Trojan outbound.
 *
 * @link https://xtls.github.io/en/config/outbounds/trojan.html
 */
class Settings
{
    /** Remote server address. */
    public string $address;

    /** Remote server port. */
    public int $port;

    /** Trojan password. */
    public string $password;

    /** Optional email used for logging. */
    public ?string $email;

    /**
     * @param string $address Remote server address.
     * @param int $port Remote server port.
     * @param string $password Trojan password.
     * @param string|null $email Optional email.
     */
    public function __construct(string $address, int $port, string $password, ?string $email = null)
    {
        $this->address = $address;
        $this->port = $port;
        $this->password = $password;
        $this->email = $email;
    }

    /**
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        $server = [
            'address' => $this->address,
            'port' => $this->port,
            'password' => $this->password,
        ];
        if (!is_null($this->email)) {
            $server['email'] = $this->email;
        }
        return ['servers' => [$server]];
    }
}
