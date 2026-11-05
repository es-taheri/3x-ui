<?php

namespace XUI\Xray\Reverse;

use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Xray;

/**
 * Object-oriented view of the Xray `reverse` section.
 *
 * <h4>Removed from 3x-ui v2.9.4, kept for older panels.</h4>
 *
 * @method array bridges(array|null $value = null) Get/Set the bridge list.
 * @method array portals(array|null $value = null) Get/Set the portal list.
 */
class Reverse
{
    /** Bridge definitions. */
    private array $bridges = [];

    /** Portal definitions. */
    private array $portals = [];

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
     * Load the reverse section from the Xray config.
     *
     * @return bool True when the config could be read.
     */
    public function load(): bool
    {
        $reverse = $this->xray->get('reverse');
        if (!$reverse->ok || !$reverse->success) {
            return false;
        }

        $data = (array)$reverse->obj(Statics::OUTPUT_ARRAY);
        if (isset($data['bridges'])) $this->bridges = (array)$data['bridges'];
        if (isset($data['portals'])) $this->portals = (array)$data['portals'];
        return true;
    }

    /**
     * Persist the reverse section into the Xray config.
     *
     * @return Result
     */
    public function update(): Result
    {
        $reverse = [];
        if ($this->portals !== []) $reverse['portals'] = $this->portals;
        if ($this->bridges !== []) $reverse['bridges'] = $this->bridges;
        return $this->xray->update(['reverse' => $reverse]);
    }

    /**
     * Append a bridge.
     *
     * @param string $tag Bridge tag.
     * @param string $domain Bridge domain.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function add_bridge(string $tag, string $domain = 'reverse.xui', bool $apply = true): true|Result
    {
        $this->bridges[] = ['tag' => $tag, 'domain' => $domain];
        return $apply ? $this->update() : true;
    }

    /**
     * Find a bridge by tag.
     *
     * @param string $bridge_tag Bridge tag.
     * @return Result
     */
    public function get_bridge(string $bridge_tag): Result
    {
        foreach ($this->bridges as $bridge) {
            if (($bridge['tag'] ?? null) === $bridge_tag) {
                return Result::make_ok(Result::make_response(true, $bridge));
            }
        }
        return Result::make_fail(404, 'reverse bridge not found');
    }

    /**
     * Replace a bridge.
     *
     * @param string $bridge_tag Existing bridge tag.
     * @param string|null $tag New tag.
     * @param string|null $domain New domain.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function update_bridge(string $bridge_tag, ?string $tag = null, ?string $domain = null, bool $apply = true): true|Result
    {
        foreach ($this->bridges as $key => $bridge) {
            if (($bridge['tag'] ?? null) !== $bridge_tag) {
                continue;
            }
            if (!is_null($tag)) $bridge['tag'] = $tag;
            if (!is_null($domain)) $bridge['domain'] = $domain;
            $this->bridges[$key] = $bridge;
            return $apply ? $this->update() : true;
        }
        return Result::make_fail(404, 'reverse bridge not found');
    }

    /**
     * Delete a bridge.
     *
     * @param string $bridge_tag Bridge tag.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function delete_bridge(string $bridge_tag, bool $apply = true): true|Result
    {
        foreach ($this->bridges as $key => $bridge) {
            if (($bridge['tag'] ?? null) === $bridge_tag) {
                unset($this->bridges[$key]);
                $this->bridges = array_values($this->bridges);
                return $apply ? $this->update() : true;
            }
        }
        return Result::make_fail(404, 'reverse bridge not found');
    }

    /**
     * Check whether a bridge tag exists.
     *
     * @param string $bridge_tag Bridge tag.
     * @return bool
     */
    public function has_bridge(string $bridge_tag): bool
    {
        foreach ($this->bridges as $bridge) {
            if (($bridge['tag'] ?? null) === $bridge_tag) {
                return true;
            }
        }
        return false;
    }

    /**
     * Append a portal.
     *
     * @param string $tag Portal tag.
     * @param string $domain Portal domain.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function add_portal(string $tag, string $domain = 'reverse.xui', bool $apply = true): true|Result
    {
        $this->portals[] = ['tag' => $tag, 'domain' => $domain];
        return $apply ? $this->update() : true;
    }

    /**
     * Find a portal by tag.
     *
     * @param string $portal_tag Portal tag.
     * @return Result
     */
    public function get_portal(string $portal_tag): Result
    {
        foreach ($this->portals as $portal) {
            if (($portal['tag'] ?? null) === $portal_tag) {
                return Result::make_ok(Result::make_response(true, $portal));
            }
        }
        return Result::make_fail(404, 'reverse portal not found');
    }

    /**
     * Replace a portal.
     *
     * @param string $portal_tag Existing portal tag.
     * @param string|null $tag New tag.
     * @param string|null $domain New domain.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function update_portal(string $portal_tag, ?string $tag = null, ?string $domain = null, bool $apply = true): true|Result
    {
        foreach ($this->portals as $key => $portal) {
            if (($portal['tag'] ?? null) !== $portal_tag) {
                continue;
            }
            if (!is_null($tag)) $portal['tag'] = $tag;
            if (!is_null($domain)) $portal['domain'] = $domain;
            $this->portals[$key] = $portal;
            return $apply ? $this->update() : true;
        }
        return Result::make_fail(404, 'reverse portal not found');
    }

    /**
     * Delete a portal.
     *
     * @param string $portal_tag Portal tag.
     * @param bool $apply Persist immediately.
     * @return true|Result
     */
    public function delete_portal(string $portal_tag, bool $apply = true): true|Result
    {
        foreach ($this->portals as $key => $portal) {
            if (($portal['tag'] ?? null) === $portal_tag) {
                unset($this->portals[$key]);
                $this->portals = array_values($this->portals);
                return $apply ? $this->update() : true;
            }
        }
        return Result::make_fail(404, 'reverse portal not found');
    }

    /**
     * Check whether a portal tag exists.
     *
     * @param string $portal_tag Portal tag.
     * @return bool
     */
    public function has_portal(string $portal_tag): bool
    {
        foreach ($this->portals as $portal) {
            if (($portal['tag'] ?? null) === $portal_tag) {
                return true;
            }
        }
        return false;
    }
}
