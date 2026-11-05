<?php

namespace XUI\Inbounds\Protocols\AmneziaWG;

/**
 * `server` block of an AmneziaWG inbound.
 *
 * It holds the interface-level configuration shared by every peer: key pair,
 * tunnel subnet, obfuscation parameters and the optional AmneziaWG 3.0/3.1
 * hardening fields.
 *
 * @link https://github.com/amnezia-vpn/amneziawg-go
 */
class ServerSettings
{
    /** WireGuard private key of the interface. */
    public string $private_key = '';

    /** WireGuard public key of the interface. */
    public string $public_key = '';

    /** Tunnel IPv4 network address. */
    public string $subnet_ip = '10.8.0.1';

    /** Tunnel IPv4 CIDR prefix length. */
    public int $subnet_cidr = 24;

    /** Tunnel IPv6 address, used when IPv6 is enabled. */
    public string $ipv6_subnet = '';

    /** Primary DNS handed to clients. */
    public string $primary_dns = '1.1.1.1';

    /** Secondary DNS handed to clients. */
    public string $secondary_dns = '1.0.0.1';

    /** Interface MTU. */
    public int $mtu = 1420;

    /** Run the inbound on the kernel module instead of the embedded stack. */
    public bool $kernel_mode = false;

    /** External interface used for NAT, empty for automatic detection. */
    public string $external_interface = '';

    /** Enable IPv6 address aliases. */
    public bool $ipv6_enabled = false;

    /** Interface used for IPv6 NAT. */
    public string $ipv6_external_interface = '';

    /** Legacy flag kept for backward compatibility. */
    public bool $route_through_xray = false;

    /** Junk packet count (`jc`). */
    public int $jc = 0;

    /** Minimum junk packet size (`jmin`). */
    public int $jmin = 0;

    /** Maximum junk packet size (`jmax`). */
    public int $jmax = 0;

    /** Init packet junk size (`s1`). */
    public int $s1 = 0;

    /** Response packet junk size (`s2`). */
    public int $s2 = 0;

    /** Cookie reply junk size (`s3`). */
    public int $s3 = 0;

    /** Transport packet junk size (`s4`). */
    public int $s4 = 0;

    /** First obfuscation header magic (`h1`). */
    public string $h1 = '';

    /** Second obfuscation header magic (`h2`). */
    public string $h2 = '';

    /** Third obfuscation header magic (`h3`). */
    public string $h3 = '';

    /** Fourth obfuscation header magic (`h4`). */
    public string $h4 = '';

    /** First special junk packet (`i1`). */
    public string $i1 = '';

    /** Second special junk packet (`i2`). */
    public string $i2 = '';

    /** Third special junk packet (`i3`). */
    public string $i3 = '';

    /** Fourth special junk packet (`i4`). */
    public string $i4 = '';

    /** Fifth special junk packet (`i5`). */
    public string $i5 = '';

    /** AmneziaWG 3.0 header protection key (base64, 32 bytes). */
    public string $header_protection_key = '';

    /** AmneziaWG 3.0 content padding addition range. */
    public string $content_padding_addition = '';

    /** AmneziaWG 3.1 random trailers. */
    public bool $random_trailers = false;

    /** AmneziaWG 3.1 disable cookies. */
    public bool $disable_cookies = false;

    /** Rekey interval. */
    public string $rekey_after_time = '';

    /** Rekey timeout. */
    public string $rekey_timeout = '';

    /** Reject-after time. */
    public string $reject_after_time = '';

    /** Keepalive timeout. */
    public string $keepalive_timeout = '';

    /** Maximum handshake attempts. */
    public string $max_handshake_attempts = '';

    /**
     * Rebuild the block from a decoded array.
     *
     * @param array $data Decoded `server` object.
     * @return self
     */
    public static function from_array(array $data): self
    {
        $instance = new self();
        foreach ($data as $key => $value) {
            $property = strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', (string)$key));
            if (property_exists($instance, $property)) {
                $instance->{$property} = $value;
            }
        }
        return $instance;
    }

    /**
     * Render the `server` object.
     *
     * @return array
     */
    public function settings(): array
    {
        return [
            'privateKey' => $this->private_key,
            'publicKey' => $this->public_key,
            'subnetIp' => $this->subnet_ip,
            'subnetCidr' => $this->subnet_cidr,
            'ipv6Subnet' => $this->ipv6_subnet,
            'primaryDns' => $this->primary_dns,
            'secondaryDns' => $this->secondary_dns,
            'mtu' => $this->mtu,
            'kernelMode' => $this->kernel_mode,
            'externalInterface' => $this->external_interface,
            'ipv6Enabled' => $this->ipv6_enabled,
            'ipv6ExternalInterface' => $this->ipv6_external_interface,
            'routeThroughXray' => $this->route_through_xray,
            'jc' => $this->jc,
            'jmin' => $this->jmin,
            'jmax' => $this->jmax,
            's1' => $this->s1,
            's2' => $this->s2,
            's3' => $this->s3,
            's4' => $this->s4,
            'h1' => $this->h1,
            'h2' => $this->h2,
            'h3' => $this->h3,
            'h4' => $this->h4,
            'i1' => $this->i1,
            'i2' => $this->i2,
            'i3' => $this->i3,
            'i4' => $this->i4,
            'i5' => $this->i5,
            'headerProtectionKey' => $this->header_protection_key,
            'contentPaddingAddition' => $this->content_padding_addition,
            'randomTrailers' => $this->random_trailers,
            'disableCookies' => $this->disable_cookies,
            'rekeyAfterTime' => $this->rekey_after_time,
            'rekeyTimeout' => $this->rekey_timeout,
            'rejectAfterTime' => $this->reject_after_time,
            'keepaliveTimeout' => $this->keepalive_timeout,
            'maxHandshakeAttempts' => $this->max_handshake_attempts,
        ];
    }
}
