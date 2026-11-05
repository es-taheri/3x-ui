<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;

/**
 * Node endpoints for managing remote 3x-ui panels from a central panel.
 *
 * ```php
 * $xui->nodes->add([
 *     'name' => 'de-fra-1',
 *     'scheme' => 'https',
 *     'address' => 'node1.example.com',
 *     'port' => 2053,
 *     'basePath' => '/',
 *     'apiToken' => '...',
 *     'enable' => true,
 * ]);
 * ```
 */
class Nodes
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
     * List every configured node with its health and last heartbeat patch.
     *
     * @return Result
     */
    public function list(): Result
    {
        return $this->_request(Request::get('list'));
    }

    /**
     * Fetch one node by id.
     *
     * @param int $id Node id.
     * @return Result
     */
    public function get(int $id): Result
    {
        return $this->_request(Request::get("get/$id"));
    }

    /**
     * Register a new remote node.
     *
     * @param array $node Node payload (`name`, `scheme`, `address`, `port`, `basePath`, `apiToken`, ...).
     * @return Result
     */
    public function add(array $node): Result
    {
        return $this->_request(Request::post('add', $node));
    }

    /**
     * Replace a node's connection details.
     *
     * `apiToken` is write-only: omit it (or send an empty string) to keep the
     * stored token, or set `clearApiToken` to true to remove it.
     *
     * @param int $id Node id.
     * @param array $node Node payload.
     * @return Result
     */
    public function update(int $id, array $node): Result
    {
        return $this->_request(Request::post("update/$id", $node));
    }

    /**
     * Delete a node.
     *
     * @param int $id Node id.
     * @return Result
     */
    public function delete(int $id): Result
    {
        return $this->_request(Request::post("del/$id"));
    }

    /**
     * Pause or resume traffic sync with a node.
     *
     * @param int $id Node id.
     * @param bool $enable New flag.
     * @return Result
     */
    public function set_enable(int $id, bool $enable): Result
    {
        return $this->_request(Request::post("setEnable/$id", ['enable' => $enable]));
    }

    /**
     * Probe unsaved node connection details.
     *
     * @param array $node Node payload.
     * @return Result
     */
    public function test(array $node): Result
    {
        return $this->_request(Request::post('test', $node));
    }

    /**
     * Fetch the leaf certificate SHA-256 of a node without verifying it.
     *
     * @param array $node Node payload.
     * @return Result
     */
    public function cert_fingerprint(array $node): Result
    {
        return $this->_request(Request::post('certFingerprint', $node));
    }

    /**
     * List the inbounds available for selective import on a remote node.
     *
     * @param array $node Node payload.
     * @return Result
     */
    public function inbounds(array $node): Result
    {
        return $this->_request(Request::post('inbounds', $node));
    }

    /**
     * Probe an existing node and update its cached health state.
     *
     * @param int $id Node id.
     * @return Result
     */
    public function probe(int $id): Result
    {
        return $this->_request(Request::post("probe/$id"));
    }

    /**
     * Trigger the panel self-updater on the given nodes.
     *
     * @param array $ids Node ids.
     * @param bool $dev Move the nodes to the dev channel.
     * @return Result
     */
    public function update_panel(array $ids, bool $dev = false): Result
    {
        return $this->_request(Request::post('updatePanel', [
            'ids' => array_values(array_map('intval', $ids)),
            'dev' => $dev,
        ]));
    }

    /**
     * Aggregated metric history for one node.
     *
     * @param int $id Node id.
     * @param string $metric `cpu` or `mem`.
     * @param int $bucket Bucket size in seconds (2, 30, 60, 120, 180, 300).
     * @return Result
     */
    public function history(int $id, string $metric, int $bucket): Result
    {
        return $this->_request(Request::get("history/$id/$metric/$bucket"));
    }

    /**
     * This panel's node-auth CA certificate (PEM).
     *
     * @return Result
     */
    public function mtls_ca(): Result
    {
        return $this->_request(Request::post('mtls/ca'));
    }

    /**
     * Set the CA certificate trusted for incoming node API clients.
     *
     * @param string $ca_cert PEM certificate, empty to disable.
     * @return Result
     */
    public function mtls_trust_ca(string $ca_cert): Result
    {
        return $this->_request(Request::post('mtls/trustCA', ['caCert' => $ca_cert]));
    }

    /**
     * Validate the stored master mTLS client credential and rebuild transports.
     *
     * @return Result
     */
    public function mtls_reload_client(): Result
    {
        return $this->_request(Request::post('mtls/reloadClient'));
    }

    /**
     * Fetch a node's own web TLS certificate/key paths.
     *
     * @param int $id Node id.
     * @return Result
     */
    public function web_cert(int $id): Result
    {
        return $this->_request(Request::get("webCert/$id"));
    }

    /**
     * Send one node request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->nodes($request);
        return $result;
    }
}
