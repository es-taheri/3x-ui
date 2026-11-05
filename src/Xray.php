<?php

namespace XUI;

use stdClass;
use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Xray\Reverse\Reverse;
use XUI\Xray\Routing\Routing;
use XUI\Xray\Settings as XrayConfig;

/**
 * Xray settings endpoints: config template, outbound management, third-party
 * integrations and config testing.
 *
 * ```php
 * $config = $xui->xray->settings();          // stdClass tree
 * $xui->xray->update(['log' => ['loglevel' => 'warning']]);
 * ```
 */
class Xray
{
    /**
     * @param Request $request Shared HTTP transport.
     */
    public function __construct(
        /** @var Request Shared HTTP transport. */
        private readonly Request $request
    )
    {
    }

    /**
     * Load the routing section of the Xray config.
     *
     * @return Routing|false False when the config could not be read.
     */
    public function routing(): Routing|false
    {
        $routing = new Routing($this);
        return $routing->load() ? $routing : false;
    }

    /**
     * Load the reverse section of the Xray config.
     *
     * <h4>Removed from 3x-ui v2.9.4.</h4>
     *
     * @return Reverse|false False when the config could not be read.
     */
    public function reverse(): Reverse|false
    {
        $reverse = new Reverse($this);
        return $reverse->load() ? $reverse : false;
    }

    /**
     * Fetch the raw template response (xraySetting, inboundTags, ...).
     *
     * @return Result
     */
    public function template(): Result
    {
        return $this->_request(Request::post(''));
    }

