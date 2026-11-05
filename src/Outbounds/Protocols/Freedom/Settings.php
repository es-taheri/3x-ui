<?php

namespace XUI\Outbounds\Protocols\Freedom;

/**
 * `settings` object of a freedom outbound.
 *
 * @link https://xtls.github.io/en/config/outbounds/freedom.html
 */
class Settings
{
    /** Domain strategy: `AsIs`, `UseIP`, `UseIPv4`, `UseIPv6`, `UseIPResolve`. */
    public string $domain_strategy;

    /** Redirect destination TCP port, null to disable. */
    public ?int $redirect;

    /** Fragmentation settings. */
    public ?array $fragment;

    /** PROXY protocol version expected by the destination. */
    public int $proxy_protocol;

    /** Optional noise settings. */
    public ?array $noise;

    /** Additional raw settings fields. */
    public array $extra;

    /**
     * @param string $domain_strategy Domain strategy.
     * @param int|null $redirect Redirect destination port.
     * @param array|null $fragment Fragmentation settings.
     * @param int $proxy_protocol PROXY protocol version.
     * @param array|null $noise Noise settings.
     * @param array $extra Additional raw settings fields.
     */
    public function __construct(
        string $domain_strategy = 'AsIs',
        ?int $redirect = null,
        ?array $fragment = null,
        int $proxy_protocol = 0,
        ?array $noise = null,
        array $extra = []
    )
    {
        $this->domain_strategy = $domain_strategy;
        $this->redirect = $redirect;
        $this->fragment = $fragment;
        $this->proxy_protocol = $proxy_protocol;
        $this->noise = $noise;
        $this->extra = $extra;
    }

    /**
     * Render the complete `settings` array.
     *
     * @return array
     */
    public function settings(): array
    {
        $return = ['domainStrategy' => $this->domain_strategy];
        if (!is_null($this->redirect)) {
            $return['redirect'] = $this->redirect;
        }
        if (!is_null($this->fragment)) {
            $return['fragment'] = $this->fragment;
        }
        if ($this->proxy_protocol > 0) {
            $return['proxyProtocol'] = $this->proxy_protocol;
        }
        if (!is_null($this->noise)) {
            $return['noise'] = $this->noise;
        }
        return array_merge($return, $this->extra);
    }
}
