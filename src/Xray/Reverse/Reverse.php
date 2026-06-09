<?php

namespace XUI\Xray\Reverse;

use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Xray;
/**
 * @method array|bool|null   bridges(array|null $value = null)          Get/Set the bridges.<br/>Don't set $value to get list of bridges.
 * @method array|bool|null   portals(array|null $value = null)          Get/Set the portals.<br/>Don't set $value to get list of portals.
 */

class Reverse
{
    private array $bridges = [];
    private array $portals = [];

    public function __construct(private readonly Xray $xray)
    {
    }

    /**
     * Load reverse configurations from xray config
     * @return bool
     */

    public function load(): bool
    {
        $reverse = $this->xray->get('reverse', output: Statics::OUTPUT_ARRAY);
        if (is_array($reverse)) {
            if (isset($reverse['bridges'])) $this->bridges = $reverse['bridges'];
            if (isset($reverse['portals'])) $this->portals = $reverse['portals'];
            return true;
        } else {
            return false;
        }
    }

    /**
     *  Update reverse configuration based on current configs.
     * @return Result
     */
    public function update(): Result
    {
        $reverse = [];
        if (!empty($this->portals)) $reverse['portals'] = $this->portals;
        if (!empty($this->bridges)) $reverse['bridges'] = $this->bridges;
        return $this->xray->update(['reverse' => $reverse]);
    }
    public function __call($name, $args)
    {
        return empty($args) ? ($this->$name ?? null) : !!($this->$name = $args[0]);
    }
    /**
     * Add bridge to reverse
     * @param string $tag
     * @param string $domain
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|Result
     */
    public function add_bridge(string $tag, string $domain = 'reverse.xui', bool $apply = true): true|Result
    {
        $this->bridges[] = [
            'tag' => $tag,
            'domain' => $domain
        ];
        if ($apply)
            return $this->update();
        else
            return true;
    }

    /**
     * Get a bridge from reverse
     * @param string $bridge_tag
     * @return Result
     */
    public function get_bridge(string $bridge_tag): Result
    {
        $return = Result::make_fail(404, 'reverse bridge not found');
        foreach ($this->bridges as $bridge):
            if ($bridge_tag == $bridge['tag']):
                $return = Result::make_ok(Result::make_response(true, $bridge));
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Update a bridge from reverse
     * @param string $bridge_tag
     * @param string|null $tag
     * @param string|null $domain
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|Result
     */
    public function update_bridge(string $bridge_tag, string $tag = null, string $domain = null, bool $apply = true): true|Result
    {
        $return = Result::make_fail(404, 'reverse bridge not found');
        foreach ($this->bridges as $key => $bridge):
            if ($bridge_tag == $bridge['tag']):
                if (!is_null($tag)) $bridge['tag'] = $tag;
                if (!is_null($tag)) $bridge['domain'] = $domain;
                $this->bridges[$key] = $bridge;
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
     * Delete a bridge from reverse
     * @param string $bridge_tag
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|Result
     */
    public function delete_bridge(string $bridge_tag, bool $apply = true): true|Result
    {
        $deleted = false;
        foreach ($this->bridges as $key => $bridge):
            if ($bridge_tag == $bridge['tag']):
                unset($this->bridges[$key]);
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
            $return = Result::make_fail(404, 'reverse bridge not found');
        }
        return $return;
    }

    /**
     * Check the bridge availability
     * @param string $bridge_tag
     * @return bool
     */
    public function has_bridge(string $bridge_tag): bool
    {
        $return = false;
        foreach ($this->bridges as $key => $bridge):
            if ($bridge_tag == $bridge['tag']):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Add portal to reverse
     * @param string $tag
     * @param string $domain
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|Result
     */
    public function add_portal(string $tag, string $domain = 'reverse.xui', bool $apply = true): true|Result
    {
        $this->portals[] = [
            'tag' => $tag,
            'domain' => $domain
        ];
        if ($apply)
            return $this->update();
        else
            return true;
    }

    /**
     * Get a portal from reverse
     * @param string $portal_tag
     * @return Result
     */
    public function get_portal(string $portal_tag): Result
    {
        $return = Result::make_fail(404, 'reverse portal not found');
        foreach ($this->portals as $portal):
            if ($portal_tag == $portal['tag']):
                $return = Result::make_ok(Result::make_response(true, $portal));
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Update a portal from reverse
     * @param string $portal_tag
     * @param string|null $tag
     * @param string|null $domain
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|Result
     */
    public function update_portal(string $portal_tag, string $tag = null, string $domain = null, bool $apply = true): true|Result
    {
        $return = Result::make_fail(404, 'reverse portal not found');
        foreach ($this->portals as $key => $portal):
            if ($portal_tag == $portal['tag']):
                if (!is_null($tag)) $portal['tag'] = $tag;
                if (!is_null($tag)) $portal['domain'] = $domain;
                $this->portals[$key] = $portal;
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
     * Delete a portal from reverse
     * @param string $portal_tag
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|Result
     */
    public function delete_portal(string $portal_tag, bool $apply = true): true|Result
    {
        $deleted = false;
        foreach ($this->portals as $key => $portal):
            if ($portal_tag == $portal['tag']):
                unset($this->portals[$key]);
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
            $return = Result::make_fail(404, 'reverse portal not found');
        }
        return $return;
    }

    /**
     * Check the portal availability on reverse
     * @param string $portal_tag
     * @return bool
     */
    public function has_portal(string $portal_tag): bool
    {
        $return = false;
        foreach ($this->portals as $key => $portal):
            if ($portal_tag == $portal['tag']):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }
}