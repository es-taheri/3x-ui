<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;

class Clients
{
    public function __construct(private readonly Request $request)
    {
    }

    public function list(
        bool   $paged = false,
        int    $page = null,
        int    $pageSize = null,
        string $search = null,
        string $filter = null,
        string $protocol = null,
        string $sort = null,
        string $order = null
    ): Result
    {
        return $this->_request(Request::get($paged ? 'list/paged' : 'list', query: $paged ? [
            'page' => $page,
            'pageSize' => $pageSize,
            'search' => $search,
            'filter' => $filter,
            'protocol' => $protocol,
            'sort' => $sort,
            'order' => $order,
        ] : []));
    }

    public function get(int|array $email_or_emails): Result|array
    {
        if (is_array($email_or_emails)) {
            $requests = [];
            foreach ($emails = $email_or_emails as $email):
                $requests[] = Request::get("get/$email");
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::get("get/$email_or_emails"));
        }
    }

    public function create(
        string $email,
        array  $inbound_ids,
        int    $total_traffic = 0,
        int    $expiry_time = 0,
        int    $tg_id = 0,
        int    $limit_ip = 0,
        bool   $enable = true,
        string $uuid = null,
        string $password = null,
        string $auth = null,
        string $sub_id = null,
        string $comment = '',
        string $flow = '',
        string $group = '',
        int    $reset = 0,
        string $security = 'auto'
    ): Result
    {
        return $this->_request(Request::post('add', [
            'client' => [
                'email' => $email,
                'totalGB' => $total_traffic,
                'expiryTime' => $expiry_time * 1000,
                'tgId' => $tg_id,
                'limitIp' => $limit_ip,
                'enable' => $enable,
                'comment' => $comment,
                'flow' => $flow,
                'group' => $group,
                'reset' => $reset,
                'security' => $security,
                'id' => $uuid ?? Statics::uuid(),
                'password' => $password ?? Statics::random(16),
                'auth' => $auth ?? Statics::random(16),
                'subId' => $sub_id ?? Statics::random(10),
            ],
            'inboundIds' => $inbound_ids,
        ]));
    }

    public function bulk_create(array $clients): array
    {
        $clients2 = [];
        foreach ($clients as $client):
            [
                $email,
                $inbound_ids,
                $total_traffic,
                $expiry_time,
                $tg_id,
                $limit_ip,
                $enable,
                $uuid,
                $password,
                $auth,
                $sub_id,
                $comment,
                $flow,
                $group,
                $reset,
                $security
            ] = $client;
            $clients2[] = [
                'client' => [
                    'email' => $email,
                    'totalGB' => $total_traffic,
                    'expiryTime' => $expiry_time * 1000,
                    'tgId' => $tg_id,
                    'limitIp' => $limit_ip,
                    'enable' => $enable,
                    'comment' => $comment,
                    'flow' => $flow,
                    'group' => $group,
                    'reset' => $reset,
                    'security' => $security,
                    'id' => $uuid,
                    'password' => $password,
                    'auth' => $auth,
                    'subId' => $sub_id,
                ],
                'inboundIds' => $inbound_ids
            ];
        endforeach;
        return $this->_requests(Request::post('bulkCreate', $clients2));
    }

    public function update(
        string $email,
        string $new_email = null,
        int    $total_traffic = null,
        int    $expiry_time = null,
        int    $tg_id = null,
        int    $limit_ip = null,
        bool   $enable = null,
        string $uuid = null,
        string $password = null,
        string $auth = null,
        string $sub_id = null,
        string $comment = null,
        string $flow = null,
        string $group = null,
        int    $reset = null,
        string $security = null): Result
    {
        $body = [];
        if (isset($new_email)) $body['email'] = $new_email;
        if (isset($total_traffic)) $body['totalGB'] = $total_traffic;
        if (isset($expiry_time)) $body['expiryTime'] = $expiry_time * 1000;
        if (isset($tg_id)) $body['tgId'] = $tg_id;
        if (isset($limit_ip)) $body['limitIp'] = $limit_ip;
        if (isset($enable)) $body['enable'] = $enable;
        if (isset($uuid)) $body['id'] = $uuid;
        if (isset($password)) $body['password'] = $password;
        if (isset($auth)) $body['auth'] = $auth;
        if (isset($sub_id)) $body['subId'] = $sub_id;
        if (isset($comment)) $body['comment'] = $comment;
        if (isset($flow)) $body['flow'] = $flow;
        if (isset($group)) $body['group'] = $group;
        if (isset($reset)) $body['reset'] = $reset;
        if (isset($security)) $body['security'] = $security;
        return $this->_request(Request::post("update/$email", $body));
    }

    public function delete(string|array $email_or_emails, bool $keep_traffic = false): Result
    {
        if (is_array($email_or_emails))
            return $this->_request(Request::post("bulkDel", ['emails' => $email_or_emails, 'keepTraffic' => $keep_traffic]));
        else
            return $this->_request(Request::post("del/$email_or_emails", ['keepTraffic' => $keep_traffic]));
    }

    public function attach(string|array $email_or_emails, array $inbound_ids): Result
    {
        if (is_array($email_or_emails))
            return $this->_request(Request::post("bulkAttach", ['emails' => $email_or_emails, 'inboundIds' => $inbound_ids]));
        else
            return $this->_request(Request::post("$email_or_emails/attach", ['inboundIds' => $inbound_ids]));
    }

    public function detach(string|array $email_or_emails, array $inbound_ids): Result
    {
        if (is_array($email_or_emails))
            return $this->_request(Request::post("bulkDetach", ['emails' => $email_or_emails, 'inboundIds' => $inbound_ids]));
        else
            return $this->_request(Request::post("$email_or_emails/detach", ['inboundIds' => $inbound_ids]));
    }

    public function reset_traffic(string|array $email_or_emails): Result
    {
        if (is_array($email_or_emails))
            return $this->_request(Request::post("bulkResetTraffic", ['emails' => $email_or_emails]));
        else
            return $this->_request(Request::post("resetTraffic/$email_or_emails"));
    }

    public function update_traffic(string $email, int $upload, int $download): Result
    {
        return $this->_request(Request::post("updateTraffic/$email", [
            'upload' => $upload,
            'download' => $download,
        ]));
    }

    public function ips(string|array $email_or_emails): Result|array
    {
        if (is_array($email_or_emails)) {
            $requests = [];
            foreach ($emails = $email_or_emails as $email):
                $requests[] = Request::post("ips/$email");
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::post("ips/$email_or_emails"));
        }
    }

    public function clear_ips(string|array $email_or_emails): Result|array
    {
        if (is_array($email_or_emails)) {
            $requests = [];
            foreach ($emails = $email_or_emails as $email):
                $requests[] = Request::post("clearIps/$email");
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::post("clearIps/$email_or_emails"));
        }
    }

    public function onlines(): Result|array
    {
        return $this->_requests(Request::post('onlines'));
    }

    public function last_onlines(): Result|array
    {
        return $this->_requests(Request::post('lastOnlines'));
    }

    public function traffic(string|array $email_or_emails): Result|array
    {
        if (is_array($email_or_emails)) {
            $requests = [];
            foreach ($emails = $email_or_emails as $email):
                $requests[] = Request::get("traffic/$email");
            endforeach;
            return $this->_requests($requests);
        } else {
            return $this->_request(Request::get("traffic/$email_or_emails"));
        }
    }

    public function bulk_adjust(array $emails, int $days, int $traffic): Result
    {
        return $this->_request(Request::post('bulkAdjust', [
            'emails' => $emails,
            'addDays' => $days,
            'addBytes' => $traffic,
        ]));
    }

    public static function create_client(
        string $email,
        array  $inbound_ids,
        int    $total_traffic = 0,
        int    $expiry_time = 0,
        int    $tg_id = 0,
        int    $limit_ip = 0,
        bool   $enable = true,
        string $uuid = null,
        string $password = null,
        string $auth = null,
        string $sub_id = null,
        string $comment = '',
        string $flow = '',
        string $group = '',
        int    $reset = 0,
        string $security = 'auto'
    ): array
    {
        return [
            $email,
            $inbound_ids,
            $total_traffic,
            $expiry_time,
            $tg_id,
            $limit_ip,
            $enable,
            $uuid ?? Statics::uuid(),
            $password ?? Statics::random(16),
            $auth ?? Statics::random(16),
            $sub_id ?? Statics::random(10),
            $comment,
            $flow,
            $group,
            $reset,
            $security
        ];
    }

    private function _request(array $request): Result
    {
        return $this->request->clients($request);
    }

    private function _requests(array $requests): array
    {
        return $this->request->clients(requests: $requests);
    }

}