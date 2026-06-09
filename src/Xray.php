<?php

namespace XUI;

use JSON\json;
use stdClass;
use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Xray\Reverse\Reverse;
use XUI\Xray\Routing\Routing;
use XUI\Xray\Settings;

class Xray
{
    public function __construct(private readonly Request $request)
    {
    }

    public function routing(): Routing|false
    {
        $routing = new Routing($this);
        if ($routing->load())
            return $routing;
        else
            return false;
    }

    /**
     * Load reverse configuration from xray core config
     * <h4>Removed from v2.9.4 of 3x-ui</h4>
     * @return Reverse|false
     */
    public function reverse(): Reverse|false
    {
        $reverse = new Reverse($this);
        if ($reverse->load())
            return $reverse;
        else
            return false;
    }

    public function template(): Result
    {
        return $this->_request(Request::post(''));
    }

    public function settings(Result $template = null, int $output = Statics::OUTPUT_OBJECT): Result|array|string|stdClass
    {
        $result = $template ?? $this->template();
        if ($result->ok && $result->success) {
            $obj = json::_in($result->response()->obj);
            return Statics::output($obj->xraySetting, $output);
        } else {
            return $result;
        }
    }

    public function get(string|array $section_or_sections, array $settings = null, int $output = Statics::OUTPUT_OBJECT): stdClass|array|string|Result
    {
        $settings = $settings ?? $this->settings(output: Statics::OUTPUT_ARRAY);
        if (is_array($settings) || $settings->ok && $settings->success) {
            if (!is_array($settings)) $settings = $settings->response(Statics::OUTPUT_ARRAY)['obj']['xraySetting'];
            $xray_settings = new Settings($settings);
            return Statics::output($xray_settings->get($section_or_sections), $output);
        } else {
            return $settings;
        }
    }

    public function update(array $update, array $settings = null): Result
    {
        $settings = $settings ?? $this->settings(output: Statics::OUTPUT_ARRAY);
        if (is_array($settings) || $settings->ok && $settings->success) {
            if (!is_array($settings)) $settings = $settings->response(Statics::OUTPUT_ARRAY)['obj']['xraySetting'];
            $xray_settings = new Settings($settings);
            $xray_settings->update($update);
            return $this->_request(Request::post('update', form_params: ['xraySetting' => json::_out($xray_settings->settings())]));
        } else {
            return $settings;
        }
    }

    public function set(array|object|string $settings): Result
    {
        return $this->_request(Request::post('update', form_params: ['xraySetting' => json::to_json($settings)]));
    }

    public function get_default(): Result
    {
        return $this->_request(Request::get('getDefaultJsonConfig'));
    }

    public function test_outbound(array $outbound, array $all_outbounds, string $mode = 'tcp'): Result
    {
        return $this->_request(Request::post('testOutbound', [
            'outbound' => $outbound,
            'allOutbounds' => $all_outbounds,
            'mode' => $mode
        ]));
    }

    private function _request(array $request): Result
    {
        return $this->request->xray($request);
    }

    private function _requests(array $requests): array
    {
        return $this->request->xray(requests: $requests);
    }
}