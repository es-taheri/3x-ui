<?php

namespace XUI\Outbounds\Protocols\Freedom;

use JSON\json;
use stdClass;

class Settings

{
    public string $domain_strategy;
    public int $redirect;
    public array $fragment;
    public int $proxy_protocol;
    public function __construct(string $domain_strategy, int $redirect, array $fragment, bool $proxy_protocol)
    {
        $this->domain_strategy = $domain_strategy;
        $this->redirect = $redirect;
        $this->fragment = $fragment;
        $this->proxy_protocol = $proxy_protocol;
    }

    /**
     * Returns fully structured settings for xray json config
     * @return array
     */
    public function settings(): array
    {
        return [
            'domainStrategy' => $this->domain_strategy,
            'redirect' => $this->redirect,
            'fragment' => $this->fragment,
            'proxyProtocol' => $this->proxy_protocol,
        ];
    }
}