    /**
     * Decode the Xray config template.
     *
     * @param Result|null $template Pre-fetched template response.
     * @param int $output One of the {@see Statics::OUTPUT_*} constants.
     * @return Result|array|string|stdClass
     */
    public function settings(?Result $template = null, int $output = Statics::OUTPUT_OBJECT): Result|array|string|stdClass
    {
        $result = $template ?? $this->template();
        if (!$result->ok || !$result->success) {
            return $result;
        }

        $payload = $result->obj(Statics::OUTPUT_ARRAY);
        $config = $payload['xraySetting'] ?? $payload;
        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }
        return Statics::output((array)$config, $output);
    }

    /**
     * Read one or many top level sections of the Xray config.
     *
     * @param string|array $section_or_sections Section name or list of names.
     * @param array|null $settings Pre-fetched config array.
     * @return stdClass|array|string|Result
     */
    public function get(string|array $section_or_sections, ?array $settings = null): stdClass|array|string|Result
    {
        $settings ??= $this->settings(output: Statics::OUTPUT_ARRAY);
        if ($settings instanceof Result) {
            return $settings;
        }

        $config = new XrayConfig((array)$settings);
        $value = $config->get($section_or_sections);
        if (!is_null($value)) {
            return Result::make_ok(Result::make_response(true, $value));
        }
        return Result::make_ok(Result::make_response(false, null, 'xray setting/s not found'));
    }

    /**
     * Merge updates into the Xray config and save it.
     *
     * @param array $update Key/value pairs to merge at the top level.
     * @param array|null $settings Pre-fetched config array.
     * @return Result
     */
    public function update(array $update, ?array $settings = null): Result
    {
        $settings ??= $this->settings(output: Statics::OUTPUT_ARRAY);
        if ($settings instanceof Result) {
            return $settings;
        }

        $config = new XrayConfig((array)$settings);
        $config->update($update);
        return $this->set($config->settings());
    }

    /**
     * Save a complete Xray config.
     *
     * @param array|object|string $settings Complete config.
     * @param string|null $outbound_test_url Optional outbound test URL.
     * @return Result
     */
    public function set(array|object|string $settings, ?string $outbound_test_url = null): Result
    {
        $form = ['xraySetting' => is_string($settings) ? $settings : (json_encode($settings) ?: '{}')];
        if (!is_null($outbound_test_url)) {
            $form['outboundTestUrl'] = $outbound_test_url;
        }
        return $this->_request(Request::post('update', form_params: $form));
    }

    /**
     * Return the built-in default Xray config shipped with the panel.
     *
     * @return Result
     */
    public function default_config(): Result
    {
        return $this->_request(Request::get('getDefaultJsonConfig'));
    }

    /**
     * Traffic statistics for every outbound.
     *
     * @return Result
     */
    public function outbounds_traffic(): Result
    {
        return $this->_request(Request::get('getOutboundsTraffic'));
    }

    /**
     * Most recent Xray process stdout/stderr output.
     *
     * @return Result
     */
    public function result(): Result
    {
        return $this->_request(Request::get('getXrayResult'));
    }

    /**
     * Manage the Cloudflare WARP integration.
     *
     * @param string $action `data`, `del`, `config`, `reg`, `regMasque`, `changeIp`, `license` or `interval`.
     * @param array $params Action specific form fields.
     * @return Result
     */
    public function warp(string $action, array $params = []): Result
    {
        return $this->_request(Request::post("warp/$action", form_params: $params));
    }

    /**
     * Manage the NordVPN integration.
     *
     * @param string $action `countries`, `servers`, `reg`, `setKey`, `data` or `del`.
     * @param array $params Action specific form fields.
     * @return Result
     */
    public function nord(string $action, array $params = []): Result
    {
        return $this->_request(Request::post("nord/$action", form_params: $params));
    }

    /**
     * Manage the PIA WireGuard integration.
     *
     * @param string $action `countries`, `servers`, `reg`, `data`, `del` or `addKey`.
     * @param array $params Action specific form fields.
     * @return Result
     */
    public function pia(string $action, array $params = []): Result
    {
        return $this->_request(Request::post("pia/$action", form_params: $params));
    }

    /**
     * Reset the traffic counter of one outbound.
     *
     * @param string $tag Outbound tag.
     * @return Result
     */
    public function reset_outbounds_traffic(string $tag): Result
    {
        return $this->_request(Request::post('resetOutboundsTraffic', form_params: ['tag' => $tag]));
    }

    /**
     * Test one outbound configuration.
     *
     * @param array|object $outbound Outbound to test.
     * @param array $all_outbounds All outbounds, used to resolve `dialerProxy` chains.
     * @param string $mode `tcp`, `real` or empty for a full HTTP probe.
     * @return Result
     */
    public function test_outbound(array|object $outbound, array $all_outbounds = [], string $mode = 'tcp'): Result
    {
        return $this->_request(Request::post('testOutbound', form_params: [
            'outbound' => json_encode($outbound) ?: '{}',
            'allOutbounds' => json_encode($all_outbounds) ?: '[]',
            'mode' => $mode,
        ]));
    }

    /**
     * Test up to 50 outbounds through one shared temporary Xray instance.
     *
     * @param array $outbounds Outbounds to test.
     * @param array $all_outbounds All outbounds, used to resolve `dialerProxy` chains.
     * @param string $mode `tcp`, `real` or empty.
     * @return Result
     */
    public function test_outbounds(array $outbounds, array $all_outbounds = [], string $mode = 'tcp'): Result
    {
        return $this->_request(Request::post('testOutbounds', form_params: [
            'outbounds' => json_encode($outbounds) ?: '[]',
            'allOutbounds' => json_encode($all_outbounds) ?: '[]',
            'mode' => $mode,
        ]));
    }

    /**
     * Live state of routing balancers.
     *
     * @param string|array $tags Balancer tag or list of tags.
     * @return Result
     */
    public function balancer_status(string|array $tags): Result
    {
        return $this->_request(Request::post('balancerStatus', form_params: [
            'tags' => is_array($tags) ? implode(',', $tags) : $tags,
        ]));
    }

    /**
     * Force a balancer to always pick one outbound.
     *
     * @param string $tag Balancer tag.
     * @param string $target Outbound tag, empty clears the override.
     * @return Result
     */
    public function balancer_override(string $tag, string $target = ''): Result
    {
        return $this->_request(Request::post('balancerOverride', form_params: [
            'tag' => $tag,
            'target' => $target,
        ]));
    }

    /**
     * Ask the running router which outbound it would pick for a synthetic connection.
     *
     * @param array $route Either `domain` or `ip` is required; other keys are optional.
     * @return Result
     */
    public function route_test(array $route): Result
    {
        return $this->_request(Request::post('routeTest', form_params: $route));
    }

    /**
     * List the geo databases found in the Xray asset directory.
     *
     * @return Result
     */
    public function geodata_files(): Result
    {
        return $this->_request(Request::get('geodata/files'));
    }

    /**
     * One page of a geo database's categories.
     *
     * @param string $file Database file name, e.g. `geosite.dat`.
     * @param string|null $q Case-insensitive substring filter.
     * @param int $offset Rows to skip.
     * @param int|null $limit Rows to return (capped at 500).
     * @return Result
     */
    public function geodata_categories(string $file, ?string $q = null, int $offset = 0, ?int $limit = null): Result
    {
        return $this->_request(Request::get('geodata/categories', query: [
            'file' => $file,
            'q' => $q,
            'offset' => $offset,
            'limit' => $limit,
        ]));
    }

    /**
     * One page of the rules inside a geo category.
     *
     * @param string $file Database file name.
     * @param string $code Category code.
     * @param string|null $q Case-insensitive substring filter.
     * @param int $offset Rows to skip.
     * @param int|null $limit Rows to return (capped at 500).
     * @return Result
     */
    public function geodata_entries(string $file, string $code, ?string $q = null, int $offset = 0, ?int $limit = null): Result
    {
        return $this->_request(Request::get('geodata/entries', query: [
            'file' => $file,
            'code' => $code,
            'q' => $q,
            'offset' => $offset,
            'limit' => $limit,
        ]));
    }

    /**
     * Validate routing tokens against the databases on disk.
     *
     * @param string|array $tokens Comma-separated tokens, or a list of tokens.
     * @param string $kind `ip` for IP rules, anything else for domain rules.
     * @return Result
     */
    public function geodata_validate(string|array $tokens, string $kind = 'site'): Result
    {
        return $this->_request(Request::post('geodata/validate', form_params: [
            'tokens' => is_array($tokens) ? implode(',', $tokens) : $tokens,
            'kind' => $kind,
        ]));
    }

    /**
     * List every outbound subscription.
     *
     * @return Result
     */
    public function outbound_subs(): Result
    {
        return $this->_request(Request::get('outbound-subs'));
    }

    /**
     * Create an outbound subscription.
     *
     * @param string $url Subscription URL.
     * @param array $options Optional form fields (`remark`, `tagPrefix`, `userAgent`, ...).
     * @return Result
     */
    public function create_outbound_sub(string $url, array $options = []): Result
    {
        return $this->_request(Request::post('outbound-subs', form_params: ['url' => $url] + $options));
    }

    /**
     * Update an outbound subscription.
     *
     * @param int $id Subscription id.
     * @param string $url Subscription URL.
     * @param array $options Optional form fields.
     * @return Result
     */
    public function update_outbound_sub(int $id, string $url, array $options = []): Result
    {
        return $this->_request(Request::post("outbound-subs/$id", form_params: ['url' => $url] + $options));
    }

    /**
     * Delete an outbound subscription.
     *
     * @param int $id Subscription id.
     * @return Result
     */
    public function delete_outbound_sub(int $id): Result
    {
        return $this->_request(Request::post("outbound-subs/$id/del"));
    }

    /**
     * Force an immediate refresh of an outbound subscription.
     *
     * @param int $id Subscription id.
     * @return Result
     */
    public function refresh_outbound_sub(int $id): Result
    {
        return $this->_request(Request::post("outbound-subs/$id/refresh"));
    }

    /**
     * Move an outbound subscription one step up or down in priority.
     *
     * @param int $id Subscription id.
     * @param string $direction `up` raises priority, anything else lowers it.
     * @return Result
     */
    public function move_outbound_sub(int $id, string $direction = 'up'): Result
    {
        return $this->_request(Request::post("outbound-subs/$id/move", form_params: ['dir' => $direction]));
    }

    /**
     * Preview a subscription URL without persisting it.
     *
     * @param string $url Subscription URL.
     * @param string|null $user_agent Custom User-Agent.
     * @param bool $allow_private Allow private/internal URLs.
     * @param bool $allow_insecure Skip TLS verification.
     * @return Result
     */
    public function parse_outbound_sub(
        string $url,
        ?string $user_agent = null,
        bool $allow_private = false,
        bool $allow_insecure = false
    ): Result
    {
        return $this->_request(Request::post('outbound-subs/parse', form_params: [
            'url' => $url,
            'userAgent' => $user_agent,
            'allowPrivate' => $allow_private ? 'true' : 'false',
            'allowInsecure' => $allow_insecure ? 'true' : 'false',
        ]));
    }

    /**
     * Send one Xray request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->xray($request);
        return $result;
    }
}
