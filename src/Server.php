<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;

/**
 * Server endpoints: status, logs, certificates, Xray binary management and
 * database backups.
 */
class Server
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
     * Real-time machine snapshot (CPU, memory, disk, network, Xray state).
     *
     * @return Result
     */
    public function status(): Result
    {
        return $this->_request(Request::get('status'));
    }

    /**
     * Whether per-client IP limits can be enforced on this host.
     *
     * @return Result
     */
    public function fail2ban_status(): Result
    {
        return $this->_request(Request::get('fail2banStatus'));
    }

    /**
     * Legacy aggregated CPU history.
     *
     * @param int $bucket Bucket size in seconds (2, 30, 60, 120, 180, 300).
     * @return Result
     */
    public function cpu_history(int $bucket): Result
    {
        return $this->_request(Request::get("cpuHistory/$bucket"));
    }

    /**
     * Aggregated time-series for one metric.
     *
     * @param string $metric `cpu`, `mem`, `netUp`, `netDown`, `online`, `load1`, `load5` or `load15`.
     * @param int $bucket Bucket size in seconds (2, 30, 60, 120, 180, 300).
     * @return Result
     */
    public function history(string $metric, int $bucket): Result
    {
        return $this->_request(Request::get("history/$metric/$bucket"));
    }

    /**
     * Current Xray runtime metrics state.
     *
     * @return Result
     */
    public function xray_metrics_state(): Result
    {
        return $this->_request(Request::get('xrayMetricsState'));
    }

    /**
     * Time-series of one Xray runtime metric.
     *
     * @param string $metric `xrAlloc`, `xrSys`, `xrHeapObjects`, `xrNumGC` or `xrPauseNs`.
     * @param int $bucket Bucket size in seconds (2, 30, 60, 120, 180, 300).
     * @return Result
     */
    public function xray_metrics_history(string $metric, int $bucket): Result
    {
        return $this->_request(Request::get("xrayMetricsHistory/$metric/$bucket"));
    }

    /**
     * Latest Xray observatory snapshot.
     *
     * @return Result
     */
    public function xray_observatory(): Result
    {
        return $this->_request(Request::get('xrayObservatory'));
    }

    /**
     * Time-series of observatory probes for one outbound tag.
     *
     * @param string $tag Outbound tag.
     * @param int $bucket Bucket size in seconds.
     * @return Result
     */
    public function xray_observatory_history(string $tag, int $bucket): Result
    {
        return $this->_request(Request::get('xrayObservatoryHistory/' . rawurlencode($tag) . "/$bucket"));
    }

    /**
     * Xray versions available for installation.
     *
     * @return Result
     */
    public function xray_versions(): Result
    {
        return $this->_request(Request::get('getXrayVersion'));
    }

    /**
     * Whether a newer 3x-ui release is available.
     *
     * @return Result
     */
    public function panel_update_info(): Result
    {
        return $this->_request(Request::get('getPanelUpdateInfo'));
    }

    /**
     * Outcome of the most recently launched panel self-update.
     *
     * @return Result
     */
    public function update_status(): Result
    {
        return $this->_request(Request::get('getUpdateStatus'));
    }

    /**
     * The assembled Xray config currently running on this host.
     *
     * @return Result
     */
    public function config_json(): Result
    {
        return $this->_request(Request::get('getConfigJson'));
    }

    /**
     * Download a full database backup.
     *
     * @return Result Raw file content is available through {@see Result::response()}.
     */
    public function get_db(): Result
    {
        return $this->_request(Request::get('getDb'));
    }

    /**
     * Download a cross-engine migration file.
     *
     * @return Result Raw file content is available through {@see Result::response()}.
     */
    public function get_migration(): Result
    {
        return $this->_request(Request::get('getMigration'));
    }

    /**
     * Generate a fresh UUID v4.
     *
     * @return Result
     */
    public function new_uuid(): Result
    {
        return $this->_request(Request::get('getNewUUID'));
    }

    /**
     * This panel's own web TLS certificate and key paths.
     *
     * @return Result
     */
    public function web_cert_files(): Result
    {
        return $this->_request(Request::get('getWebCertFiles'));
    }

    /**
     * Read-only summaries of the nodes this panel manages.
     *
     * @return Result
     */
    public function descendants(): Result
    {
        return $this->_request(Request::get('descendants'));
    }

    /**
     * Generate an X25519 key pair for REALITY.
     *
     * @return Result
     */
    public function new_x25519_cert(): Result
    {
        return $this->_request(Request::get('getNewX25519Cert'));
    }

    /**
     * Generate an ML-DSA-65 key pair.
     *
     * @return Result
     */
    public function new_mldsa65(): Result
    {
        return $this->_request(Request::get('getNewmldsa65'));
    }

    /**
     * Generate an ML-KEM-768 key pair.
     *
     * @return Result
     */
    public function new_mlkem768(): Result
    {
        return $this->_request(Request::get('getNewmlkem768'));
    }

    /**
     * Generate VLESS encryption auth options.
     *
     * @return Result
     */
    public function new_vless_enc(): Result
    {
        return $this->_request(Request::get('getNewVlessEnc'));
    }

    /**
     * Stop the Xray binary.
     *
     * @return Result
     */
    public function stop_xray(): Result
    {
        return $this->_request(Request::post('stopXrayService'));
    }

    /**
     * Restart Xray with the current config.
     *
     * @return Result
     */
    public function restart_xray(): Result
    {
        return $this->_request(Request::post('restartXrayService'));
    }

    /**
     * Download and install an Xray version.
     *
     * @param string $version Xray tag (e.g. `v25.10.31`) or `latest`.
     * @return Result
     */
    public function install_xray(string $version = 'latest'): Result
    {
        return $this->_request(Request::post('installXray/' . rawurlencode($version)));
    }

    /**
     * Self-update the panel.
     *
     * @param bool|null $dev Override the update channel for this run.
     * @return Result
     */
    public function update_panel(?bool $dev = null): Result
    {
        $form = is_null($dev) ? [] : ['dev' => $dev ? 'true' : 'false'];
        return $this->_request(Request::post('updatePanel', form_params: $form));
    }

    /**
     * Switch the panel update channel.
     *
     * @param bool $dev True for the rolling dev channel, false for stable.
     * @return Result
     */
    public function set_update_channel(bool $dev): Result
    {
        return $this->_request(Request::post('setUpdateChannel', form_params: ['dev' => $dev ? 'true' : 'false']));
    }

    /**
     * Refresh the GeoIP/GeoSite data files.
     *
     * @param string|null $file_name One file (`geoip.dat`, `geosite.dat`), all when omitted.
     * @return Result
     */
    public function update_geofile(?string $file_name = null): Result
    {
        return $this->_request(Request::post(
            is_null($file_name) ? 'updateGeofile' : 'updateGeofile/' . rawurlencode($file_name)
        ));
    }

    /**
     * Last N lines of the panel log.
     *
     * @param int $count Number of lines.
     * @param string|null $level Minimum log level filter.
     * @param bool $syslog Read system logs instead of the panel log.
     * @return Result
     */
    public function logs(int $count = 100, ?string $level = null, bool $syslog = false): Result
    {
        return $this->_request(Request::post("logs/$count", form_params: [
            'level' => $level,
            'syslog' => $syslog ? 'true' : 'false',
        ]));
    }

    /**
     * Last N lines of the Xray process log.
     *
     * @param int $count Number of lines.
     * @param string|null $filter Only include lines containing this string.
     * @param bool $show_direct Include direct traffic lines.
     * @param bool $show_blocked Include blocked traffic lines.
     * @param bool $show_proxy Include proxy traffic lines.
     * @return Result
     */
    public function xray_logs(
        int $count = 100,
        ?string $filter = null,
        bool $show_direct = false,
        bool $show_blocked = false,
        bool $show_proxy = false
    ): Result
    {
        return $this->_request(Request::post("xraylogs/$count", form_params: [
            'filter' => $filter,
            'showDirect' => $show_direct ? 'true' : 'false',
            'showBlocked' => $show_blocked ? 'true' : 'false',
            'showProxy' => $show_proxy ? 'true' : 'false',
        ]));
    }

    /**
     * Live AmneziaWG peer activity plus panel event lines.
     *
     * @param int $count Maximum peer rows and event lines.
     * @param string|null $filter Only include rows containing this string.
     * @return Result
     */
    public function amneziawg_logs(int $count = 100, ?string $filter = null): Result
    {
        return $this->_request(Request::post("amneziawglogs/$count", form_params: ['filter' => $filter]));
    }

    /**
     * Restore the panel database from a backup file.
     *
     * @param string $db_full_path Path to a `.db` or `.dump` file.
     * @param bool $keep_host_settings Keep this machine's addresses, certificates and node identity.
     * @return Result
     */
    public function import_db(string $db_full_path, bool $keep_host_settings = true): Result
    {
        $handle = fopen($db_full_path, 'rb');
        if ($handle === false) {
            return Result::make_fail(404, "Unable to open database file: $db_full_path");
        }
        return $this->_request(Request::post('importDB', multipart: [
            ['name' => 'db', 'contents' => $handle],
            ['name' => 'keepHostSettings', 'contents' => $keep_host_settings ? 'true' : 'false'],
        ]));
    }

    /**
     * Generate an ECH key pair and config list for an SNI.
     *
     * @param string $sni Server name to generate the ECH config for.
     * @return Result
     */
    public function new_ech_cert(string $sni): Result
    {
        return $this->_request(Request::post('getNewEchCert', form_params: ['sni' => $sni]));
    }

    /**
     * Compute the SHA-256 hash of a certificate for pinning.
     *
     * @param string|null $cert_file Certificate path on the server.
     * @param string|null $cert_content Inline PEM/DER certificate content.
     * @return Result
     */
    public function cert_hash(?string $cert_file = null, ?string $cert_content = null): Result
    {
        return $this->_request(Request::post('getCertHash', form_params: [
            'certFile' => $cert_file,
            'certContent' => $cert_content,
        ]));
    }

    /**
     * Fetch the live leaf certificate hashes of a remote TLS server.
     *
     * @param string $server `domain` or `domain:port`.
     * @param bool $allow_private Allow private/internal targets.
     * @return Result
     */
    public function remote_cert_hash(string $server, bool $allow_private = false): Result
    {
        return $this->_request(Request::post('getRemoteCertHash', form_params: [
            'server' => $server,
            'allowPrivate' => $allow_private ? 'true' : 'false',
        ]));
    }

    /**
     * Probe one REALITY target for feasibility.
     *
     * @param string $target `host` or `host:port`.
     * @param string|null $sni SNI sent during the handshake.
     * @param int $xver PROXY protocol version expected by the target.
     * @param bool $allow_private Allow private/internal targets.
     * @return Result
     */
    public function scan_reality_target(string $target, ?string $sni = null, int $xver = 0, bool $allow_private = false): Result
    {
        return $this->_request(Request::post('scanRealityTarget', form_params: [
            'target' => $target,
            'sni' => $sni,
            'xver' => $xver,
            'allowPrivate' => $allow_private ? 'true' : 'false',
        ]));
    }

    /**
     * Probe several REALITY targets ranked by feasibility and latency.
     *
     * @param string|array|null $targets Comma-separated tokens or a list of tokens.
     * @return Result
     */
    public function scan_reality_targets(string|array|null $targets = null): Result
    {
        $value = is_array($targets) ? implode(',', $targets) : $targets;
        return $this->_request(Request::post('scanRealityTargets', form_params: ['targets' => $value]));
    }

    /**
     * Fetch the aggregated `inbound_client_ips` table.
     *
     * @return Result
     */
    public function client_ips(): Result
    {
        return $this->_request(Request::get('clientIps'));
    }

    /**
     * Submit recently active client IP timestamps.
     *
     * @param array $client_ips List of `['clientEmail' => string, 'ips' => [['ip' => string, 'timestamp' => int]]]`.
     * @return Result
     */
    public function push_client_ips(array $client_ips): Result
    {
        return $this->_request(Request::post('clientIps', $client_ips));
    }

    /**
     * This API description as an OpenAPI 3 document.
     *
     * @return Result
     */
    public function openapi(): Result
    {
        /** @var Result $result */
        $result = $this->request->panel(Request::get('openapi.json'));
        return $result;
    }

    /**
     * Send one server request.
     *
     * @param array $request Prepared request.
     * @return Result
     */
    private function _request(array $request): Result
    {
        /** @var Result $result */
        $result = $this->request->server($request);
        return $result;
    }
}
