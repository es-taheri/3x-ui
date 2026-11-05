<?php

namespace XUI;

use XUI\Clients\Client;
use XUI\Handler\Request;
use XUI\Handler\Result;

/**
 * Client endpoints of the 3x-ui panel API.
 *
 * A client is a first-class entity that can be attached to several inbounds.
 *
 * ```php
 * $client = (new Client('alice@example.com'))->quota_gb(50)->expiry_in_days(30);
 * $xui->clients->create($client, [3, 5]);
 * ```
 */
class Clients
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
     * List every client with its attached inbounds and traffic record.
     *
     * @param string|null $search Substring matched against email/subId/comment/uuid/password/auth/tgId.
     * @param string|null $filter CSV status buckets (`online`, `active`, `deactive`, `depleted`, `expiring`).
     * @param string|null $protocol CSV protocol names.
     * @param string|null $inbound CSV inbound ids.
     * @return Result
     */
    public function list(
        ?string $search = null,
        ?string $filter = null,
        ?string $protocol = null,
        ?string $inbound = null
    ): Result
    {
        return $this->_request(Request::get('list', query: [
            'search' => $search,
            'filter' => $filter,
            'protocol' => $protocol,
            'inbound' => $inbound,
        ]));
    }

    /**
     * Server-side filtered, sorted and paginated client list.
     *
     * @param int $page 1-indexed page number.
     * @param int $page_size Rows per page, capped at 200.
     * @param string|null $search Substring search.
     * @param string|null $filter CSV status buckets.
     * @param string|null $protocol CSV protocol names.
     * @param string|null $inbound CSV inbound ids.
     * @param string|null $sort Sort key.
     * @param string|null $order `ascend` or `descend`.
     * @param int|null $expiry_from Inclusive minimum expiry (Unix milliseconds).
     * @param int|null $expiry_to Inclusive maximum expiry (Unix milliseconds).
     * @param int|null $usage_from Inclusive minimum usage in bytes.
     * @param int|null $usage_to Inclusive maximum usage in bytes.
     * @param string|null $auto_renew `on` or `off`.
     * @param string|null $has_tg_id `yes` or `no`.
     * @param string|null $has_comment `yes` or `no`.
     * @param string|null $group CSV group names.
     * @return Result
     */
    public function paged(
        int $page = 1,
        int $page_size = 25,
        ?string $search = null,
        ?string $filter = null,
        ?string $protocol = null,
        ?string $inbound = null,
        ?string $sort = null,
        ?string $order = null,
        ?int $expiry_from = null,
        ?int $expiry_to = null,
        ?int $usage_from = null,
        ?int $usage_to = null,
        ?string $auto_renew = null,
        ?string $has_tg_id = null,
        ?string $has_comment = null,
        ?string $group = null
    ): Result
    {
        return $this->_request(Request::get('list/paged', query: [
            'page' => $page,
            'pageSize' => $page_size,
            'search' => $search,
            'filter' => $filter,
            'protocol' => $protocol,
            'inbound' => $inbound,
            'sort' => $sort,
            'order' => $order,
            'expiryFrom' => $expiry_from,
            'expiryTo' => $expiry_to,
            'usageFrom' => $usage_from,
            'usageTo' => $usage_to,
            'autoRenew' => $auto_renew,
            'hasTgId' => $has_tg_id,
            'hasComment' => $has_comment,
            'group' => $group,
        ]));
    }

    /**
     * Fetch one or many clients by email.
     *
     * @param string|array $email_or_emails Client email or list of emails.
     * @return Result|Result[]
     */
    public function get(string|array $email_or_emails): Result|array
    {
        if (is_array($email_or_emails)) {
            $requests = [];
            foreach ($email_or_emails as $email) {
                $requests[] = Request::get('get/' . rawurlencode($email));
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::get('get/' . rawurlencode($email_or_emails)));
    }

    /**
     * Fetch every client sharing a subscription id.
     *
     * @param string $sub_id Subscription id.
     * @return Result
     */
    public function get_by_sub_id(string $sub_id): Result
    {
        return $this->_request(Request::get('get/subId/' . rawurlencode($sub_id)));
    }

    /**
     * Fetch every client sharing a Telegram user id.
     *
     * @param int $tg_id Telegram user id.
     * @return Result
     */
    public function get_by_tg_id(int $tg_id): Result
    {
        return $this->_request(Request::get("get/tgId/$tg_id"));
    }

    /**
     * Create a client and attach it to one or more inbounds.
     *
     * @param Client|array $client Client definition.
     * @param array $inbound_ids Target inbound ids.
     * @return Result
     */
    public function create(Client|array $client, array $inbound_ids): Result
    {
        return $this->_request(Request::post('add', [
            'client' => $client instanceof Client ? $client->to_array() : $client,
            'inboundIds' => array_values(array_map('intval', $inbound_ids)),
        ]));
    }

    /**
     * Create many clients sequentially.
     *
     * @param array $clients List of `['client' => Client|array, 'inboundIds' => int[]]` arrays.
     * @return array
     */
    public function bulk_create(array $clients): Result
    {
        $payload = [];
        foreach ($clients as $item) {
            $client = $item['client'] ?? null;
            $inbound_ids = (array)($item['inboundIds'] ?? $item['inbound_ids'] ?? []);
            if ($client instanceof Client) {
                $client = $client->to_array();
            }
            if (!is_array($client)) {
                continue;
            }
            $payload[] = [
                'client' => $client,
                'inboundIds' => array_values(array_map('intval', $inbound_ids)),
            ];
        }
        return $this->_request(Request::post('bulkCreate', $payload));
    }

    /**
     * Replace a client by email.
     *
     * The server replaces the row, so supply every field you want to keep.
     *
     * @param string $email Current client email.
     * @param Client|array $client New client payload.
     * @return Result
     */
    public function update(string $email, Client|array $client): Result
    {
        return $this->_request(Request::post(
            'update/' . rawurlencode($email),
            $client instanceof Client ? $client->to_array() : $client
        ));
    }

    /**
     * Preview the auto-renewal schedule without saving anything.
     *
     * @param array $request `expiryTime`, `reset`, `resetDay`, `resetWeekday`, `resetMax`, `resetCount`.
     * @return Result
     */
    public function renewal_preview(array $request): Result
    {
        return $this->_request(Request::post('renewalPreview', $request));
    }

    /**
     * Delete one client or many in one call.
     *
     * @param string|array $email_or_emails Client email or list of emails.
     * @param bool $keep_traffic Keep the traffic rows after deletion.
     * @return Result
     */
    public function delete(string|array $email_or_emails, bool $keep_traffic = false): Result
    {
        if (is_array($email_or_emails)) {
            return $this->_request(Request::post('bulkDel', [
                'emails' => array_values($email_or_emails),
                'keepTraffic' => $keep_traffic,
            ]));
        }
        return $this->_request(Request::post(
            'del/' . rawurlencode($email_or_emails),
            query: ['keepTraffic' => $keep_traffic ? 1 : 0]
        ));
    }

    /**
     * Attach one or many clients to one or many inbounds.
     *
     * @param string|array $email_or_emails Client email or list of emails.
     * @param array $inbound_ids Target inbound ids.
     * @return Result
     */
    public function attach(string|array $email_or_emails, array $inbound_ids): Result
    {
        if (is_array($email_or_emails)) {
            return $this->_request(Request::post('bulkAttach', [
                'emails' => array_values($email_or_emails),
                'inboundIds' => array_values(array_map('intval', $inbound_ids)),
            ]));
        }
        return $this->_request(Request::post(
            rawurlencode($email_or_emails) . '/attach',
            ['inboundIds' => array_values(array_map('intval', $inbound_ids))]
        ));
    }

    /**
     * Detach one or many clients from one or many inbounds.
     *
     * @param string|array $email_or_emails Client email or list of emails.
     * @param array $inbound_ids Inbound ids to detach.
     * @return Result
     */
    public function detach(string|array $email_or_emails, array $inbound_ids): Result
    {
        if (is_array($email_or_emails)) {
            return $this->_request(Request::post('bulkDetach', [
                'emails' => array_values($email_or_emails),
                'inboundIds' => array_values(array_map('intval', $inbound_ids)),
            ]));
        }
        return $this->_request(Request::post(
            rawurlencode($email_or_emails) . '/detach',
            ['inboundIds' => array_values(array_map('intval', $inbound_ids))]
        ));
    }

    /**
     * Replace a client's external links and external subscriptions.
     *
     * @param string $email Client email.
     * @param array $external_links Full replacement list.
     * @return Result
     */
    public function external_links(string $email, array $external_links): Result
    {
        return $this->_request(Request::post(
            rawurlencode($email) . '/externalLinks',
            ['externalLinks' => $external_links]
        ));
    }

    /**
     * Zero the up/down counters of one client or many.
     *
     * @param string|array $email_or_emails Client email or list of emails.
     * @return Result
     */
    public function reset_traffic(string|array $email_or_emails): Result
    {
        if (is_array($email_or_emails)) {
            return $this->_request(Request::post('bulkResetTraffic', [
                'emails' => array_values($email_or_emails),
            ]));
        }
        return $this->_request(Request::post('resetTraffic/' . rawurlencode($email_or_emails)));
    }

    /**
     * Reset the counters of every client.
     *
     * @return Result
     */
    public function reset_all_traffics(): Result
    {
        return $this->_request(Request::post('resetAllTraffics'));
    }

    /**
     * Delete every depleted or expired client.
     *
     * @return Result
     */
    public function delete_depleted(): Result
    {
        return $this->_request(Request::post('delDepleted'));
    }

    /**
     * Delete every client that is not attached to any inbound.
     *
     * @return Result
     */
    public function delete_orphans(): Result
    {
        return $this->_request(Request::post('delOrphans'));
    }

    /**
     * Export every client as a re-importable array.
     *
     * @return Result
     */
    public function export(): Result
    {
        return $this->_request(Request::get('export'));
    }

    /**
     * Import clients previously produced by {@see export()}.
     *
     * @param string|array $data JSON string or the array returned by export.
     * @return Result
     */
    public function import(string|array $data): Result
    {
        return $this->_request(Request::post('import', [
            'data' => is_string($data) ? $data : (json_encode($data) ?: '[]'),
        ]));
    }

    /**
     * Shift expiry and/or traffic quota for many clients.
     *
     * @param array $emails Target emails.
     * @param int $add_days Days to add (may be negative).
     * @param int $add_bytes Bytes to add (may be negative).
     * @param string|null $flow `none`, `xtls-rprx-vision` or `xtls-rprx-vision-udp443`.
     * @param int|null $limit_hwid Maximum registered devices.
     * @param string|null $ad_tag MTProto sponsor tag, `none` clears it.
     * @return Result
     */
    public function bulk_adjust(
        array $emails,
        int $add_days = 0,
        int $add_bytes = 0,
        ?string $flow = null,
        ?int $limit_hwid = null,
        ?string $ad_tag = null
    ): Result
    {
        $body = [
            'emails' => array_values($emails),
            'addDays' => $add_days,
            'addBytes' => $add_bytes,
        ];
        if (!is_null($flow)) $body['flow'] = $flow;
        if (!is_null($limit_hwid)) $body['limitHwid'] = $limit_hwid;
        if (!is_null($ad_tag)) $body['adTag'] = $ad_tag;
        return $this->_request(Request::post('bulkAdjust', $body));
    }

    /**
     * Enable many clients.
     *
     * @param array $emails Target emails.
     * @return Result
     */
    public function bulk_enable(array $emails): Result
    {
        return $this->_request(Request::post('bulkEnable', ['emails' => array_values($emails)]));
    }

    /**
     * Disable many clients.
     *
     * @param array $emails Target emails.
     * @return Result
     */
    public function bulk_disable(array $emails): Result
    {
        return $this->_request(Request::post('bulkDisable', ['emails' => array_values($emails)]));
    }

    /**
     * Delete many clients.
     *
     * @param array $emails Target emails.
     * @param bool $keep_traffic Keep traffic rows.
     * @return Result
     */
    public function bulk_delete(array $emails, bool $keep_traffic = false): Result
    {
        return $this->_request(Request::post('bulkDel', [
            'emails' => array_values($emails),
            'keepTraffic' => $keep_traffic,
        ]));
    }

    /**
     * Attach many clients to many inbounds.
     *
     * @param array $emails Target emails.
     * @param array $inbound_ids Target inbound ids.
     * @return Result
     */
    public function bulk_attach(array $emails, array $inbound_ids): Result
    {
        return $this->_request(Request::post('bulkAttach', [
            'emails' => array_values($emails),
            'inboundIds' => array_values(array_map('intval', $inbound_ids)),
        ]));
    }

    /**
     * Detach many clients from many inbounds.
     *
     * @param array $emails Target emails.
     * @param array $inbound_ids Inbound ids to detach.
     * @return Result
     */
    public function bulk_detach(array $emails, array $inbound_ids): Result
    {
        return $this->_request(Request::post('bulkDetach', [
            'emails' => array_values($emails),
            'inboundIds' => array_values(array_map('intval', $inbound_ids)),
        ]));
    }

    /**
     * List client groups with their member counts.
     *
     * @return Result
     */
    public function groups(): Result
    {
        return $this->_request(Request::get('groups'));
    }

    /**
     * List the emails of one group.
     *
     * @param string $name Group name.
     * @return Result
     */
    public function group_emails(string $name): Result
    {
        return $this->_request(Request::get('groups/' . rawurlencode($name) . '/emails'));
    }

    /**
     * Create an empty group.
     *
     * @param string $name Group name.
     * @return Result
     */
    public function group_create(string $name): Result
    {
        return $this->_request(Request::post('groups/create', ['name' => $name]));
    }

    /**
     * Rename a group.
     *
     * @param string $old_name Current group name.
     * @param string $new_name New group name.
     * @return Result
     */
    public function group_rename(string $old_name, string $new_name): Result
    {
        return $this->_request(Request::post('groups/rename', [
            'oldName' => $old_name,
            'newName' => $new_name,
        ]));
    }

    /**
     * Delete a group and clear its label from every client.
     *
     * @param string $name Group name.
     * @return Result
     */
    public function group_delete(string $name): Result
    {
        return $this->_request(Request::post('groups/delete', ['name' => $name]));
    }

    /**
     * Add many clients to a group.
     *
     * @param array $emails Target emails.
     * @param string $group Group name.
     * @return Result
     */
    public function group_bulk_add(array $emails, string $group): Result
    {
        return $this->_request(Request::post('groups/bulkAdd', [
            'emails' => array_values($emails),
            'group' => $group,
        ]));
    }

    /**
     * Clear the group label of many clients.
     *
     * @param array $emails Target emails.
     * @return Result
     */
    public function group_bulk_remove(array $emails): Result
    {
        return $this->_request(Request::post('groups/bulkRemove', [
            'emails' => array_values($emails),
        ]));
    }

    /**
     * Reset only the group-level traffic counter.
     *
     * @param string $name Group name.
     * @return Result
     */
    public function group_reset_traffic(string $name): Result
    {
        return $this->_request(Request::post('groups/resetTraffic', ['name' => $name]));
    }

    /**
     * Adjust one client's upload/download counters.
     *
     * @param string $email Client email.
     * @param int $upload Upload counter in bytes.
     * @param int $download Download counter in bytes.
     * @return Result
     */
    public function update_traffic(string $email, int $upload, int $download): Result
    {
        return $this->_request(Request::post('updateTraffic/' . rawurlencode($email), [
            'upload' => $upload,
            'download' => $download,
        ]));
    }

    /**
     * List the source IPs seen for one or many clients.
     *
     * @param string|array $email_or_emails Client email or list of emails.
     * @return Result|Result[]
     */
    public function ips(string|array $email_or_emails): Result|array
    {
        if (is_array($email_or_emails)) {
            $requests = [];
            foreach ($email_or_emails as $email) {
                $requests[] = Request::post('ips/' . rawurlencode($email));
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::post('ips/' . rawurlencode($email_or_emails)));
    }

    /**
     * Reset the recorded IP list of one or many clients.
     *
     * @param string|array $email_or_emails Client email or list of emails.
     * @return Result|Result[]
     */
    public function clear_ips(string|array $email_or_emails): Result|array
    {
        if (is_array($email_or_emails)) {
            $requests = [];
            foreach ($email_or_emails as $email) {
                $requests[] = Request::post('clearIps/' . rawurlencode($email));
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::post('clearIps/' . rawurlencode($email_or_emails)));
    }

    /**
     * List the registered HWID devices of a client.
     *
     * @param string $email Client email.
     * @return Result
     */
    public function hwids(string $email): Result
    {
        return $this->_request(Request::post('hwids/' . rawurlencode($email)));
    }

    /**
     * Remove every registered HWID device of a client.
     *
     * @param string $email Client email.
     * @return Result
     */
    public function delete_hwids(string $email): Result
    {
        return $this->_request(Request::delete('hwids/' . rawurlencode($email)));
    }

    /**
     * Remove one registered HWID device.
     *
     * @param string $email Client email.
     * @param int $id Device id.
     * @return Result
     */
    public function delete_hwid(string $email, int $id): Result
    {
        return $this->_request(Request::delete('hwids/' . rawurlencode($email) . "/$id"));
    }

    /**
     * Emails of currently connected clients.
     *
     * @return Result
     */
    public function onlines(): Result
    {
        return $this->_request(Request::post('onlines'));
    }

    /**
     * Online emails grouped by the GUID of the node that hosts them.
     *
     * @return Result
     */
    public function onlines_by_guid(): Result
    {
        return $this->_request(Request::post('onlinesByGuid'));
    }

    /**
     * Per-client source IPs grouped by the GUID of the observing node.
     *
     * @return Result
     */
    public function client_ips_by_guid(): Result
    {
        return $this->_request(Request::post('clientIpsByGuid'));
    }

    /**
     * Inbound tags that carried traffic, grouped by node GUID.
     *
     * @return Result
     */
    public function active_inbounds(): Result
    {
        return $this->_request(Request::post('activeInbounds'));
    }

    /**
     * Map of client email to last-seen Unix timestamp.
     *
     * @return Result
     */
    public function last_online(): Result
    {
        return $this->_request(Request::post('lastOnline'));
    }

    /**
     * Traffic counters of one or many clients.
     *
     * @param string|array $email_or_emails Client email or list of emails.
     * @return Result|Result[]
     */
    public function traffic(string|array $email_or_emails): Result|array
    {
        if (is_array($email_or_emails)) {
            $requests = [];
            foreach ($email_or_emails as $email) {
                $requests[] = Request::get('traffic/' . rawurlencode($email));
            }
            return $this->_requests($requests);
        }
        return $this->_request(Request::get('traffic/' . rawurlencode($email_or_emails)));
    }

    /**
     * Every share link of the clients sharing a subscription id.
     *
     * @param string $sub_id Subscription id.
     * @return Result
     */
    public function sub_links(string $sub_id): Result
    {
        return $this->_request(Request::get('subLinks/' . rawurlencode($sub_id)));
    }

    /**
     * Generate a Happ crypt5 link for a client record id.
     *
     * @param int $id Stable client record id.
     * @return Result
     */
    public function happ_link(int $id): Result
    {
        return $this->_request(Request::post("happLink/$id"));
    }

    /**
     * Every URL of one client across all attached inbounds.
     *
     * @param string $email Client email.
     * @return Result
     */
    public function links(string $email): Result
    {
        return $this->_request(Request::get('links/' . rawurlencode($email)));
    }

    /**
     * Send one clients request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->clients($request);
        return $result;
    }

    /**
     * Send many clients requests in parallel.
     *
     * @param array $requests Prepared requests.
     * @return Result[]
     */
    private function _requests(array $requests): array
    {
        /** @var Result[] $results */
        $results = $this->request->clients(requests: $requests);
        return $results;
    }
}
