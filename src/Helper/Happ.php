<?php

namespace XUI\Helper;

use InvalidArgumentException;

/**
 * Build Happ-compatible share links from raw Xray inbound settings.
 *
 * This helper is intentionally self-contained: it receives plain arrays (the
 * decoded `settings` / `streamSettings` objects) and returns URI strings, so it
 * can be used with payloads coming from any source, not only this library.
 */
class Happ
{
    /**
     * Generate a Happ-compatible share link from Xray-style settings.
     *
     * @param string $remark
     * @param string $address
     * @param int|string $port
     * @param array $client
     * @param string $protocol
     * @param array $settings
     * @param array $streamSettings
     */
    public static function generate(
        string $remark,
        string $address,
        int|string $port,
        array $client,
        string $protocol,
        array $settings = [],
        array $streamSettings = []
    ): string {
        $protocol = strtolower(trim($protocol));

        return match ($protocol) {
            'vless' => self::vless(
                $remark,
                $address,
                $port,
                $client,
                $settings,
                $streamSettings
            ),

            'vmess' => self::vmess(
                $remark,
                $address,
                $port,
                $client,
                $settings,
                $streamSettings
            ),

            'trojan' => self::trojan(
                $remark,
                $address,
                $port,
                $client,
                $settings,
                $streamSettings
            ),

            'shadowsocks',
            'ss' => self::shadowsocks(
                $remark,
                $address,
                $port,
                $client,
                $settings,
                $streamSettings
            ),

            'hysteria',
            'hysteria2',
            'hy2' => self::hysteria2(
                $remark,
                $address,
                $port,
                $client,
                $settings,
                $streamSettings
            ),

            'wireguard',
            'wg' => self::wireguard(
                $remark,
                $address,
                $port,
                $client,
                $settings,
                $streamSettings
            ),

            'mtproto' => self::mtproto(
                $remark,
                $address,
                $port,
                $client,
                $settings,
                $streamSettings
            ),

            /*
             * These Xray protocols do not have a standard
             * URI scheme that can safely be represented as
             * a normal Happ share link.
             */
            'socks',
            'http',
            'mixed',
            'dokodemo-door',
            'dokodemo',
            'tunnel',
            'tun' => throw new InvalidArgumentException(
                "Protocol '{$protocol}' has no standard Xray/Happ share-link scheme."
            ),

            default => throw new InvalidArgumentException(
                "Unsupported protocol: {$protocol}"
            ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | VLESS
    |--------------------------------------------------------------------------
    */

    /**
     * Build a VLESS share link.
     *
     * @param string $remark Input value.
     * @param string $address Input value.
     * @param int|string $port Input value.
     * @param array $client Input value.
     * @param array $settings Input value.
     * @param array $streamSettings Input value.
     * @return string
     */
    public static function vless(
        string $remark,
        string $address,
        int|string $port,
        array $client,
        array $settings,
        array $streamSettings
    ): string {
        $uuid = self::firstNonEmpty(
            $client['uuid'] ?? null,
            $client['id'] ?? null
        );

        if (!$uuid) {
            throw new InvalidArgumentException(
                'VLESS client uuid is missing.'
            );
        }

        $query = [];

        /*
         * VLESS protocol settings.
         */
        $query['encryption'] = self::firstNonEmpty(
            $settings['encryption'] ?? null,
            'none'
        );

        /*
         * Client-specific flow.
         */
        if (!empty($client['flow'])) {
            $query['flow'] = $client['flow'];
        }

        /*
         * Transport + TLS / Reality.
         */
        self::addStreamParameters(
            $query,
            $streamSettings
        );

        return self::buildUri(
            'vless',
            rawurlencode((string) $uuid) .
            '@' .
            self::formatAddress($address),
            $port,
            $query,
            $remark
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VMESS
    |--------------------------------------------------------------------------
    */

    /**
     * Build a VMess share link.
     *
     * @param string $remark Input value.
     * @param string $address Input value.
     * @param int|string $port Input value.
     * @param array $client Input value.
     * @param array $settings Input value.
     * @param array $streamSettings Input value.
     * @return string
     */
    public static function vmess(
        string $remark,
        string $address,
        int|string $port,
        array $client,
        array $settings,
        array $streamSettings
    ): string {
        $uuid = self::firstNonEmpty(
            $client['uuid'] ?? null,
            $client['id'] ?? null
        );

        if (!$uuid) {
            throw new InvalidArgumentException(
                'VMess client uuid is missing.'
            );
        }

        $network = strtolower(
            self::firstNonEmpty(
                $streamSettings['network'] ?? null,
                'tcp'
            )
        );

        $security = strtolower(
            self::firstNonEmpty(
                $streamSettings['security'] ?? null,
                'none'
            )
        );

        $config = [
            'v' => '2',
            'ps' => $remark,
            'add' => $address,
            'port' => (string) $port,
            'id' => $uuid,
            'aid' => 0,

            'scy' => self::firstNonEmpty(
                $client['security'] ?? null,
                $settings['security'] ?? null,
                'auto'
            ),

            'net' => $network,
            'type' => 'none',
            'host' => '',
            'path' => '',

            'tls' => $security === 'tls'
                ? 'tls'
                : '',
        ];

        self::addVmessTransport(
            $config,
            $network,
            $streamSettings
        );

        if ($security === 'tls') {
            self::addVmessTls(
                $config,
                $streamSettings['tlsSettings'] ?? []
            );
        }

        /*
         * VMess legacy share format does not have a normal
         * Reality representation.
         */
        $json = json_encode(
            $config,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );

        if ($json === false) {
            throw new InvalidArgumentException(
                'Unable to encode VMess configuration.'
            );
        }

        return 'vmess://' . self::base64Encode($json);
    }


    /**
     * Add TLS options to a VMess share configuration.
     *
     * @param array $config Input value.
     * @param array $tls Input value.
     * @return void
     */
    private static function addVmessTls(
        array &$config,
        array $tls
    ): void {
        if (!empty($tls['serverName'])) {
            $config['sni'] =
                (string) $tls['serverName'];
        }

        if (!empty($tls['fingerprint'])) {
            $config['fp'] =
                (string) $tls['fingerprint'];
        }

        if (!empty($tls['alpn'])) {
            $config['alpn'] =
                self::joinValues($tls['alpn']);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TROJAN
    |--------------------------------------------------------------------------
    */

    /**
     * Build a Trojan share link.
     *
     * @param string $remark Input value.
     * @param string $address Input value.
     * @param int|string $port Input value.
     * @param array $client Input value.
     * @param array $settings Input value.
     * @param array $streamSettings Input value.
     * @return string
     */
    public static function trojan(
        string $remark,
        string $address,
        int|string $port,
        array $client,
        array $settings,
        array $streamSettings
    ): string {
        $password = self::firstNonEmpty(
            $client['password'] ?? null,
            $client['uuid'] ?? null,
            $client['secret'] ?? null
        );

        if (!$password) {
            throw new InvalidArgumentException(
                'Trojan client password is missing.'
            );
        }

        $query = [];

        self::addStreamParameters(
            $query,
            $streamSettings
        );

        return self::buildUri(
            'trojan',
            rawurlencode((string) $password) .
            '@' .
            self::formatAddress($address),
            $port,
            $query,
            $remark
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SHADOWSOCKS
    |--------------------------------------------------------------------------
    */

    /**
     * Build a Shadowsocks share link.
     *
     * @param string $remark Input value.
     * @param string $address Input value.
     * @param int|string $port Input value.
     * @param array $client Input value.
     * @param array $settings Input value.
     * @param array $streamSettings Input value.
     * @return string
     */
    public static function shadowsocks(
        string $remark,
        string $address,
        int|string $port,
        array $client,
        array $settings,
        array $streamSettings
    ): string {
        $password = self::firstNonEmpty(
            $client['password'] ?? null,
            $client['secret'] ?? null,
            $client['auth'] ?? null
        );

        if ($password === null || $password === '') {
            throw new InvalidArgumentException(
                'Shadowsocks client password is missing.'
            );
        }

        $method = self::firstNonEmpty(
            $settings['method'] ?? null,
            $settings['servers'][0]['method'] ?? null,
            'aes-128-gcm'
        );

        /*
         * SIP002.
         */
        $userinfo = self::base64UrlEncode(
            $method . ':' . $password
        );

        return self::buildUri(
            'ss',
            $userinfo .
            '@' .
            self::formatAddress($address),
            $port,
            [],
            $remark
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HYSTERIA2
    |--------------------------------------------------------------------------
    */

    /**
     * Build a Hysteria 2 share link.
     *
     * @param string $remark Input value.
     * @param string $address Input value.
     * @param int|string $port Input value.
     * @param array $client Input value.
     * @param array $settings Input value.
     * @param array $streamSettings Input value.
     * @return string
     */
    public static function hysteria2(
        string $remark,
        string $address,
        int|string $port,
        array $client,
        array $settings,
        array $streamSettings
    ): string {
        $auth = self::firstNonEmpty(
            $client['auth'] ?? null,
            $client['password'] ?? null,
            $client['secret'] ?? null
        );

        if (!$auth) {
            throw new InvalidArgumentException(
                'Hysteria2 authentication credential is missing.'
            );
        }

        $query = [];

        $tls = $streamSettings['tlsSettings'] ?? [];

        if (!empty($tls['serverName'])) {
            $query['sni'] =
                (string) $tls['serverName'];
        }

        if (!empty($tls['fingerprint'])) {
            $query['fp'] =
                (string) $tls['fingerprint'];
        }

        if (!empty($tls['alpn'])) {
            $query['alpn'] =
                self::joinValues($tls['alpn']);
        }

        if (!empty($tls['echConfigList'])) {
            $query['ech'] =
                (string) $tls['echConfigList'];
        }

        if (!empty($tls['verifyPeerCertByName'])) {
            $query['vcn'] =
                (string) $tls['verifyPeerCertByName'];
        }

        if (!empty($tls['pinnedPeerCertSha256'])) {
            $query['pinSHA256'] =
                (string) $tls['pinnedPeerCertSha256'];
        }

        self::addFinalMask(
            $query,
            $streamSettings
        );

        return self::buildUri(
            'hysteria2',
            rawurlencode((string) $auth) .
            '@' .
            self::formatAddress($address),
            $port,
            $query,
            $remark
        );
    }


    /*
    |--------------------------------------------------------------------------
    | WIREGUARD
    |--------------------------------------------------------------------------
    */

    /**
     * Build a WireGuard share link.
     *
     * @param string $remark Input value.
     * @param string $address Input value.
     * @param int|string $port Input value.
     * @param array $client Input value.
     * @param array $settings Input value.
     * @param array $streamSettings Input value.
     * @return string
     */
    public static function wireguard(
        string $remark,
        string $address,
        int|string $port,
        array $client,
        array $settings,
        array $streamSettings
    ): string {
        $privateKey = self::firstNonEmpty(
            $client['privateKey'] ?? null,
            $settings['secretKey'] ?? null,
            $client['secret'] ?? null
        );

        if (!$privateKey) {
            throw new InvalidArgumentException(
                'WireGuard private key is missing.'
            );
        }

        $peerPublicKey = self::firstNonEmpty(
            $client['publicKey'] ?? null,
            $settings['peers'][0]['publicKey'] ?? null
        );

        $preSharedKey = self::firstNonEmpty(
            $client['preSharedKey'] ?? null,
            $settings['peers'][0]['preSharedKey'] ?? null
        );

        $allowedIPs = self::firstNonEmpty(
            $client['allowedIPs'] ?? null,
            $settings['peers'][0]['allowedIPs'] ?? null,
            [
                '0.0.0.0/0',
                '::/0'
            ]
        );

        $keepAlive = self::firstNonEmpty(
            $client['keepAlive'] ?? null,
            $settings['peers'][0]['keepAlive'] ?? null,
            null
        );

        $query = [];

        if ($peerPublicKey) {
            $query['publicKey'] =
                $peerPublicKey;
        }

        $query['allowedIPs'] =
            self::joinValues($allowedIPs);

        if ($preSharedKey) {
            $query['preSharedKey'] =
                $preSharedKey;
        }

        if ($keepAlive !== null &&
            $keepAlive !== '') {
            $query['keepAlive'] =
                (string) $keepAlive;
        }

        if (!empty($settings['mtu'])) {
            $query['mtu'] =
                (string) $settings['mtu'];
        }

        if (!empty($settings['reserved'])) {
            $query['reserved'] =
                self::joinValues(
                    $settings['reserved']
                );
        }

        return self::buildUri(
            'wireguard',
            rawurlencode((string) $privateKey) .
            '@' .
            self::formatAddress($address),
            $port,
            $query,
            $remark
        );
    }


    /*
    |--------------------------------------------------------------------------
    | MTPROTO
    |--------------------------------------------------------------------------
    */

    /**
     * Build an MTProto share link.
     *
     * @param string $remark Input value.
     * @param string $address Input value.
     * @param int|string $port Input value.
     * @param array $client Input value.
     * @param array $settings Input value.
     * @param array $streamSettings Input value.
     * @return string
     */
    public static function mtproto(
        string $remark,
        string $address,
        int|string $port,
        array $client,
        array $settings,
        array $streamSettings
    ): string {
        $secret = self::firstNonEmpty(
            $client['secret'] ?? null,
            $client['password'] ?? null,
            $client['auth'] ?? null
        );

        if (!$secret) {
            throw new InvalidArgumentException(
                'MTProto secret is missing.'
            );
        }

        $query = [
            'server' => $address,
            'port' => (string) $port,
            'secret' => $secret,
        ];

        return 'tg://proxy?' .
            http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            ) .
            '#' .
            rawurlencode($remark);
    }


    /*
    |--------------------------------------------------------------------------
    | STREAM / TRANSPORT
    |--------------------------------------------------------------------------
    */

    /**
     * Add transport and security query parameters to a share link.
     *
     * @param array $query Input value.
     * @param array $stream Input value.
     * @return void
     */
    private static function addStreamParameters(
        array &$query,
        array $stream
    ): void {
        $network = strtolower(
            self::firstNonEmpty(
                $stream['network'] ?? null,
                'tcp'
            )
        );

        switch ($network) {

            /*
             * ----------------------------------------------------------
             * TCP
             * ----------------------------------------------------------
             */
            case 'tcp':
                $query['type'] = 'tcp';

                self::addTcpParameters(
                    $query,
                    $stream['tcpSettings'] ?? []
                );
            break;


            /*
             * ----------------------------------------------------------
             * mKCP
             * ----------------------------------------------------------
             */
            case 'kcp':
            case 'mkcp':
                $query['type'] = 'kcp';

                self::addKcpParameters(
                    $query,
                    $stream['kcpSettings'] ?? []
                );
            break;


            /*
             * ----------------------------------------------------------
             * WebSocket
             * ----------------------------------------------------------
             */
            case 'ws':
            case 'websocket':
                $query['type'] = 'ws';

                self::addWsParameters(
                    $query,
                    $stream['wsSettings'] ?? []
                );
            break;


            /*
             * ----------------------------------------------------------
             * gRPC
             * ----------------------------------------------------------
             */
            case 'grpc':
                $query['type'] = 'grpc';

                self::addGrpcParameters(
                    $query,
                    $stream['grpcSettings'] ?? []
                );
            break;


            /*
             * ----------------------------------------------------------
             * HTTPUpgrade
             * ----------------------------------------------------------
             */
            case 'httpupgrade':
                $query['type'] = 'httpupgrade';

                self::addHttpUpgradeParameters(
                    $query,
                    $stream['httpupgradeSettings'] ?? []
                );
            break;


            /*
             * ----------------------------------------------------------
             * XHTTP
             * ----------------------------------------------------------
             */
            case 'xhttp':
                $query['type'] = 'xhttp';

                self::addXHttpParameters(
                    $query,
                    $stream['xhttpSettings'] ?? []
                );
            break;


            default:
                throw new InvalidArgumentException(
                    "Unsupported Xray transport: {$network}"
                );
        }


        /*
         * --------------------------------------------------------------
         * Security
         * --------------------------------------------------------------
         */

        $security = strtolower(
            self::firstNonEmpty(
                $stream['security'] ?? null,
                'none'
            )
        );

        switch ($security) {

            case '':
            case 'none':
                $query['security'] = 'none';
            break;


            case 'tls':
                self::addTlsParameters(
                    $query,
                    $stream['tlsSettings'] ?? []
                );
            break;


            case 'reality':
                self::addRealityParameters(
                    $query,
                    $stream['realitySettings'] ?? []
                );
            break;


            default:
                $query['security'] = $security;
            break;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TCP
    |--------------------------------------------------------------------------
    */

    /**
     * Add TCP camouflage parameters to the query.
     *
     * @param array $query Input value.
     * @param array $tcp Input value.
     * @return void
     */
    private static function addTcpParameters(
        array &$query,
        array $tcp
    ): void {
        $header = $tcp['header'] ?? [];

        $headerType = strtolower(
            self::firstNonEmpty(
                $header['type'] ?? null,
                'none'
            )
        );

        if ($headerType === 'none') {
            return;
        }

        if ($headerType === 'http') {
            $query['headerType'] = 'http';

            $request =
                $header['request'] ?? [];

            if (!empty($request['path'])) {
                $query['path'] =
                    self::joinValues(
                        $request['path']
                    );
            }

            /*
             * TCP HTTP camouflage may put the host
             * inside request.headers.
             */
            if (!empty($request['headers'])) {
                $host =
                    self::extractHeaderHost(
                        $request['headers']
                    );

                if (
                    $host !== null &&
                    $host !== ''
                ) {
                    $query['host'] = $host;
                }
            }

            return;
        }

        $query['headerType'] =
            $headerType;
    }


    /*
    |--------------------------------------------------------------------------
    | mKCP
    |--------------------------------------------------------------------------
    */

    /**
     * Add mKCP parameters to the query.
     *
     * @param array $query Input value.
     * @param array $kcp Input value.
     * @return void
     */
    private static function addKcpParameters(
        array &$query,
        array $kcp
    ): void {
        if (
            isset($kcp['seed']) &&
            $kcp['seed'] !== ''
        ) {
            $query['seed'] =
                (string) $kcp['seed'];
        }

        if (!empty($kcp['header'])) {
            $header =
                $kcp['header'];

            if (isset($header['type'])) {
                $query['headerType'] =
                    (string) $header['type'];
            }
        }

        foreach ([
                     'mtu',
                     'tti',
                     'uplinkCapacity',
                     'downlinkCapacity',
                     'congestion',
                     'readBufferSize',
                     'writeBufferSize',
                 ] as $field) {

            if (
                array_key_exists($field, $kcp) &&
                $kcp[$field] !== ''
            ) {
                $query[$field] =
                    (string) $kcp[$field];
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | WebSocket
    |--------------------------------------------------------------------------
    */

    /**
     * Add WebSocket parameters to the query.
     *
     * @param array $query Input value.
     * @param array $ws Input value.
     * @return void
     */
    private static function addWsParameters(
        array &$query,
        array $ws
    ): void {
        $query['type'] = 'ws';

        /*
         * Path.
         */
        $query['path'] = self::firstNonEmpty(
            $ws['path'] ?? null,
            '/'
        );

        /*
         * IMPORTANT:
         *
         * Prefer:
         *
         * wsSettings.host
         *
         * and then:
         *
         * wsSettings.headers.Host
         *
         * Also supports lowercase host.
         */
        $host = self::firstNonEmpty(
            $ws['host'] ?? null,
            self::extractHeaderHost(
                $ws['headers'] ?? []
            )
        );

        if (
            $host !== null &&
            $host !== ''
        ) {
            $query['host'] = $host;
        }

        /*
         * WebSocket heartbeat.
         */
        if (isset($ws['heartbeatPeriod'])) {
            $query['heartbeatPeriod'] =
                (string) $ws['heartbeatPeriod'];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | gRPC
    |--------------------------------------------------------------------------
    */

    /**
     * Add gRPC parameters to the query.
     *
     * @param array $query Input value.
     * @param array $grpc Input value.
     * @return void
     */
    private static function addGrpcParameters(
        array &$query,
        array $grpc
    ): void {
        if (
            isset($grpc['serviceName']) &&
            $grpc['serviceName'] !== ''
        ) {
            $query['serviceName'] =
                (string) $grpc['serviceName'];
        }

        /*
         * gRPC uses authority instead of host.
         */
        if (
            isset($grpc['authority']) &&
            $grpc['authority'] !== ''
        ) {
            $query['authority'] =
                (string) $grpc['authority'];
        }

        if (!empty($grpc['multiMode'])) {
            $query['mode'] = 'multi';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | HTTPUpgrade
    |--------------------------------------------------------------------------
    */

    /**
     * Add HTTPUpgrade parameters to the query.
     *
     * @param array $query Input value.
     * @param array $http Input value.
     * @return void
     */
    private static function addHttpUpgradeParameters(
        array &$query,
        array $http
    ): void {
        $query['type'] =
            'httpupgrade';

        $query['path'] =
            self::firstNonEmpty(
                $http['path'] ?? null,
                '/'
            );

        /*
         * IMPORTANT:
         *
         * Prefer:
         *
         * httpupgradeSettings.host
         *
         * and then headers.Host.
         */
        $host = self::firstNonEmpty(
            $http['host'] ?? null,
            self::extractHeaderHost(
                $http['headers'] ?? []
            )
        );

        if (
            $host !== null &&
            $host !== ''
        ) {
            $query['host'] =
                $host;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | XHTTP
    |--------------------------------------------------------------------------
    */

    /**
     * Add XHTTP parameters to the query.
     *
     * @param array $query Input value.
     * @param array $xhttp Input value.
     * @return void
     */
    private static function addXHttpParameters(
        array &$query,
        array $xhttp
    ): void {
        $query['type'] =
            'xhttp';

        $query['path'] =
            self::firstNonEmpty(
                $xhttp['path'] ?? null,
                '/'
            );

        /*
         * IMPORTANT:
         *
         * Prefer:
         *
         * xhttpSettings.host
         *
         * and then headers.Host.
         */
        $host = self::firstNonEmpty(
            $xhttp['host'] ?? null,
            self::extractHeaderHost(
                $xhttp['headers'] ?? []
            )
        );

        if (
            $host !== null &&
            $host !== ''
        ) {
            $query['host'] =
                $host;
        }

        /*
         * XHTTP mode.
         */
        if (!empty($xhttp['mode'])) {
            $query['mode'] =
                (string) $xhttp['mode'];
        }

        /*
         * XHTTP advanced options.
         */
        foreach ([
                     'xPaddingBytes',
                     'scMaxEachPostBytes',
                     'scMinPostsIntervalMs',
                     'uplinkChunkSize',
                 ] as $field) {

            if (
                array_key_exists($field, $xhttp) &&
                $xhttp[$field] !== ''
            ) {
                $query[$field] =
                    (string) $xhttp[$field];
            }
        }

        /*
         * XHTTP extra.
         */
        if (
            isset($xhttp['extra']) &&
            $xhttp['extra'] !== ''
        ) {
            $extra =
                $xhttp['extra'];

            if (is_array($extra)) {
                $extra = json_encode(
                    $extra,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                );
            }

            if (
                $extra !== false &&
                $extra !== ''
            ) {
                $query['extra'] =
                    $extra;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TLS
    |--------------------------------------------------------------------------
    */

    /**
     * Add TLS query parameters.
     *
     * @param array $query Input value.
     * @param array $tls Input value.
     * @return void
     */
    private static function addTlsParameters(
        array &$query,
        array $tls
    ): void {
        $query['security'] = 'tls';

        if (!empty($tls['serverName'])) {
            $query['sni'] =
                (string) $tls['serverName'];
        }

        if (!empty($tls['fingerprint'])) {
            $query['fp'] =
                (string) $tls['fingerprint'];
        }

        if (!empty($tls['alpn'])) {
            $query['alpn'] =
                self::joinValues(
                    $tls['alpn']
                );
        }

        if (!empty($tls['echConfigList'])) {
            $query['ech'] =
                (string) $tls['echConfigList'];
        }

        if (!empty($tls['verifyPeerCertByName'])) {
            $query['vcn'] =
                (string) $tls['verifyPeerCertByName'];
        }

        if (!empty($tls['pinnedPeerCertSha256'])) {
            $query['pinSHA256'] =
                (string) $tls['pinnedPeerCertSha256'];
        }

        if (isset($tls['allowInsecure'])) {
            $query['allowInsecure'] =
                !empty($tls['allowInsecure'])
                    ? '1'
                    : '0';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REALITY
    |--------------------------------------------------------------------------
    */

    /**
     * Add REALITY query parameters.
     *
     * @param array $query Input value.
     * @param array $reality Input value.
     * @return void
     */
    private static function addRealityParameters(
        array &$query,
        array $reality
    ): void {
        $query['security'] =
            'reality';

        /*
         * SNI / serverName.
         */
        $serverName = self::firstNonEmpty(
            $reality['serverName'] ?? null,
            self::firstArrayValue(
                $reality['serverNames'] ?? []
            )
        );

        if (
            $serverName !== null &&
            $serverName !== ''
        ) {
            $query['sni'] =
                (string) $serverName;
        }

        /*
         * REALITY public key.
         */
        $publicKey = self::firstNonEmpty(
            $reality['publicKey'] ?? null,
            $reality['settings']['publicKey'] ?? null
        );

        if (
            $publicKey !== null &&
            $publicKey !== ''
        ) {
            $query['pbk'] =
                (string) $publicKey;
        }

        /*
         * REALITY short ID.
         */
        $shortId = self::firstNonEmpty(
            $reality['shortId'] ?? null,
            self::firstArrayValue(
                $reality['shortIds'] ?? []
            )
        );

        if (
            $shortId !== null &&
            $shortId !== ''
        ) {
            $query['sid'] =
                (string) $shortId;
        }

        /*
         * Fingerprint.
         */
        $fingerprint =
            self::firstNonEmpty(
                $reality['fingerprint'] ?? null,
                $reality['settings']['fingerprint'] ?? null,
                'chrome'
            );

        if ($fingerprint !== '') {
            $query['fp'] =
                (string) $fingerprint;
        }

        /*
         * SpiderX.
         */
        if (!empty($reality['spiderX'])) {
            $query['spx'] =
                (string) $reality['spiderX'];
        }

        /*
         * MLDSA.
         */
        if (!empty($reality['mldsa65Verify'])) {
            $query['pqv'] =
                (string) $reality['mldsa65Verify'];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | VMESS TRANSPORT
    |--------------------------------------------------------------------------
    */

    /**
     * Add transport fields to a VMess share configuration.
     *
     * @param array $config Input value.
     * @param string $network Input value.
     * @param array $stream Input value.
     * @return void
     */
    private static function addVmessTransport(
        array &$config,
        string $network,
        array $stream
    ): void {
        switch ($network) {

            /*
             * TCP
             */
            case 'tcp':
                $tcp =
                    $stream['tcpSettings'] ?? [];

                $header =
                    $tcp['header'] ?? [];

                $headerType =
                    strtolower(
                        self::firstNonEmpty(
                            $header['type'] ?? null,
                            'none'
                        )
                    );

                if (
                    $headerType === 'http'
                ) {
                    $config['type'] =
                        'http';

                    $request =
                        $header['request'] ?? [];

                    if (!empty($request['path'])) {
                        $config['path'] =
                            self::joinValues(
                                $request['path']
                            );
                    }

                    if (!empty($request['headers'])) {
                        $host =
                            self::extractHeaderHost(
                                $request['headers']
                            );

                        if (
                            $host !== null &&
                            $host !== ''
                        ) {
                            $config['host'] =
                                $host;
                        }
                    }
                }

            break;


            /*
             * mKCP
             */
            case 'kcp':
            case 'mkcp':
                $kcp =
                    $stream['kcpSettings'] ?? [];

                $config['type'] =
                    self::firstNonEmpty(
                        $kcp['header']['type'] ?? null,
                        'none'
                    );

                if (!empty($kcp['seed'])) {
                    $config['path'] =
                        (string) $kcp['seed'];
                }

            break;


            /*
             * WebSocket
             */
            case 'ws':
            case 'websocket':
                $ws =
                    $stream['wsSettings'] ?? [];

                $config['path'] =
                    self::firstNonEmpty(
                        $ws['path'] ?? null,
                        '/'
                    );

                /*
                 * IMPORTANT:
                 *
                 * VMess WS host comes from:
                 *   wsSettings.host
                 * or headers.Host
                 */
                $config['host'] =
                    self::firstNonEmpty(
                        $ws['host'] ?? null,
                        self::extractHeaderHost(
                            $ws['headers'] ?? []
                        ),
                        ''
                    );

                if (isset($ws['heartbeatPeriod'])) {
                    $config['heartbeatPeriod'] =
                        (string) $ws['heartbeatPeriod'];
                }

            break;


            /*
             * gRPC
             */
            case 'grpc':
                $grpc =
                    $stream['grpcSettings'] ?? [];

                $config['path'] =
                    (string) (
                        $grpc['serviceName'] ?? ''
                    );

                if (
                    !empty($grpc['authority'])
                ) {
                    $config['authority'] =
                        (string) $grpc['authority'];
                }

                if (!empty($grpc['multiMode'])) {
                    $config['type'] =
                        'multi';
                }

            break;


            /*
             * HTTPUpgrade
             */
            case 'httpupgrade':
                $http =
                    $stream['httpupgradeSettings']
                    ?? [];

                $config['path'] =
                    self::firstNonEmpty(
                        $http['path'] ?? null,
                        '/'
                    );

                $config['host'] =
                    self::firstNonEmpty(
                        $http['host'] ?? null,
                        self::extractHeaderHost(
                            $http['headers'] ?? []
                        ),
                        ''
                    );

            break;


            /*
             * XHTTP
             */
            case 'xhttp':
                $xhttp =
                    $stream['xhttpSettings']
                    ?? [];

                $config['path'] =
                    self::firstNonEmpty(
                        $xhttp['path'] ?? null,
                        '/'
                    );

                $config['host'] =
                    self::firstNonEmpty(
                        $xhttp['host'] ?? null,
                        self::extractHeaderHost(
                            $xhttp['headers'] ?? []
                        ),
                        ''
                    );

                if (!empty($xhttp['mode'])) {
                    $config['mode'] =
                        (string) $xhttp['mode'];
                }

                foreach ([
                             'xPaddingBytes',
                             'scMaxEachPostBytes',
                             'scMinPostsIntervalMs',
                             'uplinkChunkSize',
                         ] as $field) {

                    if (
                        array_key_exists(
                            $field,
                            $xhttp
                        ) &&
                        $xhttp[$field] !== ''
                    ) {
                        $config[$field] =
                            (string) $xhttp[$field];
                    }
                }

            break;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | HYSTERIA FINAL MASK
    |--------------------------------------------------------------------------
    */

    /**
     * Add the FINALMASK query parameter.
     *
     * @param array $query Input value.
     * @param array $stream Input value.
     * @return void
     */
    private static function addFinalMask(
        array &$query,
        array $stream
    ): void {
        if (empty($stream['finalmask'])) {
            return;
        }

        $json = json_encode(
            $stream['finalmask'],
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        );

        if ($json !== false) {
            $query['fm'] =
                $json;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | URI
    |--------------------------------------------------------------------------
    */

    /**
     * Build a share URI from its parts.
     *
     * @param string $scheme Input value.
     * @param string $userinfoHost Input value.
     * @param int|string $port Input value.
     * @param array $query Input value.
     * @param string $remark Input value.
     * @return string
     */
    private static function buildUri(
        string $scheme,
        string $userinfoHost,
        int|string $port,
        array $query,
        string $remark
    ): string {
        $url =
            $scheme .
            '://' .
            $userinfoHost .
            ':' .
            (string) $port;

        if ($query) {
            $url .= '?' .
                http_build_query(
                    $query,
                    '',
                    '&',
                    PHP_QUERY_RFC3986
                );
        }

        if ($remark !== '') {
            $url .= '#' .
                rawurlencode($remark);
        }

        return $url;
    }


    /*
    |--------------------------------------------------------------------------
    | ADDRESS
    |--------------------------------------------------------------------------
    */

    /**
     * Format an address for a share URI (IPv6 in brackets).
     *
     * @param string $address Input value.
     * @return string
     */
    private static function formatAddress(
        string $address
    ): string {
        /*
         * IPv6:
         *
         * 2001:db8::1
         *
         * becomes:
         *
         * [2001:db8::1]
         */
        if (
            filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6
            )
        ) {
            return '[' . $address . ']';
        }

        return $address;
    }


    /*
    |--------------------------------------------------------------------------
    | HEADER HOST
    |--------------------------------------------------------------------------
    */

    /**
     * Extract a Host header value from a headers map.
     *
     * @param array $headers Input value.
     * @return ?string
     */
    private static function extractHeaderHost(
        array $headers
    ): ?string {
        foreach ($headers as $key => $value) {

            $normalizedKey =
                strtolower(
                    trim((string) $key)
                );

            /*
             * Support:
             *
             * Host
             * host
             * HOST
             * :authority
             */
            if (
                $normalizedKey === 'host' ||
                $normalizedKey === ':authority'
            ) {
                return self::joinValues($value);
            }
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Return the first non-null, non-empty value.
     *
     * @param mixed $values Input value.
     * @return mixed
     */
    private static function firstNonEmpty(
        mixed ...$values
    ): mixed {
        foreach ($values as $value) {
            if (
                $value !== null &&
                $value !== ''
            ) {
                return $value;
            }
        }

        return null;
    }


    /**
     * Return the first value of an array.
     *
     * @param mixed $value Input value.
     * @return mixed
     */
    private static function firstArrayValue(
        mixed $value
    ): mixed {
        if (
            is_array($value) &&
            $value !== []
        ) {
            return reset($value);
        }

        return null;
    }


    /**
     * Join a list of values with commas.
     *
     * @param mixed $value Input value.
     * @return string
     */
    private static function joinValues(
        mixed $value
    ): string {
        if (is_array($value)) {
            return implode(
                ',',
                array_map(
                    static fn($v): string =>
                    (string) $v,
                    $value
                )
            );
        }

        return (string) $value;
    }


    /*
    |--------------------------------------------------------------------------
    | BASE64
    |--------------------------------------------------------------------------
    */

    /**
     * Base64 encode a string.
     *
     * @param string $value Input value.
     * @return string
     */
    private static function base64Encode(
        string $value
    ): string {
        return base64_encode($value);
    }


    /**
     * Base64url encode a string without padding.
     *
     * @param string $value Input value.
     * @return string
     */
    private static function base64UrlEncode(
        string $value
    ): string {
        return rtrim(
            strtr(
                base64_encode($value),
                '+/',
                '-_'
            ),
            '='
        );
    }
}
