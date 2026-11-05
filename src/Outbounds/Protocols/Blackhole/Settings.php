<?php

namespace XUI\Outbounds\Protocols\Blackhole;

/**
 * `settings` object of a blackhole outbound.
 *
 * @link https://xtls.github.io/en/config/outbounds/blackhole.html
 */
class Settings
{
    /** Response type: `none` or `http`. */
    public string $response_type;

    /**
     * @param string $response_type Response type.
     */
    public function __construct(string $response_type = 'none')
    {
        $this->response_type = $response_type;
    }

    /**
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        return ['response' => ['type' => $this->response_type]];
    }
}
