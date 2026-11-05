<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;

/**
 * Client for the panel's subscription server (separate HTTP port, default 2096).
 *
 * Build a dedicated `Request` whose base URI points at the subscription server.
 * The path prefixes are configured in Settings → Subscription; pass the same
 * prefix (for example `/sub/`) to the methods below.
 *
 * ```php
 * $request = new Request(Request::config('panel.example.com', 2096));
 * $subscriptions = new Subscriptions($request);
 * $subscriptions->links('/sub/', 'abc123');
 * ```
 */
class Subscriptions
{
    /**
     * @param Request $request Shared HTTP transport pointed at the subscription server.
     */
    public function __construct(
        /** @var Request Shared HTTP transport. */
        private readonly Request $request
    )
    {
    }

    /**
     * Fetch the base64 encoded subscription links.
     *
     * @param string $sub_path Configured subscription path prefix, e.g. `/sub/`.
     * @param string $sub_id Client subscription id.
     * @return Result Raw base64 body is available through {@see Result::raw()}.
     */
    public function links(string $sub_path, string $sub_id): Result
    {
        return $this->_request(Request::get($this->path($sub_path, $sub_id)));
    }

    /**
     * Fetch the subscription links and decode the base64 body.
     *
     * @param string $sub_path Configured subscription path prefix.
     * @param string $sub_id Client subscription id.
     * @return array<int,string> Decoded link list (empty on failure).
     */
    public function links_array(string $sub_path, string $sub_id): array
    {
        $result = $this->links($sub_path, $sub_id);
        $raw = $result->raw();
        if (is_null($raw)) {
            return [];
        }
        $decoded = base64_decode(trim($raw), true);
        if ($decoded === false) {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', trim($decoded)) ?: [];
        return array_values(array_filter(array_map('trim', $lines), static fn(string $line): bool => $line !== ''));
    }

    /**
     * Fetch the subscription status view-model as JSON (`?format=info`).
     *
     * @param string $sub_path Configured subscription path prefix.
     * @param string $sub_id Client subscription id.
     * @return Result
     */
    public function info(string $sub_path, string $sub_id): Result
    {
        return $this->_request(Request::get($this->path($sub_path, $sub_id), query: ['format' => 'info']));
    }

    /**
     * Fetch the HTML info page instead of the links.
     *
     * @param string $sub_path Configured subscription path prefix.
     * @param string $sub_id Client subscription id.
     * @return Result
     */
    public function page(string $sub_path, string $sub_id): Result
    {
        return $this->_request(Request::get($this->path($sub_path, $sub_id), query: ['html' => 1]));
    }

    /**
     * HWID device-slot counters for a subscription (read-only).
     *
     * @param string $sub_path Configured subscription path prefix.
     * @param string $sub_id Client subscription id.
     * @return Result
     */
    public function hwid_status(string $sub_path, string $sub_id): Result
    {
        return $this->_request(Request::get($this->path($sub_path, $sub_id, 'hwid-status')));
    }

    /**
     * Fetch the JSON (Xray outbounds) subscription.
     *
     * @param string $json_path Configured JSON subscription path prefix.
     * @param string $sub_id Client subscription id.
     * @return Result
     */
    public function json(string $json_path, string $sub_id): Result
    {
        return $this->_request(Request::get($this->path($json_path, $sub_id)));
    }

    /**
     * Fetch the Clash/Mihomo YAML subscription.
     *
     * @param string $clash_path Configured Clash subscription path prefix.
     * @param string $sub_id Client subscription id.
     * @return Result
     */
    public function clash(string $clash_path, string $sub_id): Result
    {
        return $this->_request(Request::get($this->path($clash_path, $sub_id)));
    }

    /**
     * Build a subscription route from a configured prefix and id.
     *
     * @param string $prefix Configured path prefix.
     * @param string $sub_id Client subscription id.
     * @param string|null $suffix Optional trailing segment (e.g. `hwid-status`).
     * @return string
     */
    private function path(string $prefix, string $sub_id, ?string $suffix = null): string
    {
        $route = rtrim($prefix, '/') . '/' . rawurlencode($sub_id);
        if (!is_null($suffix)) {
            $route .= '/' . $suffix;
        }
        return $route;
    }

    /**
     * Send one subscription request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->root($request);
        return $result;
    }
}
