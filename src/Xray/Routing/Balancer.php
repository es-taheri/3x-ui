<?php

namespace XUI\Xray\Routing;

use JSON\json;

/**
 * @method string|self  tag(string $value = null) The identifier of this load balancer, used to reference balancerTag in RuleObject.
 * @method array|self  selector(array $value = null) An array of strings. Each string is used for prefix matching against outbound identifiers.
 * @method string|self  fallback_tag(string $value = null) If all outbounds cannot be connected based on observation results, the outbound specified by this configuration item is used.
 * @method string|self  strategy_type(string $value = null) <b>random</b> : Default value. Randomly selects a matched outbound proxy.<br><b>roundRobin</b> : Selects matched outbound proxies in order.<br><b>leastPing</b> : Selects the matched outbound proxy with the lowest latency based on observation results.<br><b>leastLoad</b> : Selects the most stable outbound proxy based on observation results.
 * @method array|self  strategy_settings(array $value = null) This is an optional configuration item. The configuration format varies for different load balancing strategies. <h5>Currently, only the "leastLoad" strategy supports this item.</h5><br><a href = "https://xtls.github.io/en/config/routing.html#strategysettingsobject">StrategySettingsObject</a>
 */
class Balancer

{

    private string $tag;
    private array $selector;
    private string $fallback_tag = '';
    private string $strategy_type = 'roundRobin';
    private array $strategy_settings = [];

    public function __construct(string $tag, array|string|object $extra = [])
    {
        $this->tag = $tag;
        $extra = json::to_array($extra);
        if (isset($extra['selector'])) $this->selector = $extra['selector'];
        if (isset($extra['fallbackTag'])) $this->fallback_tag = $extra['fallbackTag'];
        if (isset($extra['strategy']['type'])) $this->strategy_type = $extra['strategy']['type'];
        if (isset($extra['strategy']['settings'])) $this->strategy_settings = $extra['strategy']['settings'];
    }

    public function __call($name, $args)
    {
        if (empty($args))
            return $this->$name;
        else
            $this->$name = $args[0];
        return $this;
    }

    public function balancer(): array
    {
        $return = [
            'tag' => $this->tag,
            'selector' => $this->selector,
            'fallbackTag' => $this->fallback_tag
        ];
        if ($this->strategy_type == 'leastLoad')
            $return['strategy'] = [
                'type' => $this->strategy_type,
                'settings' => $this->strategy_settings
            ];
        return $return;
    }

    /**
     * Read a balancer and convert it to Balancer object oriented\
     * Only balancer accepted!
     * @param array|object|string $balancer
     * @return Balancer|false
     */
    public static function read(array|object|string $balancer): Balancer|false
    {
        $balancer = json::to_array($balancer);
        if (isset($balancer['tag']))
            return new self($balancer['tag'], $balancer);
        else
            return false;
    }
}