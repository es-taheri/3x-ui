<?php

namespace XUI\Xray\Routing;

use JSON\json;

/**
 * @method array|string|self  inbound_tag(array|string $value = null) An array/string where each item represents an identifier.
 * @method string|self        outbound_tag(array|string $value = null) Corresponds to the identifier of an outbound.
 * @method string|self        balancer_tag(string $value = null) Corresponds to the identifier of a balancer.
 * @method array|string|self  user(array|string $value = null) An array where each item represents an email address.
 * @method string|self        network(string $value = null) This can be "tcp", "udp", or "tcp,udp".
 * @method array|string|self  protocol(array|string $value = null) An array where each item represents a protocol. ["http" | "tls" | "bittorrent"]
 * @method string|self        domain_matcher(string $value = null) The domain matching algorithm used varies depending on the settings.
 * @method array|string|self  domain(array|string $value = null) The domain matching algorithm used varies depending on the settings.
 * @method array|string|self  ip(array|string $value = null) An array where each item represents an IP range.
 * @method array|string|self  port(array|string $value = null) The target port range
 * @method array|string|self  source(array|string $value = null) An array where each item represents an IP range in the format of IP, CIDR, GeoIP, or loading IP from a file.
 * @method array|string|self  source_port(array|string $value = null) The source port
 * @method string|self        attrs(string $value = null) A json object with string keys and values, used to detect the HTTP headers of the traffic.
 * @method string|self        type(string $value = null) Currently, only the option "field" is supported.
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

    public function __construct(array|string $inbound_tag, string $outbound_tag, array|string|object $extra = [])
    {
        $this->inbound_tag = (is_string($inbound_tag)) ? [$inbound_tag] : $inbound_tag;
        $this->outbound_tag = $outbound_tag;
        $extra = json::to_array($extra);
        if (isset($extra['balancerTag'])) $this->balancer_tag = $extra['balancerTag'];
        if (isset($extra['user'])) $this->user = $extra['user'];
        if (isset($extra['network'])) $this->network = $extra['network'];
        if (isset($extra['protocol'])) $this->protocol = $extra['protocol'];
        if (isset($extra['domainMatcher'])) $this->domain_matcher = $extra['domainMatcher'];
        if (isset($extra['domain'])) $this->domain = $extra['domain'];
        if (isset($extra['ip'])) $this->ip = $extra['ip'];
        if (isset($extra['port'])) $this->port = $extra['port'];
        if (isset($extra['source'])) $this->source = $extra['source'];
        if (isset($extra['sourcePort'])) $this->source_port = $extra['sourcePort'];
        if (isset($extra['attrs'])) $this->attrs = $extra['attrs'];
        if (isset($extra['type'])) $this->type = $extra['type'];
    }

    public function __call($name, $args)
    {
        if (empty($args))
            return $this->$name;
        else
            $this->$name = $args[0];
        return $this;
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
            return new self($rule['inboundTag'], $rule['outboundTag'], $rule);
        else
            return false;
    }
}