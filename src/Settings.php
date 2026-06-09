<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;

class Settings
{
    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Get panel full settings
     * @return Result
     */
    public function settings(): Result
    {
        return $this->_request(Request::post('all'));
    }

    /**
     * Get a setting/settings from panel settings
     * @param string|array $section_or_sections
     * @param array|null $settings
     * @return mixed
     */
    public function get(string|array $section_or_sections, array $settings = null): mixed
    {
        $settings = $settings ?? $this->settings();
        if (is_array($settings) || $settings->ok && $settings->success) {
            if (!is_array($settings)) $settings = $settings->response(Statics::OUTPUT_ARRAY)['obj'];
            $panel_settings = new Settings\Settings($settings);
            return $panel_settings->get($section_or_sections);
        }else{
            return $settings;
        }
    }

    /**
     * Update panel settings
     * @param array $update
     * @param array|null $settings
     * @return Result
     */
    public function update(array $update, array $settings = null): Result
    {
        $settings = $settings ?? $this->settings();
        if (is_array($settings) || $settings->ok && $settings->success) {
            if (!is_array($settings)) $settings = $settings->response(Statics::OUTPUT_ARRAY)['obj'];
            $xray_settings = new Settings\Settings($settings);
            $xray_settings->update($update);
            return $this->_request(Request::post('update', $xray_settings->settings));
        } else {
            return $settings;
        }
    }

    /**
     * Restart panel
     * @return Result
     */
    public function restart(): Result
    {
        return $this->_request(Request::post('restartPanel'));
    }

    /**
     * Get default xray config based on panel&xray-core version
     * @return Result
     */
    public function get_default_xray(): Result
    {
        return $this->_request(Request::get('getDefaultJsonConfig'));
    }

    /**
     * Get default panel config based on panel version
     * @return Result
     */
    public function get_default(): Result
    {
        return $this->_request(Request::post('getDefaultJsonConfig'));
    }

    private function _request(array $request): Result
    {
        return $this->request->settings($request);
    }

    private function _requests(array $requests): array
    {
        return $this->request->settings(requests: $requests);
    }
}