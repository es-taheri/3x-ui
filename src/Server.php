<?php

namespace XUI;

use XUI\Handler\Request;
use XUI\Handler\Result;

class Server
{
    public function __construct(private readonly Request $request)
    {
    }

    public function status(): Result
    {
        return $this->_request(Request::get('status'));
    }

    public function cpu_history(int $bucket): Result
    {
        return $this->_request(Request::get("cpuHistory/$bucket"));
    }

    public function history(string $metric, int $bucket): Result
    {
        return $this->_request(Request::get("history/$metric/$bucket"));
    }

    public function xray_metrics_state(string $metric = null, int $bucket = null): Result
    {
        return $this->_request(isset($metric, $bucket) ?
            Request::get("xrayMetricsHistory/$metric/$bucket") :
            Request::get('xrayMetricsState'));
    }
    public function xray_observatory(string $tag = null, int $bucket = null): Result
    {
        return $this->_request(isset($tag, $bucket) ?
            Request::get("xrayObservatoryHistory/$tag/$bucket") :
            Request::get('xrayObservatory'));
    }

    private function _request(array $request): Result
    {
        return $this->request->server($request);
    }

    private function _requests(array $requests): array
    {
        return $this->request->server(requests: $requests);
    }

}