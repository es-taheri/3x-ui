<?php

namespace XUI\Settings;

class Settings
{
    public array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function get(array|string $key): mixed
    {
        if (is_array($key)) {
            $return = [];
            foreach ($key as $a_key):
                $return[$a_key] = $this->settings[$a_key];
            endforeach;
        } else {
            $return = $this->settings[$key];
        }
        return $return;
    }

    public function update(array $updates): void
    {
        foreach ($updates as $a_key => $value):
            $this->settings[$a_key] = $value;
        endforeach;
    }
}