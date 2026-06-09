<?php

namespace XUI\Outbounds\Protocols\Blackhole;

use JSON\json;
use stdClass;

class Settings

{
    public string $response_type;

    public function __construct(string $response_type='none')
    {
        $this->response_type = $response_type;
    }

    /**
     * Returns fully structured settings for xray json config
     * @return array
     */
    public function settings(): array
    {
        return [
            'response' => [
                'type' => $this->response_type
            ]
        ];
    }
}