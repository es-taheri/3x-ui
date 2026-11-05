<?php

namespace XUI\Handler;

use JSON\json;
use XUI\Helper\Statics;

/**
 * Immutable value object describing one HTTP round-trip to the panel.
 *
 * It combines the Guzzle result (transport metadata) with the decoded 3x-ui
 * response envelope (`{success, msg, obj}`), so callers only need to inspect a
 * single object:
 *
 * ```php
 * $result = $xui->inbounds->list();
 * if ($result->ok && $result->success) {
 *     $items = $result->response()->obj;
 * }
 * ```
 *
 * @property-read bool $ok True when the HTTP request itself completed.
 * @property-read bool $success True when the panel answered with `success: true`.
 */
class Result
{
    /** True when the HTTP request was transported and answered successfully. */
    public bool $ok;

    /** True when the panel response envelope has `success: true`. */
    public bool $success = false;

    /** Raw HTTP status code (0 for connection errors). */
    public int $code = 0;

    /** Raw response body, or null when the request failed. */
    private ?string $raw = null;

    /** Decoded response body (array/object/scalar) or null when not JSON. */
    private mixed $decoded = null;

    /** Transport error message, or null on success. */
    public ?string $error = null;

    /** Response headers, or null when the request failed. */
    public ?array $header = null;

    /** Response body size in bytes. */
    public int $size = 0;

    /** Request duration in seconds. */
    public float $time_taken = 0.0;

    /**
     * @param array{ok?:bool,code?:int,response?:string|null,header?:array|null,size?:int,time_taken?:float,error?:string|null} $result
     */
    public function __construct(
        /** @var array{ok?:bool,code?:int,response?:string|null,header?:array|null,size?:int,time_taken?:float,error?:string|null} Raw request result. */
        private readonly array $result
    )
    {
        $this->ok = (bool)($result['ok'] ?? false);
        $this->code = (int)($result['code'] ?? 0);
        $this->error = $result['error'] ?? null;
        $this->header = $result['header'] ?? null;
        $this->size = (int)($result['size'] ?? 0);
        $this->time_taken = (float)($result['time_taken'] ?? 0.0);

        $response = $result['response'] ?? null;
        if (is_string($response) && $response !== '') {
            $this->raw = $response;
            $this->decoded = json_decode($response, true);
            if (is_array($this->decoded) && array_key_exists('success', $this->decoded)) {
                $this->success = (bool)$this->decoded['success'];
            } elseif ($this->ok) {
                // A 200 response that is actually an HTML login page is not success.
                $this->success = !str_starts_with(ltrim($this->raw), '<');
            }
        } elseif ($this->ok) {
            // Endpoints that stream empty files still count as successful.
            $this->success = true;
        }
    }

    /**
     * HTTP transport details of a successful request.
     *
     * @return object{code:int,header:array|null,size:int,time_taken:float}|null
     */
    public function success(): object|null
    {
        if (!$this->ok) {
            return null;
        }
        return (object)[
            'code' => $this->code,
            'header' => $this->header,
            'size' => $this->size,
            'time_taken' => $this->time_taken,
        ];
    }

    /**
     * Decoded panel response.
     *
     * @param int $type One of the {@see Statics::OUTPUT_*} constants.
     * @return mixed
     */
    public function response(int $type = Statics::OUTPUT_OBJECT): mixed
    {
        if ($this->decoded === null) {
            return $this->raw;
        }
        return Statics::output($this->decoded, $type);
    }

    /**
     * Value of the `obj` member of the response envelope, if any.
     *
     * @param int $type One of the {@see Statics::OUTPUT_*} constants.
     * @return mixed
     */
    public function obj(int $type = Statics::OUTPUT_OBJECT): mixed
    {
        if (!is_array($this->decoded) || !array_key_exists('obj', $this->decoded)) {
            return null;
        }
        $obj = $this->decoded['obj'];
        if (is_null($obj)) {
            return null;
        }
        if (!is_array($obj) && !is_object($obj)) {
            return $obj;
        }
        return Statics::output($obj, $type);
    }

    /**
     * Human readable message sent by the panel.
     *
     * @return string
     */
    public function msg(): string
    {
        return is_array($this->decoded) ? (string)($this->decoded['msg'] ?? '') : '';
    }

    /**
     * Transport or panel error details, or null when nothing failed.
     *
     * @return object{code:int,error:string}|null
     */
    public function fail(): object|null
    {
        if ($this->ok && $this->success) {
            return null;
        }
        return (object)[
            'code' => $this->code,
            'error' => $this->error ?? $this->msg(),
        ];
    }

    /**
     * Raw response body exactly as received.
     *
     * @return string|null
     */
    public function raw(): ?string
    {
        return $this->raw;
    }

    /**
     * Build a successful result from an already-decoded response envelope.
     *
     * @param array|object|string|null $response Response envelope.
     * @param int $code HTTP status code.
     * @param array $header Response headers.
     * @param int $size Body size in bytes.
     * @param float $time_taken Request duration in seconds.
     * @return Result
     */
    public static function make_ok(
        array|object|string|null $response = null,
        int $code = 200,
        array $header = [],
        int $size = 0,
        float $time_taken = 0.0
    ): Result
    {
        $raw = $response === null ? '' : (is_string($response) ? $response : json::to_json($response));
        return new self([
            'ok' => true,
            'response' => $raw,
            'code' => $code,
            'header' => $header,
            'size' => $size,
            'time_taken' => $time_taken,
        ]);
    }

    /**
     * Build a failed result without touching the network.
     *
     * @param int $code HTTP-like status code describing the failure.
     * @param string $error Error message.
     * @param array|object|string|null $response Optional response envelope.
     * @return Result
     */
    public static function make_fail(int $code, string $error, array|object|string|null $response = null): Result
    {
        return new self([
            'ok' => false,
            'code' => $code,
            'error' => $error,
            'response' => $response === null ? null : (is_string($response) ? $response : json::to_json($response)),
        ]);
    }

    /**
     * Build a panel response envelope array.
     *
     * @param bool $success Whether the operation succeeded.
     * @param array|object|string|null $obj Response payload.
     * @param string $msg Optional message.
     * @return array{success:bool,obj:mixed,msg:string}
     */
    public static function make_response(bool $success, array|object|string|null $obj, string $msg = ''): array
    {
        return ['success' => $success, 'obj' => $obj, 'msg' => $msg];
    }
}
