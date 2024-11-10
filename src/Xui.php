<?php

namespace XUI;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Panel\Panel;
use XUI\Server\Server;
use XUI\Xray\Xray;

class Xui
{
    public bool $has_ssl;
    public string $host;
    public string $port;
    public string $uri_path;
    private string $address;
    private Client $guzzle;
    public int $output;
    public int $response_output;
    public Server $server;
    public Panel $panel;
    public Xray $xray;
    public const OUTPUT_JSON = 111;
    public const OUTPUT_OBJECT = 112;
    public const OUTPUT_ARRAY = 113;
    public const UNIT_BYTE = 1;
    public const UNIT_KILOBYTE = 1024;
    public const UNIT_MEGABYTE = 1024 * self::UNIT_KILOBYTE;
    public const UNIT_GIGABYTE = 1024 * self::UNIT_MEGABYTE;
    public const UNIT_TERABYTE = 1024 * self::UNIT_GIGABYTE;

    public function __construct(
        $xui_host,
        $xui_port,
        $xui_uri_path = '/',
        $has_ssl = false,
        $cookie_dir = null,
        $timeout = 10,
        $proxy = null,
        $output = self::OUTPUT_OBJECT,
        $response_output = self::OUTPUT_OBJECT
    )
    {
        $this->has_ssl = $has_ssl;
        $this->host = $xui_host;
        $this->port = $xui_port;
        $this->uri_path = $xui_uri_path;
        if ($this->has_ssl)
            $this->address = 'https://' . $this->host . ':' . $this->port . $this->uri_path;
        else
            $this->address = 'http://' . $this->host . ':' . $this->port . $this->uri_path;
        $cookie_dir = (is_null($cookie_dir)) ? "$xui_host.cookie" : $cookie_dir . "/$xui_host.cookie";
        $this->guzzle = new Client([
            'base_uri' => $this->address,
            'timeout' => $timeout,
            'proxy' => $proxy,
            'cookies' => new FileCookieJar($cookie_dir, true)
        ]);
        $this->output = $output;
        $this->response_output = $response_output;
    }

    public function login($username, $password): object|array|string
    {
        $st = microtime(true);
        if ($this->is_login()) {
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $this->server = new Server($this->guzzle, $this->output, $this->response_output);
            $this->panel = new Panel($this->guzzle, $this->output, $this->response_output);
            $this->xray = new Xray($this->guzzle, $this->output, $this->response_output);
            $return = ['ok' => true, 'response' => null, 'size' => null, 'time_taken' => $tt];
        } else {
            try {
                $result = $this->guzzle->post('login', [
                    'form_params' => [
                        'username' => $username,
                        'password' => $password
                    ]
                ]);
                $body = $result->getBody();
                $contents = json::_in($body->getContents(), true);
                if ($contents['success']):
                    $this->server = new Server($this->guzzle, $this->output, $this->response_output);
                    $this->panel = new Panel($this->guzzle, $this->output, $this->response_output);
                    $this->xray = new Xray($this->guzzle, $this->output, $this->response_output);
                endif;
                $response = $this->response_output($contents);
                $et = microtime(true);
                $tt = round($et - $st, 3);
                $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
            } catch (GuzzleException $err) {
                $error_code = $err->getCode();
                $error = $err->getMessage();
                $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
            }
        }
        return $this->output($return);
    }

    private function is_login(): bool
    {
        try {
            $this->guzzle->post('server/status');
            return true;
        } catch (GuzzleException $err) {
            return false;
        }
    }

    public static function random(int $length = 32): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 1; $i <= $length; $i++) :
            $out .= $chars[rand(0, strlen($chars) - 1)];
        endfor;
        return $out;
    }

    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function output(array|object|string $data): object|array|string
    {
        switch ($this->output):
            case Xui::OUTPUT_JSON:
                $return = json::to_json($data);
            break;
            case Xui::OUTPUT_OBJECT:
                $return = json::to_object($data);
            break;
            case Xui::OUTPUT_ARRAY:
                $return = json::to_array($data);
            break;
        endswitch;
        return $return;
    }

    private function response_output(array|object|string $data): object|array|string
    {
        switch ($this->response_output):
            case Xui::OUTPUT_JSON:
                $return = json::to_json($data);
            break;
            case Xui::OUTPUT_OBJECT:
                $return = json::to_object($data);
            break;
            case Xui::OUTPUT_ARRAY:
                $return = json::to_array($data);
            break;
        endswitch;
        return $return;
    }
}