<?php

namespace XUI\Xray\Routing;

/**
 * Fluent builder for one Xray routing balancer.
 *
 * ```php
 * $balancer = Routing::balancer('auto')
 *     ->selector(['proxy-', 'direct-'])
 *     ->strategy_type('leastPing');
 * ```
 *
 * @method string|self tag(string|null $value = null) Balancer identifier.
 * @method array|self selector(array|null $value = null) Outbound tag prefixes matched by the balancer.
 * @method string|self fallback_tag(string|null $value = null) Outbound used when every member fails.
 * @method string|self strategy_type(string|null $value = null) `random`, `roundRobin`, `leastPing` or `leastLoad`.
 * @method array|self strategy_settings(array|null $value = null) Strategy specific settings (leastLoad only).
 */
class Balancer
{
    /** Balancer identifier. */
    private string $tag;

    /** Outbound tag prefixes matched by the balancer. */
    private array $selector = [];

    /** Outbound used when every member fails. */
    private string $fallback_tag = '';

    /** Load-balancing strategy. */
    private string $strategy_type = 'roundRobin';

    /** Strategy specific settings. */
    private array $strategy_settings = [];

    /**
     * @param string $tag Balancer identifier.
     * @param array|string|object $extra Additional balancer fields.
     */
    public function __construct(string $tag, array|string|object $extra = [])
    {
        $this->tag = $tag;

        $data = match (true) {
            is_array($extra) => $extra,
            is_object($extra) => (array)$extra,
            default => (array)(json_decode($extra, true) ?: []),
        };

        if (isset($data['selector'])) $this->selector = (array)$data['selector'];
        if (isset($data['fallbackTag'])) $this->fallback_tag = (string)$data['fallbackTag'];
        if (isset($data['strategy']['type'])) $this->strategy_type = (string)$data['strategy']['type'];
        if (isset($data['strategy']['settings'])) $this->strategy_settings = (array)$data['strategy']['settings'];
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
     * Render the balancer array.
     *
     * @return array
     */
    public function balancer(): array
    {
        $return = [
            'tag' => $this->tag,
            'selector' => $this->selector,
        ];
        if ($this->fallback_tag !== '') {
            $return['fallbackTag'] = $this->fallback_tag;
        }
        if ($this->strategy_type === 'leastLoad') {
            $return['strategy'] = [
                'type' => $this->strategy_type,
                'settings' => $this->strategy_settings,
            ];
        }
        return $return;
    }

    /**
     * Rebuild a balancer from its JSON representation.
     *
     * @param array|object|string $balancer Balancer payload.
     * @return Balancer|false
     */
    public static function read(array|object|string $balancer): Balancer|false
    {
        $data = match (true) {
            is_array($balancer) => $balancer,
            is_object($balancer) => (array)$balancer,
            default => (array)(json_decode($balancer, true) ?: []),
        };
        if (!isset($data['tag'])) {
            return false;
        }
        return new self((string)$data['tag'], $data);
    }
}
