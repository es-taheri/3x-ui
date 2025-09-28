<?php

namespace XUI\Xray\Inbound;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Xray\Inbound\Protocols\DokodemoDoor\DokodemoDoor;
use XUI\Xray\Inbound\Protocols\Http\Http;
use XUI\Xray\Inbound\Protocols\Shadowsocks\Shadowsocks;
use XUI\Xray\Inbound\Protocols\Socks\Socks;
use XUI\Xray\Inbound\Protocols\Trojan\Trojan;
use XUI\Xray\Inbound\Protocols\Vless\Vless;
use XUI\Xray\Inbound\Protocols\Vmess\Vmess;
use XUI\Xray\Outbound\Protocols\StreamSettings as ob_StreamSettings;
use XUI\Xray\Outbound\Protocols\Trojan\Trojan as ob_Trojan;
use XUI\Xray\Outbound\Protocols\Shadowsocks\Shadowsocks as ob_Shadowsocks;
use XUI\Xray\Outbound\Protocols\Vmess\Vmess as ob_Vmess;
use XUI\Xray\Outbound\Protocols\Vless\Vless as ob_Vless;
use XUI\Xray\Outbound\Protocols\Socks\Socks as ob_Socks;
use XUI\Xray\Outbound\Protocols\Http\Http as ob_Http;
use XUI\Xui;

class Inbound
{
    private Client $guzzle;
    public int $output;
    public int $response_output;

    public function __construct(Client $guzzle, int $output = Xui::OUTPUT_OBJECT, int $response_output = Xui::OUTPUT_OBJECT)
    {
        $this->guzzle = $guzzle;
        $this->output = $output;
        $this->response_output = $response_output;
    }

