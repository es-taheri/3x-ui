<?php

namespace XUI\Handler;

use JSON\json;

/**
 * Names of the messages pushed by the panel WebSocket (`/ws`).
 *
 * The stream itself must be consumed by a WebSocket client; this class only
 * names the event types and decodes one message envelope. Every message has the
 * shape `{type, payload, time}` where `time` is Unix milliseconds.
 *
 * ```php
 * $message = WebSocketEvent::decode($raw);
 * if ($message['type'] === WebSocketEvent::STATUS) {
 *     $status = $message['payload'];
 * }
 * ```
 */
class WebSocketEvent
{
    /** Server health snapshot pushed every two seconds. */
    public const STATUS = 'status';

    /** Live traffic deltas plus online/last-online maps. */
    public const TRAFFIC = 'traffic';

    /** Absolute client counters and/or inbound summaries. */
    public const CLIENT_STATS = 'client_stats';

    /** Full inbound list after an inbound mutation. */
    public const INBOUNDS = 'inbounds';

    /** Current outbound traffic rows. */
    public const OUTBOUNDS = 'outbounds';

    /** Current node tree after the heartbeat probe cycle. */
    public const NODES = 'nodes';

    /** In-panel notification emitted by a server action. */
    public const NOTIFICATION = 'notification';

    /** Xray process state change. */
    public const XRAY_STATE = 'xray_state';

    /** Requests a REST re-fetch; the payload `type` names what to refresh. */
    public const INVALIDATE = 'invalidate';

    /** Invalidate payload type: refresh clients. */
    public const INVALIDATE_CLIENTS = 'clients';

    /** Xray state: running. */
    public const XRAY_RUNNING = 'running';

    /** Xray state: stopped. */
    public const XRAY_STOP = 'stop';

    /** Xray state: error. */
    public const XRAY_ERROR = 'error';

    /** Notification level: success. */
    public const LEVEL_SUCCESS = 'success';

    /** Notification level: warning. */
    public const LEVEL_WARNING = 'warning';

    /**
     * Decode one WebSocket message.
     *
     * @param string|array|object $message Raw JSON text or an already-decoded message.
     * @return array{type:string,payload:mixed,time:int}
     */
    public static function decode(string|array|object $message): array
    {
        $data = match (true) {
            is_array($message) => $message,
            is_object($message) => (array)$message,
            default => (array)(json_decode($message, true) ?: []),
        };

        return [
            'type' => (string)($data['type'] ?? ''),
            'payload' => $data['payload'] ?? null,
            'time' => (int)($data['time'] ?? 0),
        ];
    }

    /**
     * Normalise a decoded payload to an array.
     *
     * @param mixed $payload Message payload.
     * @return array
     */
    public static function payload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }
        if (is_object($payload)) {
            return json::to_array($payload) ?: [];
        }
        return [];
    }
}
