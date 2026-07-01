<?php

namespace XUI\Helper;

use JSON\json;

class V2rayng
{
    /**
     * VMess configurations in V2rayNG are shared as URLs with the vmess:// protocol prefix, followed by base64-encoded JSON configuration data.
     * @param string $remark Remark/profile name (server description)
     * @param string $address Server address (domain or IP)
     * @param int $port Server port
     * @param string $uuid UUID for client authentication
     * @param string $transport Network/transport protocol (tcp, ws, grpc, h2, quic)
     * @param string $comflage_type Camouflage type (http, none)
     * @param string $security Security/encryption method (auto, aes-128-gcm, chacha20-poly1305, etc.)
     * @param string $host Host header value (for WebSocket connections)
     * @param string $path WebSocket path or gRPC service name
     * @param string $tls Whether to use TLS ("tls" or empty)
     * @param string|null $alpn tls alpn http version
     * @param string|null $sni Server Name Indication for TLS
     * @param string|null $finger_print TLS fingerprint type
     * @param bool $insecure accept insecure on tls enabled?
     * @param int $alter_id AlterID (deprecated in newer versions, often set to 0)
     * @param int $version Protocol version (usually "2")
     * @return string
     */
    public static function vmess(
        string $remark,
        string $address,
        int    $port,
        string $uuid,
        string $transport,
        string $comflage_type = 'none',
        string $security = 'auto',
        string $host = '',
        string $path = '/',
        string $tls = '',
        string $alpn = null,
        string $sni = null,
        string $finger_print = null,
        bool   $insecure = true,
        int    $alter_id = 0,
        int    $version = 2
    )
    {
        $structure = [
            'v' => $version,
            'ps' => $remark,
            'add' => $address,
            'port' => $port,
            'id' => $uuid,
            'scy' => $security,
            'aid' => $alter_id,
            'net' => $transport,
            'type' => $comflage_type,
            'host' => $host,
            'path' => $path,
            'sni' => $sni,
            'alpn' => $alpn,
            'tls' => $tls,
            'fp' => $finger_print,
            'insecure' => $insecure,
        ];
        return 'vmess://' . base64_encode(json::_out($structure));
    }

    /**
     * VLESS configurations in the V2rayNG follow this standardized format: `vless://UUID@SERVER:PORT?PARAMS#REMARK`
     * @param string $remark Remark/profile name (server description)
     * @param string $address Server address (domain or IP)
     * @param int $port Server port
     * @param string $uuid UUID for client authentication
     * @param string $transport Network/transport protocol (tcp, ws, grpc, h2, quic)
     * @param string $comflage_type Camouflage type (http, none)
     * @param string $security Security/encryption method (auto, aes-128-gcm, chacha20-poly1305, etc.)
     * @param string $host Host header value (for WebSocket connections)
     * @param string $path WebSocket path or gRPC service name
     * @param string $tls Whether to use TLS ("tls" or empty)
     * @param string|null $alpn tls alpn http version
     * @param string|null $sni Server Name Indication for TLS
     * @param string|null $finger_print TLS fingerprint type
     * @param bool $insecure accept insecure on tls enabled?
     * @param string $flow Traffic flow control
     * @return string
     */

    public static function vless(
        string $remark,
        string $address,
        int    $port,
        string $uuid,
        string $transport,
        string $comflage_type = 'none',
        string $security = 'auto',
        string $host = '',
        string $path = '/',
        string $tls = '',
        string $alpn = null,
        string $sni = null,
        string $finger_print = null,
        bool   $insecure = true,
        string $flow = ''
    ): string
    {
        $remark = urlencode($remark);
        $query = http_build_query([
            'scy' => $security,
            'flow' => $flow,
            'net' => $transport,
            'type' => $comflage_type,
            'host' => $host,
            'path' => urlencode($path),
            'sni' => $sni,
            'alpn' => $alpn,
            'tls' => $tls,
            'fp' => $finger_print,
            'insecure' => $insecure,
        ]);
        $structure = "$uuid@$address:$port?$query#$remark";
        return 'vmess://' . $structure;
    }
}