    /**
     * Add a new inbound
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|DokodemoDoor $config
     * @param string|null $remark
     * @param int $total_traffic
     * @param int|string $expiry_time
     * @param int $download
     * @param int $upload
     * @param bool $enable
     * @return array|false|mixed|string
     */
    public function add(
        Vmess|Vless|Trojan|Shadowsocks|Socks|Http|DokodemoDoor $config, string $remark = null, int $total_traffic = 0,
        int|string                                             $expiry_time = 0, int $download = 0, int $upload = 0, bool $enable = true
    ): mixed
    {
        $st = microtime(true);
        $protocol = $config->protocol;
        $request_data = [
            'up' => $upload,
            'down' => $download,
            'total' => $total_traffic,
            'remark' => $remark,
            'enable' => $enable,
            'expiryTime' => $expiry_time * 1000,
            'listen' => $config->listen,
            'port' => $config->port,
            'protocol' => $config->protocol,
        ];
        switch ($protocol):
            case 'vmess':
            case 'vless':
            case 'trojan':
            case 'shadowsocks':
                $request_data['settings'] = json::_out($config->settings->settings());
                $request_data['streamSettings'] = json::_out($config->stream_settings->stream_settings());
                $request_data['sniffing'] = json::_out($config->sniffing->sniffing());
                break;
            case 'socks':
            case 'http':
            case 'dokodemo-door':
                $request_data['settings'] = json::_out($config->settings->settings());
                break;
        endswitch;
        try {
            $result = $this->guzzle->post("panel/inbound/add", [
                'form_params' => $request_data
            ]);
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Get a list of inbounds
     * @return array|false|mixed|string
     */
    public function list(): mixed
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/list");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Get an inbound config.\
     * Only return inbound in `response`!
     * @param int $inbound_id
     * @return array|false|mixed|string
     */
    public function get(int $inbound_id): mixed
    {
        $st = microtime(true);
        $last_output = $this->output;
        $last_response_output = $this->response_output;
        $this->output = Xui::OUTPUT_OBJECT;
        $this->response_output = Xui::OUTPUT_OBJECT;
        $result = $this->list();
        if ($result->ok) {
            $response = $result->response;
            if ($response->success) {
                $return = [
                    'ok' => false,
                    'error_code' => 500,
                    'error' => 'inbound not found'
                ];
                $inbounds_list = $response->obj;
                foreach ($inbounds_list as $inbound):
                    if ($inbound->id == $inbound_id):
                        $this->response_output = $last_response_output;
                        $inbound = json::_in(json::_out($inbound), true);
                        $et = microtime(true);
                        $tt = round($et - $st, 3);
                        $return = [
                            'ok' => true,
                            'response' => $this->response_output($inbound),
                            'size' => null,
                            'time_taken' => $tt
                        ];
                    endif;
                endforeach;
            } else {
                $return = [
                    'ok' => false,
                    'error_code' => 500,
                    'error' => 'Fetching inbounds list error: ' . $response->msg
                ];
            }
        } else {
            $return = [
                'ok' => false,
                'error_code' => $result->error_code,
                'error' => 'Fetching inbounds list error: ' . $result->error
            ];
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $this->output($return);
    }

    public function exist(int $inbound_id): bool
    {
        $last_output = $this->output;
        $last_response_output = $this->response_output;
        $this->output = Xui::OUTPUT_OBJECT;
        $this->response_output = Xui::OUTPUT_OBJECT;
        $result = $this->list();
        if ($result->ok) {
            $response = $result->response;
            $exist = false;
            if ($response->success) :
                $inbounds_list = $response->obj;
                foreach ($inbounds_list as $inbound):
                    if ($inbound->id == $inbound_id):
                        $exist = true;
                    endif;
                endforeach;
            endif;
        } else {
            $exist = false;
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $exist;
    }

    /**
     * Update an inbound
     * @param int $inbound_id
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|DokodemoDoor|null $config
     * @param string|null $remark
     * @param int|null $total_traffic
     * @param int|string|null $expiry_time
     * @param int|null $download
     * @param int|null $upload
     * @param bool|null $enable
     * @return array|false|mixed|string
     */
    public function update(
        int $inbound_id, Vmess|Vless|Trojan|Shadowsocks|Socks|Http|DokodemoDoor $config = null, string $remark = null,
        int $total_traffic = null, int|string $expiry_time = null, int $download = null, int $upload = null, bool $enable = null
    ): mixed
    {
        $st = microtime(true);
        $last_output = $this->output;
        $last_response_output = $this->response_output;
        $this->output = Xui::OUTPUT_OBJECT;
        $this->response_output = Xui::OUTPUT_OBJECT;
        $result = $this->list();
        if ($result->ok) {
            $response = $result->response;
            if ($response->success) {
                $return = [
                    'ok' => false,
                    'error_code' => 500,
                    'error' => 'inbound not found'
                ];
                $inbounds_list = $response->obj;
                foreach ($inbounds_list as $inbound):
                    if ($inbound->id == $inbound_id):
                        $inbound_protocol = $inbound->protocol;
                        $protocol = (is_null($config)) ? $inbound_protocol : $config->protocol;
                        if ($protocol == $inbound_protocol) {
                            $remark = (is_null($remark)) ? $inbound->remark : $remark;
                            $total_traffic = (is_null($total_traffic)) ? $inbound->total : $total_traffic;
                            $expiry_time = (is_null($expiry_time)) ? $inbound->expiryTime : $expiry_time * 1000;
                            $download = (is_null($download)) ? $inbound->down : $download;
                            $upload = (is_null($upload)) ? $inbound->up : $upload;
                            $enable = (is_null($enable)) ? $inbound->enable : $enable;
                            $listen = (is_null($config)) ? $inbound->listen : $config->listen;
                            $port = (is_null($config)) ? $inbound->port : $config->port;
                            $request_data = [
                                'up' => $upload,
                                'down' => $download,
                                'total' => $total_traffic,
                                'remark' => $remark,
                                'enable' => $enable,
                                'expiryTime' => $expiry_time,
                                'listen' => $listen,
                                'port' => $port,
                                'protocol' => $protocol,
                            ];
                            switch ($protocol):
                                case 'vmess':
                                case 'vless':
                                case 'trojan':
                                case 'shadowsocks':
                                    $request_data['settings'] = (is_null($config)) ? $inbound->settings : json::_out($config->settings->settings());
                                    $request_data['streamSettings'] = (is_null($config)) ? $inbound->streamSettings : json::_out($config->stream_settings->stream_settings());
                                    $request_data['sniffing'] = (is_null($config)) ? $inbound->sniffing : json::_out($config->sniffing->sniffing());
                                    break;
                                case 'socks':
                                case 'http':
                                case 'dokodemo-door':
                                    $request_data['settings'] = (is_null($config)) ? $inbound->settings : json::_out($config->settings->settings());
                                    break;
                            endswitch;
                            $this->response_output = $last_response_output;
                            try {
                                $result = $this->guzzle->post("panel/inbound/update/$inbound_id", [
                                    'form_params' => $request_data
                                ]);
                                $body = $result->getBody();
                                $response = $this->response_output($body->getContents());
                                $et = microtime(true);
                                $tt = round($et - $st, 3);
                                $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
                            } catch (GuzzleException $err) {
                                $error_code = $err->getCode();
                                $error = $err->getMessage();
                                $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
                            }
                        } else {
                            $return = [
                                'ok' => false,
                                'error_code' => 500,
                                'error' => 'inbound current protocol and $config protocol must be the same'
                            ];
                        }
                        break;
                    endif;
                endforeach;
            } else {
                $return = [
                    'ok' => false,
                    'error_code' => 500,
                    'error' => 'Fetching inbounds list error: ' . $response->msg
                ];
            }
        } else {
            $return = [
                'ok' => false,
                'error_code' => $result->error_code,
                'error' => 'Fetching inbounds list error: ' . $result->error
            ];
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $this->output($return);
    }

    /**
     * Delete an inbound
     * @param int $inbound_id
     * @return array|false|mixed|string
     */
    public function delete(int $inbound_id): mixed
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/del/$inbound_id");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Add a new client to an existing inbound
     * @param int $inbound_id
     * @param string $client_id
     * @param string $email
     * @param int|null $total_traffic
     * @param int|string|null $expiry_time
     * @param int|null $telegram_id
     * @param int|null $subscription_id
     * @return mixed
     */
    public function add_client(
        int $inbound_id, string $client_id, string $email, int $total_traffic = 0, int|string $expiry_time = 0,
        int $telegram_id = null, int $subscription_id = null
    ): mixed
    {
        $st = microtime(true);
        $settings = [
            'clients' => [
                [
                    'id' => $client_id,
                    'email' => $email,
                    'totalGB' => $total_traffic,
                    'expiryTime' => $expiry_time * 1000,
                    'enable' => true,
                    'tgId' => $telegram_id,
                    'subId' => $subscription_id
                ]
            ]
        ];
        $request_data = ['settings' => json::_out($settings)];
        try {
            $result = $this->guzzle->post("panel/inbound/addClient", [
                'query' => ['id' => $inbound_id],
                'form_params' => $request_data
            ]);
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Update a client of an inbound
     * @param int $inbound_id
     * @param string $client_id
     * @param string $email
     * @param int|null $total_traffic
     * @param int|string|null $expiry_time
     * @param bool $status
     * @return string|array|object
     */
    public function update_client(int $inbound_id, string $client_id, string $email, int|null $total_traffic = 0, int|string|null $expiry_time = 0, bool $status = true): string|array|object
    {
        $st = microtime(true);
        $settings = [
            'clients' => [
                [
                    'id' => $client_id,
                    'email' => $email,
                    'totalGB' => $total_traffic,
                    'expiryTime' => $expiry_time,
                    'enable' => $status
                ]
            ]
        ];

        $request_data = ['settings' => json::_out($settings)];

        try {
            $result = $this->guzzle->post("panel/inbound/updateClient/$client_id", [
                'query' => ['id' => $inbound_id],
                'form_params' => $request_data
            ]);
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Delete a client from an inbound
     * @param int $inbound_id
     * @param string $client_uuid
     * @return object|array|string
     */
    public function delete_client(int $inbound_id, string $client_uuid): object|array|string
    {
        $st = microtime(true);

        try {
            $result = $this->guzzle->post("panel/inbound/$inbound_id/delClient/$client_uuid");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }

        return $this->output($return);
    }

    /**
     * Delete a client from an inbound
     * @return object|array|string
     */
    public function online_clients(): object|array|string
    {
        $st = microtime(true);

        try {
            $result = $this->guzzle->post("panel/api/inbounds/onlines");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }

        return $this->output($return);
    }

    /**
     * Export an inbound.\
     * Only return json encoded exported inbound in `response`!
     * @param int $inbound_id
     * @return mixed
     */
    public function export(int $inbound_id): mixed
    {
        $st = microtime(true);
        $last_output = $this->output;
        $last_response_output = $this->response_output;
        $this->output = Xui::OUTPUT_OBJECT;
        $this->response_output = Xui::OUTPUT_OBJECT;
        $result = $this->get($inbound_id);
        if ($result->ok) {
            $inbound = $result->response;
            $this->response_output = $last_response_output;
            $inbound = json::_out($inbound);
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = [
                'ok' => true,
                'response' => $inbound,
                'size' => null,
                'time_taken' => $tt
            ];
        } else {
            $return = [
                'ok' => false,
                'error_code' => $result->error_code,
                'error' => 'Fetching inbound error: ' . $result->error
            ];
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $this->output($return);
    }

    /**
     * Import an inbound.\
     * Only json encoded exported inbound accepted!
     * @param string $exported_inbound
     * @return array|false|mixed|string
     */
    public function import(string $exported_inbound): mixed
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/import", [
                'form_params' => ['data' => $exported_inbound]
            ]);
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Get IPLimit log of connected ips to a client of inbound
     * @param string $client_email
     * @return object|array|string
     */
    public function get_client_ips(string $client_email): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/clientIps/$client_email");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Clear IPLimit log of connected ips to a client of inbound
     * @param string $client_email
     * @return object|array|string
     */
    public function clear_client_ips(string $client_email): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/clearClientIps/$client_email");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Reset a client of inbound traffic usage
     * @param int $inbound_id
     * @param string $client_email
     * @return object|array|string
     */
    public function reset_client_traffic(int $inbound_id, string $client_email): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/$inbound_id/resetClientTraffic/$client_email");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Get online inbounds by their clients' email
     * @return object|array|string
     */
    public function onlines(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/onlines");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Read inbound config exported or got from server.
     * @param array|string|object $inbound
     * @return Http|Socks|Vless|false|Trojan|Shadowsocks|Vmess|DokodemoDoor
     */
    public static function read(array|string|object $inbound): Http|Socks|Vless|false|Trojan|Shadowsocks|Vmess|DokodemoDoor
    {
        $inbound = json::to_object($inbound);
        if (is_object($inbound)) {
            switch ($inbound->protocol):
                case 'vmess':
                    $settings = $inbound->settings;
                    $stream = $inbound->streamSettings;
                    $sniffing = $inbound->sniffing;
                    $config = new Vmess($inbound->listen, $inbound->port, $settings, $stream, $sniffing);
                    break;
                case 'vless':
                    $settings = $inbound->settings;
                    $stream = $inbound->streamSettings;
                    $sniffing = $inbound->sniffing;
                    $config = new Vless($inbound->listen, $inbound->port, $settings, $stream, $sniffing);
                    break;
                case 'trojan':
                    $settings = $inbound->settings;
                    $stream = $inbound->streamSettings;
                    $sniffing = $inbound->sniffing;
                    $config = new Trojan($inbound->listen, $inbound->port, $settings, $stream, $sniffing);
                    break;
                case 'shadowsocks':
                    $settings = $inbound->settings;
                    $stream = $inbound->streamSettings;
                    $sniffing = $inbound->sniffing;
                    $config = new Shadowsocks($inbound->listen, $inbound->port, $settings, $stream, $sniffing);
                    break;
                case 'dokodemo-door':
                    $settings = $inbound->settings;
                    $config = new DokodemoDoor($inbound->listen, $inbound->port, $settings);
                    break;
                case 'socks':
                    $settings = $inbound->settings;
                    $config = new Socks($inbound->listen, $inbound->port, $settings);
                    break;
                case 'http':
                    $settings = $inbound->settings;
                    $config = new Http($inbound->listen, $inbound->port, $settings);
                    break;
            endswitch;
            return $config ?? false;
        } else {
            return false;
        }
    }

    /**
     * Convert inbound config to outbound config
     * @param string $address
     * @param int $port
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http $inbound_config
     * @return ob_Trojan|ob_Shadowsocks|ob_Vmess|ob_Vless|ob_Socks|ob_Http|false
     */
    public static function to_outbound(
        string $address, Vmess|Vless|Trojan|Shadowsocks|Socks|Http $inbound_config, int|null $port = null
    ): ob_Trojan|ob_Shadowsocks|ob_Vmess|ob_Vless|ob_Socks|ob_Http|false
    {
        $port = $port ?? $inbound_config->port;
        switch ($inbound_config->protocol):
            case 'vmess':
                $settings = $inbound_config->settings;
                $stream = $inbound_config->stream_settings;
                $config = new ob_Vmess();
                $config_settings = new \XUI\Xray\Outbound\Protocols\Vmess\Settings($address, $port);
                $config_settings->add_user($settings->clients[0]['id']);
                break;
            case 'vless':
                $settings = $inbound_config->settings;
                $stream = $inbound_config->stream_settings;
                $config = new ob_Vless();
                $config_settings = new \XUI\Xray\Outbound\Protocols\Vless\Settings($address, $port);
                $config_settings->add_user($settings->clients[0]['id']);
                break;
            case 'trojan':
                $settings = $inbound_config->settings;
                $stream = $inbound_config->stream_settings;
                $config = new ob_Trojan();
                $config_settings = new \XUI\Xray\Outbound\Protocols\Trojan\Settings(
                    $address, $port, $settings->clients[0]['password'], $settings->clients[0]['email']
                );
                break;
            case 'shadowsocks':
                $settings = $inbound_config->settings;
                $stream = $inbound_config->stream_settings;
                $config = new ob_Shadowsocks();
                $config_settings = new \XUI\Xray\Outbound\Protocols\Shadowsocks\Settings(
                    $address, $port, $settings->password, $settings->method, $settings->email
                );
                break;
            case 'socks':
                $settings = $inbound_config->settings;
                $config = new ob_Socks();
                $config_settings = new \XUI\Xray\Outbound\Protocols\Socks\Settings($address, $port);
                $config_settings->add_user($settings->accounts[0]['username'], $settings->accounts[0]['password']);
                break;
            case 'http':
                $settings = $inbound_config->settings;
                $config = new ob_Http();
                $config_settings = new \XUI\Xray\Outbound\Protocols\Http\Settings($address, $port);
                $config_settings->add_user($settings->accounts[0]['username'], $settings->accounts[0]['password']);
                break;
        endswitch;
        if (isset($config_settings))
            $config->settings = $config_settings;
        if (isset($stream)):
            $config_stream = new ob_StreamSettings($stream->network, $stream->security);
            switch ($stream->network):
                case 'tcp':
                    $header_type = $stream->tcp_settings['header']['type'];
                    switch ($header_type):
                        case 'http':
                            $request_headers = $stream->tcp_settings['header']['request']['headers'];
                            $request_headers = (empty($request_headers)) ? ['Host' => []] : $request_headers;
                            $header_request = [
                                'version' => $stream->tcp_settings['header']['response']['version'],
                                'method' => $stream->tcp_settings['header']['request']['method'],
                                'path' => $stream->tcp_settings['header']['request']['path'],
                                'headers' => $request_headers
                            ];
                            break;
                        case 'none':
                            $header_request = [];
                            break;
                    endswitch;
                    $config_stream->tcp_settings($header_type, $header_request);
                    break;
                case 'ws':
                    $config_stream->ws_settings($stream->ws_settings['acceptProxyProtocol'], $stream->ws_settings['path']);
                    break;
                case 'kcp':
                    $config_stream->kcp_settings(
                        $stream->kcp_settings['header']['type'],
                        $stream->kcp_settings['seed'],
                        $stream->kcp_settings['mtu'],
                        $stream->kcp_settings['tti'],
                        $stream->kcp_settings['uplinkCapacity'],
                        $stream->kcp_settings['downLinkCapacity'],
                        $stream->kcp_settings['congestion'],
                        $stream->kcp_settings['readBufferSize'],
                        $stream->kcp_settings['writeBufferSize'],
                    );
                    break;
                case 'http':
                    $config_stream->http_settings(
                        $stream->http_settings['method'],
                        $stream->http_settings['path'],
                        $stream->http_settings['host'],
                        $stream->http_settings['read_idle_timeout'],
                        $stream->http_settings['health_check_timeout']
                    );
                    break;
                case 'domainsocket':
                    $config_stream->ds_settings($stream->ds_settings['path'], $stream->ds_settings['abstract'], $stream->ds_settings['padding']);
                    break;
                case 'quic':
                    $config_stream->quic_settings($stream->quic_settings['security'], $stream->quic_settings['key'], $stream->quic_settings['header']['type']);
                    break;
                case 'grpc':
                    $config_stream->grpc_settings(
                        $stream->grpc_settings['serviceName'],
                        $stream->grpc_settings['multiMode'],
                        $stream->grpc_settings['idle_timeout'],
                        $stream->grpc_settings['health_check_timeout'],
                        $stream->grpc_settings['permit_without_stream'],
                        $stream->grpc_settings['initial_windows_size']
                    );
                    break;
            endswitch;
            switch ($stream->security):
                case 'none':
                    $config_stream->security = 'none';
                    break;
                case 'tls':
                    $config_stream->tls_settings(
                        $stream->tls_settings['serverName'],
                        $stream->tls_settings['allowInsecure'],
                        $stream->tls_settings['alpn'],
                        $stream->tls_settings['fingerprint'],
                    );
                    break;
                case 'reality':
                    $config_stream->reality_settings(
                        $stream->reality_settings['show'],
                        $stream->reality_settings['settings']['fingerprint'],
                        $stream->reality_settings['serverNames'][0],
                        $stream->reality_settings['settings']['publicKey'],
                        $stream->reality_settings['shortIds'][0],
                        $stream->reality_settings['settings']['spiderX']
                    );
                    break;
            endswitch;
            $config->stream_settings = $config_stream;
        endif;
        return $config ?? false;
    }

    private function output(array|object|string $data): object|array|string
    {
        return match ($this->output) {
            Xui::OUTPUT_JSON => json::to_json($data),
            Xui::OUTPUT_OBJECT => json::to_object($data),
            Xui::OUTPUT_ARRAY => json::to_array($data)
        };
    }

    private function response_output(array|object|string $data): object|array|string
    {
        return match ($this->response_output) {
            Xui::OUTPUT_JSON => json::to_json($data),
            Xui::OUTPUT_OBJECT => json::to_object($data),
            Xui::OUTPUT_ARRAY => json::to_array($data)
        };
    }
}