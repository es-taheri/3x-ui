<?php

namespace XUI\Xray;

/**
 * In-memory wrapper around the decoded Xray config array.
 *
 * It lets callers read and merge top level sections without re-implementing the
 * array plumbing.
 */
class Settings
{
    /** Decoded Xray config. */
    public array $settings;

    /**
     * @param array $settings Decoded Xray config.
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Read one or many top level sections.
     *
     * @param string|array $key Section name or list of names.
     * @return mixed A single section value, or an array keyed by section name.
     */
    public function get(string|array $key): mixed
    {
        if (is_array($key)) {
            $return = [];
            foreach ($key as $section) {
                if (array_key_exists($section, $this->settings)) {
                    $return[$section] = $this->settings[$section];
                }
            }
            return $return;
        }
        return $this->settings[$key] ?? null;
    }

    /**
     * Merge top level sections into the config.
     *
     * @param array $updates Section name/value pairs.
     * @return void
     */
    public function update(array $updates): void
    {
        foreach ($updates as $key => $value) {
            $this->settings[$key] = $value;
        }
    }

    /**
     * Render the config array.
     *
     * @return array
     */
    public function settings(): array
    {
        if (isset($this->settings['policy']['levels']) && is_array($this->settings['policy']['levels'])) {
            $this->settings['policy']['levels'] = (object)$this->settings['policy']['levels'];
        }
        if (isset($this->settings['stats']) && is_array($this->settings['stats'])) {
            $this->settings['stats'] = (object)$this->settings['stats'];
        }
        if (isset($this->settings['outbounds']) && is_array($this->settings['outbounds'])) {
            foreach ($this->settings['outbounds'] as $key => $outbound) {
                if (isset($outbound['settings']) && is_array($outbound['settings'])) {
                    $this->settings['outbounds'][$key]['settings'] = (object)$outbound['settings'];
                }
            }
        }
        return $this->settings;
    }
}
