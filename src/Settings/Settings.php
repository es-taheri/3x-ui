<?php

namespace XUI\Settings;

/**
 * In-memory wrapper around the decoded panel settings array.
 */
class Settings
{
    /** Decoded panel settings keyed by setting name. */
    public array $settings;

    /**
     * @param array $settings Decoded panel settings.
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Read one or many settings.
     *
     * @param string|array $key Setting name or list of names.
     * @return mixed A single value, or an array keyed by setting name.
     */
    public function get(string|array $key): mixed
    {
        if (is_array($key)) {
            $return = [];
            foreach ($key as $name) {
                if (array_key_exists($name, $this->settings)) {
                    $return[$name] = $this->settings[$name];
                }
            }
            return $return;
        }
        return $this->settings[$key] ?? null;
    }

    /**
     * Merge setting updates.
     *
     * @param array $updates Setting name/value pairs.
     * @return void
     */
    public function update(array $updates): void
    {
        foreach ($updates as $key => $value) {
            $this->settings[$key] = $value;
        }
    }
}
