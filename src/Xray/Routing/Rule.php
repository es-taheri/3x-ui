<?php

namespace XUI\Xray\Routing;

/**
 * Fluent builder for one Xray routing rule.
 *
 * ```php
 * $rule = Routing::rule('in-443', 'proxy')
 *     ->domain(['geosite:google', 'domain:example.com'])
 *     ->network('tcp');
 * ```
 *
 * @method array|string|self inbound_tag(array|string|null $value = null) Inbound tag(s) the rule applies to.
 * @method string|self outbound_tag(string|null $value = null) Outbound tag selected by the rule.
 * @method string|self balancer_tag(string|null $value = null) Balancer tag selected by the rule.
 * @method array|string|self user(array|string|null $value = null) Emails the rule applies to.
 * @method string|self network(string|null $value = null) `tcp`, `udp` or `tcp,udp`.
 * @method array|string|self protocol(array|string|null $value = null) Sniffed protocols (`http`, `tls`, `bittorrent`).
 * @method string|self domain_matcher(string|null $value = null) Domain matcher.
 * @method array|string|self domain(array|string|null $value = null) Domain rules.
 * @method array|string|self ip(array|string|null $value = null) IP rules.
 * @method array|string|self port(array|string|null $value = null) Destination ports.
 * @method array|string|self source(array|string|null $value = null) Source IP rules.
 * @method array|string|self source_port(array|string|null $value = null) Source ports.
 * @method array|string|self attrs(array|string|null $value = null) // HTTP attribute match object.
 * @method string|self type(string|null $value = null) Rule type, only `field` is supported.
 */
class Rule
{
    /** Inbound tags the rule applies to. */
    private array|string $inbound_tag;

    /** Outbound tag selected by the rule. */
    private string $outbound_tag;

    /** Balancer tag selected by the rule. */
    private ?string $balancer_tag = null;

    /** Emails the rule applies to. */
    private array|string|null $user = null;

    /** Networks the rule applies to. */
    private ?string $network = null;

    /** Sniffed protocols the rule applies to. */
    private array|string|null $protocol = null;

    /** Domain matcher. */
    private ?string $domain_matcher = null;

    /** Domain rules. */
    private array|string|null $domain = null;

    /** IP rules. */
    private array|string|null $ip = null;

    /** Destination ports. */
    private array|string|null $port = null;

    /** Source IP rules. */
    private array|string|null $source = null;

    /** Source ports. */
    private array|string|null $source_port = null;

    /** HTTP attribute matches. */
    private array|string|null $attrs = null;

    /** Rule type. */
    private ?string $type = null;

    /**
     * @param array|string $inbound_tag Inbound tag or list of tags.
     * @param string $outbound_tag Outbound tag.
     * @param array|string|object $extra Additional rule fields.
     */
    public function __construct(array|string $inbound_tag, string $outbound_tag, array|string|object $extra = [])
    {
        $this->inbound_tag = $inbound_tag;
        $this->outbound_tag = $outbound_tag;

        $data = match (true) {
            is_array($extra) => $extra,
            is_object($extra) => (array)$extra,
            default => (array)(json_decode($extra, true) ?: []),
        };

        if (isset($data['balancerTag'])) $this->balancer_tag = (string)$data['balancerTag'];
        if (isset($data['user'])) $this->user = $data['user'];
        if (isset($data['network'])) $this->network = (string)$data['network'];
        if (isset($data['protocol'])) $this->protocol = $data['protocol'];
        if (isset($data['domainMatcher'])) $this->domain_matcher = (string)$data['domainMatcher'];
        if (isset($data['domain'])) $this->domain = $data['domain'];
        if (isset($data['ip'])) $this->ip = $data['ip'];
        if (isset($data['port'])) $this->port = $data['port'];
        if (isset($data['source'])) $this->source = $data['source'];
        if (isset($data['sourcePort'])) $this->source_port = $data['sourcePort'];
        if (isset($data['attrs'])) $this->attrs = $data['attrs'];
        if (isset($data['type'])) $this->type = (string)$data['type'];
    }

    /**
     * Magical fluent getters/setters.
     *
     * @param string $name Property name.
     * @param array $arguments Empty to read, one value to write.
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!property_exists($this, $name)) {
            return null;
        }
        if ($arguments === []) {
            return $this->{$name};
        }
        $this->{$name} = $arguments[0];
        return $this;
    }

    /**
     * Render the rule array.
     *
     * @return array
     */
    public function rule(): array
    {
        $rule = [
            'inboundTag' => is_array($this->inbound_tag) ? $this->inbound_tag : [$this->inbound_tag],
            'outboundTag' => $this->outbound_tag,
        ];
        if (!is_null($this->balancer_tag)) $rule['balancerTag'] = $this->balancer_tag;
        if (!is_null($this->user)) $rule['user'] = is_string($this->user) ? [$this->user] : $this->user;
        if (!is_null($this->network)) $rule['network'] = $this->network;
        if (!is_null($this->protocol)) $rule['protocol'] = is_string($this->protocol) ? [$this->protocol] : $this->protocol;
        if (!is_null($this->domain_matcher)) $rule['domainMatcher'] = $this->domain_matcher;
        if (!is_null($this->domain)) $rule['domain'] = is_string($this->domain) ? [$this->domain] : $this->domain;
        if (!is_null($this->ip)) $rule['ip'] = is_string($this->ip) ? [$this->ip] : $this->ip;
        if (!is_null($this->port)) $rule['port'] = is_array($this->port) ? implode(',', $this->port) : $this->port;
        if (!is_null($this->source)) $rule['source'] = is_string($this->source) ? [$this->source] : $this->source;
        if (!is_null($this->source_port)) $rule['sourcePort'] = is_array($this->source_port) ? implode(',', $this->source_port) : $this->source_port;
        if (!is_null($this->attrs)) $rule['attrs'] = $this->attrs;
        if (!is_null($this->type)) $rule['type'] = $this->type;
        return $rule;
    }

    /**
     * Rebuild a rule from its JSON representation.
     *
     * @param array|object|string $rule Rule payload.
     * @return false|Rule
     */
    public static function read(array|object|string $rule): false|Rule
    {
        $data = match (true) {
            is_array($rule) => $rule,
            is_object($rule) => (array)$rule,
            default => (array)(json_decode($rule, true) ?: []),
        };
        if ($data === []) {
            return false;
        }
        return new self($data['inboundTag'] ?? [], (string)($data['outboundTag'] ?? ''), $data);
    }
}
