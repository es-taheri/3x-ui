<?php

namespace XUI\Inbounds;
/**
 * @method bool enabled(bool $status = null)
 * @method array dest_override(array $destinations = null)
 * @method bool metadata_only(bool $status = null)
 * @method array domains_excluded(array $domains = null)
 * @method bool route_only(bool $status = null)
 */
class Sniffing
{
    private bool $enabled;
    private array $dest_override = ['http', 'tls', 'quic', 'fakedns'];
    private bool $metadata_only = false;
    private array $domains_excluded = [];
    private bool $route_only = false;

    public function __construct(
        bool  $enabled = true, array $dest_override = ['http', 'tls', 'quic', 'fakedns'], bool $metadata_only = false,
        array $domains_excluded = [], bool $route_only = false
    )
    {
        $this->enabled = $enabled;
        $this->dest_override = $dest_override;
        $this->metadata_only = $metadata_only;
        $this->domains_excluded = $domains_excluded;
        $this->route_only = $route_only;
    }

    public function __call(string $name, array $arguments)
    {
        $value = array_shift($arguments);
        return $value ? $this->{$name} = $value : $this->{$name};
    }

    /**
     * Returns fully structured sniffing for xray json config
     * @return array
     */
    public function sniffing(): array
    {
        return [
            'enabled' => $this->enabled,
            'destOverride' => $this->dest_override,
            'metadataOnly' => $this->metadata_only,
            'domainsExcluded' => $this->domains_excluded,
            'routeOnly' => $this->route_only,
        ];
    }
}