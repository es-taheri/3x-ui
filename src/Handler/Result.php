<?php

namespace XUI\Handler;


use XUI\Helper\Statics;
use JSON\json;

/**
 *
 * @property bool $ok Identifies HTTP request SUCCESS or NOT
 * @property bool $success Identifies 3x-ui api response OK or NOT
 */
class Result
{
    public bool $ok;
    public bool $success = false;
    private readonly string|object|array $response;

    public function __construct(private readonly array $result)
    {
        $this->ok = $result['ok'];
        if (!empty(@$result['response'])):
            $this->response = $result['response'];
            $this->success = json::to_object($result['response'])->success;
        endif;
    }

    /**
     * HTTP request success details
     * @return object{code:int,header:object,size:int}|null
     */
    public function success(): object|null
    {
        if ($this->success)
            return json::to_object([
                'code' => $this->result['code'],
                'header' => $this->result['header'],
                'size' => $this->result['size'],
                'time_taken' => $this->result['time_taken'],
            ]);
        else
            return null;
    }

    /**
     * 3x-ui response
     * @param int $type
     * @return object{success:true,obj:object,msg:string}|object{success:false,msg:string}|array|string|null
     */
    public function response(int $type = Statics::OUTPUT_OBJECT): object|array|string|null
    {
        return (isset($this->response)) ? Statics::output($this->response, $type) : null;
    }

    /**
     * HTTP request error details
     * @return object{code:int,error:string}|null
     */
    public function fail(): object|null
    {
        if (!$this->success)
            return json::to_object([
                'code' => $this->result['code'],
                'error' => $this->result['error'],
            ]);
        else
            return null;
    }

    public static function make_ok(array $response = null, int $code = 200, array $header = [], int $size = 0, float $time_taken = 0): Result
    {
        return new self(['ok' => true, 'response' => $response, 'code' => $code, 'header' => $header, 'size' => $size]);
    }

    public static function make_fail(int $code, string $error, array $response = null): Result
    {
        return new self(['ok' => false, 'code' => 404, 'error' => 'routing rule not found', 'response' => null]);
    }

    public static function make_response(bool $success, array|object|string $obj, string $msg = ''): array
    {
        return ['success' => $success, 'obj' => $obj, 'msg' => $msg];
    }
}