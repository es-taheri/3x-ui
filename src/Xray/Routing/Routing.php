<?php

namespace XUI\Xray\Routing;

use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Xray;

/**
 * @method string|bool|null  domain_strategy(string|null $value = null)   Get/Set the domain strategy.<br/>Don't set $value to get value of domain strategy.
 * @method string|bool|null  domain_matcher(string|null $value = null)    Get/Set the domain matcher.<br/>Don't set $value to get value of domain matcher.
 * @method array|bool|null   balancers(array|null $value = null)          Get/Set the balancers list.<br/>Don't set $value to get value of balancers.
 * @method array|bool|null   rules(array|null $value = null)          Get/Set the rules.<br/>Don't set $value to get value of rules.
 */
class Routing
{
    private string $domain_strategy;
    private string $domain_matcher;
    private array $rules;
    private array $balancers;

    public function __construct(private readonly Xray $xray)
    {
    }

    /**
     * Load routing configurations from xray config
     * @return bool
     */
    public function load(): bool
    {
        $routing = $this->xray->get('routing', output: Statics::OUTPUT_ARRAY);
        if (is_array($routing)) {
            if (isset($routing['domainStrategy'])) $this->domain_strategy = $routing['domainStrategy'];
            if (isset($routing['domainMatcher'])) $this->domain_matcher = $routing['domainMatcher'];
            if (isset($routing['rules'])) $this->rules = $routing['rules'];
            if (isset($routing['balancers'])) $this->balancers = $routing['balancers'];
            return true;
        } else {
            return false;
        }
    }

    /**
     *  Update routing configuration based on current configs.
     * @return Result
     */
    public function update(): Result
    {
        $routing = [
            'domainStrategy' => $this->domain_strategy,
            'rules' => $this->rules,
        ];
        if (isset($this->domain_matcher)) $routing['domainMatcher'] = $this->domain_matcher;
        if (isset($this->balancers)) $routing['balancers'] = $this->balancers;
        return $this->xray->update(['routing' => $routing]);
    }

    public function __call($name, $args)
    {
        return empty($args) ? ($this->$name ?? null) : !!($this->$name = $args[0]);
    }

    /**
     * Add rule to routing
     * @param Rule $rule
     * @param bool $apply Apply changes to routing in xray config
     * @return true|Result
     */
    public function add_rule(Rule $rule, bool $apply = true): true|Result
    {
        $this->rules[] = $rule->rule();
        if ($apply) {
            return $this->update();
        } else {
            return true;
        }
    }

