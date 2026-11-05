<?php

namespace XUI\Xray\Routing;

use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Xray;

/**
 * Object-oriented view of the Xray `routing` section.
 *
 * ```php
 * $routing = $xui->xray->routing();
 * $routing->add_rule(Routing::rule('in-443', 'proxy')->domain(['geosite:google']));
 * ```
 *
 * @method string|null domain_strategy(string|null $value = null) Get/Set the domain strategy.
 * @method string|null domain_matcher(string|null $value = null) Get/Set the domain matcher.
 * @method array|null balancers(array|null $value = null) Get/Set the balancer list.
 * @method array|null rules(array|null $value = null) Get/Set the rule list.
 */
class Routing
{
    /** Routing domain strategy. */
    private ?string $domain_strategy = null;

    /** Domain matcher (`hybrid` or `mph`). */
    private ?string $domain_matcher = null;

    /** Routing rules. */
    private array $rules = [];

    /** Routing balancers. */
    private array $balancers = [];

    /**
     * @param Xray $xray Parent Xray helper.
     */
    public function __construct(
        /** @var Xray Parent Xray helper. */
        private readonly Xray $xray
    )
    {
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
     * Load the routing section from the Xray config.
     *
     * @return bool True when the config could be read.
     */
    public function load(): bool
    {
        $routing = $this->xray->get('routing');
        if (!$routing->ok || !$routing->success) {
            return false;
        }

        $data = (array)$routing->obj(Statics::OUTPUT_ARRAY);
        if (isset($data['domainStrategy'])) $this->domain_strategy = (string)$data['domainStrategy'];
        if (isset($data['domainMatcher'])) $this->domain_matcher = (string)$data['domainMatcher'];
        if (isset($data['rules'])) $this->rules = (array)$data['rules'];
        if (isset($data['balancers'])) $this->balancers = (array)$data['balancers'];
        return true;
    }

    /**
     * Persist the routing section into the Xray config.
     *
     * @return Result
     */
    public function update(): Result
    {
        $routing = ['rules' => $this->rules];
        if (!is_null($this->domain_strategy)) {
            $routing['domainStrategy'] = $this->domain_strategy;
        }
        if (!is_null($this->domain_matcher)) {
            $routing['domainMatcher'] = $this->domain_matcher;
        }
        if ($this->balancers !== []) {
            $routing['balancers'] = $this->balancers;
        }
        return $this->xray->update(['routing' => $routing]);
    }

    /**
     * Append a rule.
     *
     * @param Rule $rule Rule to append.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function add_rule(Rule $rule, bool $apply = true): true|Result
    {
        $this->rules[] = $rule->rule();
        return $apply ? $this->update() : true;
    }

    /**
     * Find the first rule matching an outbound tag and inbound tags.
     *
     * @param string|array $rule_inbound_tag Inbound tag or list of tags.
     * @param string $rule_outbound_tag Outbound tag.
     * @return Result
     */
    public function get_rule(string|array $rule_inbound_tag, string $rule_outbound_tag): Result
    {
        foreach ($this->rules as $rule) {
            if ($this->matches($rule, $rule_inbound_tag, $rule_outbound_tag)) {
                return Result::make_ok(Result::make_response(true, $rule));
            }
        }
        return Result::make_fail(404, 'routing rule not found');
    }

    /**
     * Replace the first matching rule.
     *
     * @param string|array $rule_inbound_tag Inbound tag or list of tags.
     * @param string $rule_outbound_tag Outbound tag.
     * @param Rule $rule Replacement rule.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function update_rule(string|array $rule_inbound_tag, string $rule_outbound_tag, Rule $rule, bool $apply = true): true|Result
    {
        foreach ($this->rules as $key => $current) {
            if ($this->matches($current, $rule_inbound_tag, $rule_outbound_tag)) {
                $this->rules[$key] = $rule->rule();
                return $apply ? $this->update() : true;
            }
        }
        return Result::make_fail(404, 'routing rule not found');
    }

    /**
     * Delete the first matching rule.
     *
     * @param string|array $rule_inbound_tag Inbound tag or list of tags.
     * @param string $rule_outbound_tag Outbound tag.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function delete_rule(string|array $rule_inbound_tag, string $rule_outbound_tag, bool $apply = true): true|Result
    {
        foreach ($this->rules as $key => $rule) {
            if ($this->matches($rule, $rule_inbound_tag, $rule_outbound_tag)) {
                unset($this->rules[$key]);
                $this->rules = array_values($this->rules);
                return $apply ? $this->update() : true;
            }
        }
        return Result::make_fail(404, 'routing rule not found');
    }

    /**
     * Check whether a matching rule exists.
     *
     * @param string|array $rule_inbound_tag Inbound tag or list of tags.
     * @param string $rule_outbound_tag Outbound tag.
     * @return bool
     */
    public function has_rule(string|array $rule_inbound_tag, string $rule_outbound_tag): bool
    {
        foreach ($this->rules as $rule) {
            if ($this->matches($rule, $rule_inbound_tag, $rule_outbound_tag)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a rule.
     *
     * @param string|array $inbound_tag Inbound tag or list of tags.
     * @param string $outbound_tag Outbound tag.
     * @return Rule
     */
    public static function rule(string|array $inbound_tag, string $outbound_tag): Rule
    {
        return new Rule($inbound_tag, $outbound_tag);
    }

    /**
     * Append a balancer.
     *
     * @param Balancer $balancer Balancer to append.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function add_balancer(Balancer $balancer, bool $apply = true): true|Result
    {
        $this->balancers[] = $balancer->balancer();
        return $apply ? $this->update() : true;
    }

    /**
     * Find a balancer by tag.
     *
     * @param string $balancer_tag Balancer tag.
     * @return Result
     */
    public function get_balancer(string $balancer_tag): Result
    {
        foreach ($this->balancers as $balancer) {
            if (($balancer['tag'] ?? null) === $balancer_tag) {
                return Result::make_ok(Result::make_response(true, $balancer));
            }
        }
        return Result::make_fail(404, 'routing balancer not found');
    }

    /**
     * Replace a balancer.
     *
     * @param string $balancer_tag Existing balancer tag.
     * @param Balancer $balancer Replacement balancer.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function update_balancer(string $balancer_tag, Balancer $balancer, bool $apply = true): true|Result
    {
        foreach ($this->balancers as $key => $current) {
            if (($current['tag'] ?? null) === $balancer_tag) {
                $this->balancers[$key] = $balancer->balancer();
                return $apply ? $this->update() : true;
            }
        }
        return Result::make_fail(404, 'routing balancer not found');
    }

    /**
     * Delete a balancer.
     *
     * @param string $balancer_tag Balancer tag.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function delete_balancer(string $balancer_tag, bool $apply = true): true|Result
    {
        foreach ($this->balancers as $key => $balancer) {
            if (($balancer['tag'] ?? null) === $balancer_tag) {
                unset($this->balancers[$key]);
                $this->balancers = array_values($this->balancers);
                return $apply ? $this->update() : true;
            }
        }
        return Result::make_fail(404, 'routing balancer not found');
    }

    /**
     * Check whether a balancer tag exists.
     *
     * @param string $balancer_tag Balancer tag.
     * @return bool
     */
    public function has_balancer(string $balancer_tag): bool
    {
        foreach ($this->balancers as $balancer) {
            if (($balancer['tag'] ?? null) === $balancer_tag) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a balancer.
     *
     * @param string $balancer_tag Balancer tag.
     * @return Balancer
     */
    public static function balancer(string $balancer_tag): Balancer
    {
        return new Balancer($balancer_tag);
    }

    /**
     * Check whether a rule matches the given outbound/inbound tags.
     *
     * @param array $rule Rule payload.
     * @param string|array $inbound_tags Inbound tag or list of tags.
     * @param string $outbound_tag Outbound tag.
     * @return bool
     */
    private function matches(array $rule, string|array $inbound_tags, string $outbound_tag): bool
    {
        if (($rule['outboundTag'] ?? null) !== $outbound_tag) {
            return false;
        }
        $wanted = is_array($inbound_tags) ? $inbound_tags : [$inbound_tags];
        $actual = (array)($rule['inboundTag'] ?? []);
        foreach ($wanted as $tag) {
            if (!in_array($tag, $actual, true)) {
                return false;
            }
        }
        return true;
    }
}
