<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;

/**
 * Host override endpoints (the replacement of the legacy `externalProxy` list).
 *
 * A host group attaches one or more host names, ports and TLS settings to one
 * or more inbounds. Each enabled host renders one extra subscription link.
 *
 * Host payloads are plain arrays that mirror the panel `HostGroup` schema:
 * `inboundIds`, `hosts`, `port`, `security`, `remark`, `tags`, `sni`, ...
 */
class Hosts
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
     * List every host group across all inbounds.
     *
     * @return Result
     */
    public function list(): Result
    {
        return $this->_request(Request::get('list'));
    }

    /**
     * List every raw host row with its numeric id.
     *
     * @return Result
     */
    public function raw(): Result
    {
        return $this->_request(Request::get('raw'));
    }

    /**
     * Fetch one host group by group id.
     *
     * @param string $group_id Host group id.
     * @return Result
     */
    public function get(string $group_id): Result
    {
        return $this->_request(Request::get('get/' . rawurlencode($group_id)));
    }

    /**
     * Fetch one inbound's hosts grouped by host group.
     *
     * @param int $inbound_id Inbound id.
     * @return Result
     */
    public function by_inbound(int $inbound_id): Result
    {
        return $this->_request(Request::get("byInbound/$inbound_id"));
    }

    /**
     * Distinct sorted set of tags used across all hosts.
     *
     * @return Result
     */
    public function tags(): Result
    {
        return $this->_request(Request::get('tags'));
    }

    /**
     * Create a host group.
     *
     * @param array $host Host group payload.
     * @return Result
     */
    public function add(array $host): Result
    {
        return $this->_request(Request::post('add', $host));
    }

    /**
     * Replace a host group.
     *
     * @param string $group_id Host group id.
     * @param array $host Host group payload.
     * @return Result
     */
    public function update(string $group_id, array $host): Result
    {
        return $this->_request(Request::post('update/' . rawurlencode($group_id), $host));
    }

    /**
     * Delete a host group.
     *
     * @param string $group_id Host group id.
     * @return Result
     */
    public function delete(string $group_id): Result
    {
        return $this->_request(Request::post('del/' . rawurlencode($group_id)));
    }

    /**
     * Enable or disable a host group.
     *
     * @param string $group_id Host group id.
     * @param bool $enable New flag.
     * @return Result
     */
    public function set_enable(string $group_id, bool $enable): Result
    {
        return $this->_request(Request::post('setEnable/' . rawurlencode($group_id), ['enable' => $enable]));
    }

    /**
     * Reorder host groups by the position of each group id.
     *
     * @param array $group_ids Ordered host group ids.
     * @return Result
     */
    public function reorder(array $group_ids): Result
    {
        return $this->_request(Request::post('reorder', ['ids' => array_values($group_ids)]));
    }

    /**
     * Add a host group to one or more inbounds.
     *
     * @param array $host Host group payload.
     * @return Result
     */
    public function bulk_add(array $host): Result
    {
        return $this->_request(Request::post('bulk/add', $host));
    }

    /**
     * Enable or disable many host groups.
     *
     * @param array $group_ids Host group ids.
     * @param bool $enable New flag.
     * @return Result
     */
    public function bulk_set_enable(array $group_ids, bool $enable): Result
    {
        return $this->_request(Request::post('bulk/setEnable', [
            'ids' => array_values($group_ids),
            'enable' => $enable,
        ]));
    }

    /**
     * Delete many host groups.
     *
     * @param array $group_ids Host group ids.
     * @return Result
     */
    public function bulk_delete(array $group_ids): Result
    {
        return $this->_request(Request::post('bulk/del', ['ids' => array_values($group_ids)]));
    }

    /**
     * Send one hosts request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->hosts($request);
        return $result;
    }
}
