<?php

namespace XUI\Panel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Xui;

class Panel
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

    public function all_settings(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/setting/all");
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

    public function get_setting(array|string $setting)
    {
        $panel_settings = new Settings($this->all_settings());
        return $panel_settings->get($setting);
    }

    public function update_setting(array $settings): object|array|string
    {
        $st = microtime(true);
        $panel_settings = new Settings($this->all_settings());
        $panel_settings->update($settings);
        try {
            $result = $this->guzzle->post("panel/setting/update", [
                'form_params' => $panel_settings->settings
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

    public function restart(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/setting/restartPanel");
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

    public function default_xray_config(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->get("panel/setting/getDefaultJsonConfig");
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