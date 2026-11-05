<?php

namespace XUI\Inbounds;

use BadMethodCallException;

/**
 * Builds the `sniffing` object of an Xray inbound.
 *
 * Sniffing lets the panel detect the real protocol of a connection so routing
 * rules can match on domains.
 *
 * The magic accessors are declared in the class docblock for IDE completion:
 * call them with an argument to set, without one to read.
 *
 * ```php
 * $sniffing = new Sniffing();
 * $sniffing->enabled(false);
 * $sniffing->dest_override(['http', 'tls']);
 * ```
 *
 * @method bool|self enabled(bool|null $enabled = null) Get/Set whether sniffing is enabled.
 * @method array|self dest_override(array|null $destinations = null) Get/Set the sniffed destination overrides.
 * @method bool|self metadata_only(bool|null $status = null) Get/Set whether only metadata is sniffed.
 * @method array|self domains_excluded(array|null $domains = null) Get/Set excluded domains.
 * @method bool|self route_only(bool|null $status = null) Get/Set route-only mode.
 */
class Sniffing
{
    /** Sniffing destination: HTTP. */
    public const DEST_HTTP = 'http';

    /** Sniffing destination: TLS. */
    public const DEST_TLS = 'tls';

    /** Sniffing destination: QUIC. */
    public const DEST_QUIC = 'quic';

    /** Sniffing destination: FakeDNS. */
    public const DEST_FAKEDNS = 'fakedns';

    /** Whether sniffing is enabled. */
    private bool $enabled;

    /** Destinations whose sniffed protocol replaces the configured one. */
    private array $dest_override;

    /** Only sniff metadata, never the payload. */
    private bool $metadata_only;

    /** Domains that must not be sniffed. */
    private array $domains_excluded;

    /** Use sniffed domains only for routing, not as the target address. */
    private bool $route_only;

    /**
     * @param bool $enabled Whether sniffing is enabled.
     * @param array $dest_override Sniffed destinations to override.
     * @param bool $metadata_only Only sniff metadata.
     * @param array $domains_excluded Domains excluded from sniffing.
     * @param bool $route_only Use sniffed domains for routing only.
     */
    public function __construct(
        bool $enabled = true,
        array $dest_override = [self::DEST_HTTP, self::DEST_TLS, self::DEST_QUIC, self::DEST_FAKEDNS],
        bool $metadata_only = false,
        array $domains_excluded = [],
        bool $route_only = false
    )
    {
        $this->enabled = $enabled;
        $this->dest_override = $dest_override;
        $this->metadata_only = $metadata_only;
        $this->domains_excluded = $domains_excluded;
        $this->route_only = $route_only;
    }

    /**
     * Rebuild an instance from a decoded `sniffing` array/object.
     *
     * @param array|object|string $sniffing Decoded sniffing object.
     * @return self
     */
    public static function from_array(array|object|string $sniffing): self
    {
        $data = match (true) {
            is_array($sniffing) => $sniffing,
            is_object($sniffing) => (array)$sniffing,
            default => (array)(json_decode($sniffing, true) ?: []),
        };

        return new self(
            (bool)($data['enabled'] ?? true),
            (array)($data['destOverride'] ?? [self::DEST_HTTP, self::DEST_TLS, self::DEST_QUIC, self::DEST_FAKEDNS]),
            (bool)($data['metadataOnly'] ?? false),
            (array)($data['domainsExcluded'] ?? []),
            (bool)($data['routeOnly'] ?? false)
        );
    }

    /**
     * Magic getter/setter used by the documented fluent accessors.
     *
     * @param string $name Property name.
     * @param array $arguments Zero arguments read, one argument writes.
     * @return mixed
     * @throws BadMethodCallException When the property does not exist.
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!property_exists($this, $name)) {
            throw new BadMethodCallException(sprintf('Unknown sniffing property "%s".', $name));
        }
        if ($arguments === []) {
            return $this->{$name};
        }
        $this->{$name} = $arguments[0];
        return $this;
    }

    /**
     * Render the complete `sniffing` array.
     *
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
