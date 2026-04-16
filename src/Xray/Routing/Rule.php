<?php

namespace XUI\Xray\Routing;

use JSON\json;

/**
 * @method array|string|bool  inbound_tag(array|string $value = null) An array/string where each item represents an identifier.
 * @method string|bool        outbound_tag(array|string $value = null) Corresponds to the identifier of an outbound.
 * @method string|bool        balancer_tag(string $value = null) Corresponds to the identifier of a balancer.
 * @method array|string|bool  user(array|string $value = null) An array where each item represents an email address.
 * @method string|bool        network(string $value = null) This can be "tcp", "udp", or "tcp,udp".
 * @method array|string|bool  protocol(array|string $value = null) An array where each item represents a protocol. ["http" | "tls" | "bittorrent"]
 * @method string|bool        domain_matcher(string $value = null) The domain matching algorithm used varies depending on the settings.
 * @method array|string|bool  domain(array|string $value = null) The domain matching algorithm used varies depending on the settings.
 * @method array|string|bool  ip(array|string $value = null) An array where each item represents an IP range.
 * @method array|string|bool  port(array|string $value = null) The target port range
 * @method array|string|bool  source(array|string $value = null) An array where each item represents an IP range in the format of IP, CIDR, GeoIP, or loading IP from a file.
 * @method array|string|bool  source_port(array|string $value = null) The source port
 * @method string|bool        attrs(string $value = null) A json object with string keys and values, used to detect the HTTP headers of the traffic.
 * @method string|bool        type(string $value = null) Currently, only the option "field" is supported.
 */
class Rule
{
    private array|string $inbound_tag;
    private string $outbound_tag;
    private string $balancer_tag;
    private array|string $user;
    private string $network;
    private array|string $protocol;
    private string $domain_matcher;
    private array|string $domain;
    private array|string $ip;
    private array|string $port;
    private array|string $source;
    private array|string $source_port;
    private string $attrs;
    private string $type;

    public function __construct(array|string $inbound_tag, string $outbound_tag)
    {
        $this->inbound_tag = (is_string($inbound_tag)) ? [$inbound_tag] : $inbound_tag;
        $this->outbound_tag = $outbound_tag;
    }

    public function __call($name, $args)
    {
        return empty($args) ? $this->$name : !!($this->$name = $args[0]);
    }

    public function rule(): array
    {
        $rule = [];
        $inbound_tag = $this->inbound_tag;
        $rule['inboundTag'] = (is_string($inbound_tag)) ? [$inbound_tag] : $inbound_tag;
        $rule['outboundTag'] = $this->outbound_tag;
        if (isset($this->balancer_tag)) $rule['balancerTag'] = $this->balancer_tag;
        if (isset($this->user)) $rule['user'] = is_string($this->user) ? [$this->user] : $this->user;
        if (isset($this->network)) $rule['network'] = $this->network;
        if (isset($this->protocol)) $rule['protocol'] = is_string($this->protocol) ? [$this->protocol] : $this->protocol;
        if (isset($this->domain_matcher)) $rule['domainMatcher'] = $this->domain_matcher;
        if (isset($this->domain)) $rule['domain'] = is_string($this->domain) ? [$this->domain] : $this->domain;
        if (isset($this->ip)) $rule['ip'] = is_string($this->ip) ? [$this->ip] : $this->ip;
        if (isset($this->port)) $rule['port'] = is_array($this->port) ? implode(',', $this->port) : $this->port;
        if (isset($this->source)) $rule['source'] = is_string($this->source) ? [$this->source] : $this->source;
        if (isset($this->source_port)) $rule['sourcePort'] = is_array($this->source_port) ? implode(',', $this->source_port) : $this->source_port;
        if (isset($this->attrs)) $rule['attrs'] = $this->attrs;
        if (isset($this->type)) $rule['type'] = $this->type;
        return $rule;
    }

    /**
     * Read a routing rule and convert it to Rule object oriented\
     * Only rule accepted!
     * @param array|object|string $rule
     * @return false|Rule
     */
    public static function read(array|object|string $rule): false|Rule
    {
        $rule = json::to_array($rule);
        if (isset($rule['inboundTag'], $rule['outboundTag']))
            return new self($rule['inboundTag'], $rule['outboundTag']);
        else
            return false;
    }
}