    /**
     * Get a rule from routing
     * @param string|array $rule_inbound_tag
     * @param string $rule_outbound_tag
     * @return Result
     */
    public function get_rule(string|array $rule_inbound_tag, string $rule_outbound_tag): Result
    {
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $return = Result::make_fail(404, 'routing rule not found');
        foreach ($this->rules as $rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($rule['inboundTag']) && !in_array($a_inbound_tag, $rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $rule['outboundTag'] && $is_same):
                $return = Result::make_ok(Result::make_response(true, $rule));
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Update a rule of routing
     * @param string|array $rule_inbound_tag
     * @param string $rule_outbound_tag
     * @param Rule $rule
     * @param bool $apply Apply changes to routing in xray config
     * @return true|Result
     */
    public function update_rule(string|array $rule_inbound_tag, string $rule_outbound_tag, Rule $rule, bool $apply = true): true|Result
    {
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $return = Result::make_fail(404, 'routing rule not found');
        foreach ($this->rules as $key => $a_rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($a_rule['inboundTag']) && !in_array($a_inbound_tag, $a_rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $a_rule['outboundTag'] && $is_same):
                $this->rules[$key] = $rule->rule();
                if ($apply)
                    $return = $this->update();
                else
                    $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Delete a rule from routing
     * @param string|array $rule_inbound_tag
     * @param string $rule_outbound_tag
     * @param bool $apply Apply changes to routing in xray config
     * @return true|Result
     */
    public function delete_rule(string|array $rule_inbound_tag, string $rule_outbound_tag, bool $apply = true): true|Result
    {
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $deleted = false;
        foreach ($this->rules as $key => $rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($rule['inboundTag']) && !in_array($a_inbound_tag, $rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $rule['outboundTag'] && $is_same):
                unset($this->rules[$key]);
                $deleted = true;
                break;
            endif;
        endforeach;
        if ($deleted) {
            if ($apply)
                $return = $this->update();
            else
                $return = true;
        } else {
            $return = Result::make_fail(404, 'routing rule not found');
        }
        return $return;
    }

    /**
     * Check a rule availability on routing
     * @param string|array $rule_inbound_tag
     * @param string $rule_outbound_tag
     * @return bool
     */
    public function has_rule(string|array $rule_inbound_tag, string $rule_outbound_tag): bool
    {
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $return = false;
        foreach ($this->rules as $rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($rule['inboundTag']) && !in_array($a_inbound_tag, $rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $rule['outboundTag'] && $is_same):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Configure a rule
     * @param array|string $inbound_tag
     * @param string $outbound_tag
     * @return Rule
     */
    public static function rule(array|string $inbound_tag, string $outbound_tag): Rule
    {
        return new Rule($inbound_tag, $outbound_tag);
    }

    /**
     * Add balancer to routing
     * @param Balancer $balancer
     * @param bool $apply Apply changes to routing in xray config
     * @return true|Result
     */
    public function add_balancer(Balancer $balancer, bool $apply = true): true|Result
    {
        $this->balancers[] = $balancer->balancer();
        if ($apply) {
            return $this->update();
        } else {
            return true;
        }
    }

    /**
     * Get a balancer from routing
     * @param string $balancer_tag
     * @return Result
     */
    public function get_balancer(string $balancer_tag): Result
    {
        foreach ($this->balancers as $balancer):
            if ($balancer_tag == $balancer['tag'])
                return Result::make_ok(Result::make_response(true, $balancer));
        endforeach;
        return Result::make_fail(404, 'routing balancer not found');
    }

    /**
     * Update a balancer of routing
     * @param string $balancer_tag
     * @param Balancer $balancer
     * @param bool $apply Apply changes to routing in xray config
     * @return true|Result
     */
    public function update_balancer(string $balancer_tag, Balancer $balancer, bool $apply = true): true|Result
    {
        foreach ($this->balancers as $key => $a_balancer):
            if ($balancer_tag == $a_balancer['tag']):
                $this->balancers[$key] = $balancer->balancer();
                if ($apply)
                    return $this->update();
                else
                    return true;
            endif;
        endforeach;
        return Result::make_fail(404, 'routing rule not found');
    }

    /**
     * Delete a balancer from routing
     * @param string $balancer_tag
     * @param bool $apply Apply changes to routing in xray config
     * @return true|Result
     */
    public function delete_balancer(string $balancer_tag, bool $apply = true): true|Result
    {
        foreach ($this->balancers as $key => $a_balancer):
            if ($balancer_tag == $a_balancer['tag']):
                unset($this->balancers[$key]);
                if ($apply)
                    return $this->update();
                else
                    return true;
            endif;
        endforeach;
        return Result::make_fail(404, 'routing rule not found');
    }

    /**
     * Check a balancer availability on routing
     * @param string $balancer_tag
     * @return bool
     */
    public function has_balancer(string $balancer_tag): bool
    {
        foreach ($this->balancers as $a_balancer):
            if ($balancer_tag == $a_balancer['tag']):
                return true;
            endif;
        endforeach;
        return false;
    }

    /**
     * Configure a balancer
     * @param string $balancer_tag
     * @param array|string|object $balancer
     * @return Balancer
     */
    public static function balancer(string $balancer_tag): Balancer
    {
        return new Balancer($balancer_tag);
    }

}