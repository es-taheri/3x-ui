<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;
use XUI\Helper\Statics;
use XUI\Settings\Settings as PanelSettings;

/**
 * Panel settings and API token endpoints.
 *
 * ```php
 * $all = $xui->settings->settings();
 * $xui->settings->update(['webPort' => 2053]);
 * $token = $xui->settings->create_api_token('monitoring', 'monitor');
 * ```
 */
class Settings
{
    /** API token scope: full panel access. */
    public const SCOPE_ADMIN = 'admin';

    /** API token scope: read-only status and metrics. */
    public const SCOPE_MONITOR = 'monitor';

    /** API token scope: node configuration sync. */
    public const SCOPE_NODE_SYNC = 'node-sync';

    /** API token scope: node sync plus panel self-update. */
    public const SCOPE_NODE_ADMIN = 'node-admin';

    /** Update channel: stable. */
    public const CHANNEL_STABLE = 'stable';

    /** Update channel: rolling dev builds. */
    public const CHANNEL_DEV = 'dev';

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
     * Return every panel setting.
     *
     * @return Result
     */
    public function settings(): Result
    {
        return $this->_request(Request::post('all'));
    }

    /**
     * Read one or many settings.
     *
     * @param string|array $key_or_keys Setting name or list of names.
     * @param array|null $settings Pre-fetched settings array.
     * @return mixed
     */
    public function get(string|array $key_or_keys, ?array $settings = null): mixed
    {
        if (is_null($settings)) {
            $result = $this->settings();
            if (!$result->ok || !$result->success) {
                return $result;
            }
            $settings = (array)$result->obj(Statics::OUTPUT_ARRAY);
        }
        return (new PanelSettings($settings))->get($key_or_keys);
    }

    /**
     * Merge and persist settings.
     *
     * @param array $update Setting name/value pairs.
     * @param array|null $settings Pre-fetched settings array.
     * @return Result
     */
    public function update(array $update, ?array $settings = null): Result
    {
        if (is_null($settings)) {
            $result = $this->settings();
            if (!$result->ok || !$result->success) {
                return $result;
            }
            $settings = (array)$result->obj(Statics::OUTPUT_ARRAY);
        }

        $panel_settings = new PanelSettings($settings);
        $panel_settings->update($update);
        return $this->_request(Request::post('update', $panel_settings->settings));
    }

    /**
     * Computed default settings for the current request host.
     *
     * @return Result
     */
    public function default_settings(): Result
    {
        return $this->_request(Request::post('defaultSettings'));
    }

    /**
     * Shipped default value of every browser-safe setting key.
     *
     * @return Result
     */
    public function factory_defaults(): Result
    {
        return $this->_request(Request::post('factoryDefaults'));
    }

    /**
     * Built-in default Xray config template.
     *
     * @return Result
     */
    public function default_xray_config(): Result
    {
        return $this->_request(Request::get('getDefaultJsonConfig'));
    }

    /**
     * Validate a regular expression with the backend RE2 compiler.
     *
     * @param string $regex Regular expression.
     * @return Result
     */
    public function validate_regex(string $regex): Result
    {
        return $this->_request(Request::post('validateRegex', ['regex' => $regex]));
    }

    /**
     * Change the panel admin credentials.
     *
     * @param string $old_username Current username.
     * @param string $old_password Current password.
     * @param string $new_username New username.
     * @param string $new_password New password.
     * @return Result
     */
    public function update_user(string $old_username, string $old_password, string $new_username, string $new_password): Result
    {
        return $this->_request(Request::post('updateUser', [
            'oldUsername' => $old_username,
            'oldPassword' => $old_password,
            'newUsername' => $new_username,
            'newPassword' => $new_password,
        ]));
    }

    /**
     * Restart the whole panel process.
     *
     * @return Result
     */
    public function restart(): Result
    {
        return $this->_request(Request::post('restartPanel'));
    }

    /**
     * Send a test email through the configured SMTP server.
     *
     * @return Result
     */
    public function test_smtp(): Result
    {
        return $this->_request(Request::post('testSmtp'));
    }

    /**
     * Send a test message through the configured Telegram bot.
     *
     * @return Result
     */
    public function test_tg_bot(): Result
    {
        return $this->_request(Request::post('testTgBot'));
    }

    /**
     * Report the capabilities of the configured Telegram bot.
     *
     * @return Result
     */
    public function tg_bot_capabilities(): Result
    {
        return $this->_request(Request::post('tgBotCapabilities'));
    }

    /**
     * Send a test notification through the configured Discord bot.
     *
     * @return Result
     */
    public function test_discord(): Result
    {
        return $this->_request(Request::post('testDiscord'));
    }

    /**
     * List every API token.
     *
     * @return Result
     */
    public function api_tokens(): Result
    {
        return $this->_api_token_request(Request::get(''));
    }

    /**
     * Create an API token. The plaintext is returned only once.
     *
     * @param string $name Human readable label.
     * @param string $scope One of the SCOPE_* constants.
     * @param int $expires_at Expiry in Unix milliseconds, 0 for never.
     * @return Result
     */
    public function create_api_token(string $name, string $scope = self::SCOPE_ADMIN, int $expires_at = 0): Result
    {
        return $this->_api_token_request(Request::post('create', [
            'name' => $name,
            'scope' => $scope,
            'expiresAt' => $expires_at,
        ]));
    }

    /**
     * Permanently delete an API token.
     *
     * @param int $id Token id.
     * @param string $expected_scope Stored scope, as a safety check.
     * @return Result
     */
    public function delete_api_token(int $id, string $expected_scope = self::SCOPE_ADMIN): Result
    {
        return $this->_api_token_request(Request::post("delete/$id", [
            'expectedScope' => $expected_scope,
        ]));
    }

    /**
     * Enable or disable an API token.
     *
     * @param int $id Token id.
     * @param bool $enabled New state.
     * @param string $expected_scope Stored scope, as a safety check.
     * @return Result
     */
    public function set_api_token_enabled(int $id, bool $enabled, string $expected_scope = self::SCOPE_ADMIN): Result
    {
        return $this->_api_token_request(Request::post("setEnabled/$id", [
            'enabled' => $enabled,
            'expectedScope' => $expected_scope,
        ]));
    }

    /**
     * Send one settings request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->settings($request);
        return $result;
    }

    /**
     * Send one API token request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _api_token_request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->api_tokens($request);
        return $result;
    }
}
