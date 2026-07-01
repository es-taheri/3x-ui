<?php

namespace XUI\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Promise\Utils;
use JSON\json;
use Psr\Http\Message\ResponseInterface;
use XUI\Helper\Statics;

class Request
{
    private Client $guzzle;
    private string $base_uri;
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';

    /**
     * @param array $config Use `Request::config()` to make configurations
     */
    public function __construct(array $config)
    {
        $this->base_uri = $config['base_uri'];
        $this->guzzle = new Client($config);
        $config = $this->csrf_token($config);
        $this->guzzle = new Client($config);
    }

    public static function config(
        string $host,
        int    $port,
        string $uri_path = '/',
        bool   $has_ssl = false,
        string $api_token = null,
        string $cookie_dir = Statics::COOKIE_DIR,
        int    $timeout = 10,
        string $proxy = null,
        array  $extra = []
    ): array
    {
        $uri_path = empty($uri_path) || $uri_path == '/' ? '/' : "/$uri_path/";
        if ($has_ssl)
            $base_uri = 'https://' . $host . ':' . $port . $uri_path;
        else
            $base_uri = 'http://' . $host . ':' . $port . $uri_path;
        if (!is_dir($cookie_dir))
            mkdir($cookie_dir, 0700, true);
        else
            chmod($cookie_dir, 0700);
        $cookie_path = $cookie_dir . "/$host.cookie";
        $extra['base_uri'] = $base_uri;
        $extra['headers'] = [];
        if (!empty($api_token))
            $extra['headers'] = ['Authorization' => 'Bearer ' . $api_token];
        else
            $extra['cookies'] = new FileCookieJar($cookie_path, true);
        $extra['timeout'] = $timeout;
        $extra['proxy'] = $proxy;
        return $extra;
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function authentication(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = '/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = '/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function inbounds(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/inbounds/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/inbounds/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function server(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/server/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/server/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function clients(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/clients/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/clients/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function nodes(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/nodes/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/nodes/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function custom_geo(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/custom-geo/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/custom-geo/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function backup(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function settings(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/setting/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/setting/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function api_tokens(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/setting/apiTokens/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/setting/apiTokens/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * @param array $request Use `Request::make()` for making request options
     * @param array $requests Use `[Request::make(),...]` for making request options
     * @return Result|Result[]
     */
    public function xray(array $request = [], array $requests = []): Result|array
    {
        if (!empty($request)) {
            $request['route'] = 'panel/api/xray/' . $request['route'];
            return $this->request($request);
        } else {
            foreach ($requests as $key => $request):
                $requests[$key]['route'] = 'panel/api/xray/' . $request['route'];
            endforeach;
            return $this->multi_request($requests);
        }
    }

    /**
     * Fetch CSRF token from panel and store it
     */
    private function csrf_token(array $config): array
    {
        $result = $this->request(self::get('csrf-token'));
        if ($result->ok && $result->success)
            $config['headers']['x-csrf-token'] = $result->response()->obj;
        return $config;
    }

    private function request(array $request): Result
    {
        extract($request);
        $st = microtime(true);
        $url = rtrim($this->base_uri, '/') . '/' . ltrim($route, '/');
        $options = [];
        if (!empty($query)) $options['query'] = $query;
        if (!empty($body)) $options['body'] = json::_out($body);
        if (!empty($form_params)) $options['form_params'] = $form_params;
        return $this->guzzle
            ->requestAsync($method, $url, $options)
            ->then(
                function (ResponseInterface $result) use ($st) {
                    return $this->onFulfilled($result, $st);
                },
                function (ClientException|ServerException|RequestException|GuzzleException|ConnectException $error) use ($st) {
                    return $this->onRejected($error, $st);
                }
            )->wait();
    }

    /**
     * @param array $requests
     * @return Result[]
     */
    private function multi_request(array $requests): array
    {
        $promises = [];
        foreach ($requests as $request) {
            $st = microtime(true);
            extract($request);
            $url = rtrim($this->base_uri, '/') . '/' . ltrim($route, '/');
            $options = [];
            if (!empty($query)) $options['query'] = $query;
            if (!empty($body)) $options['body'] = json::_out($body);
            if (!empty($form_params)) $options['form_params'] = $form_params;
            $promises[] = $this->guzzle
                ->requestAsync($method, $url, $options)
                ->then(
                    function (ResponseInterface $result) use ($st) {
                        return $this->onFulfilled($result, $st);
                    },
                    function (ClientException|ServerException|RequestException|GuzzleException|ConnectException $error) use ($st) {
                        return $this->onRejected($error, $st);
                    }
                );
        }
        return Utils::settle($promises)->wait();
    }

    /**
     * @param string $route
     * @param string $method
     * @param array $body
     * @param array $query
     * @param array $form_params
     * @return array{route:string,method:string,body:array,query:array,form_params:array}
     */
    private static function make(string $route, string $method, array $body = [], array $query = [], array $form_params = [], array $multipart = []): array
    {
        return [
            'route' => $route,
            'method' => $method,
            'body' => $body,
            'query' => $query,
            'form_params' => $form_params,
            'multipart' => $multipart,
        ];
    }

    public static function post(string $route, array $body = [], array $form_params = [], array $query = [], array $multipart = []): array
    {
        return self::make($route, self::METHOD_POST, $body, $query, $form_params, $multipart);
    }

    public static function get(string $route, array $query = [], array $body = [], array $form_params = [], array $multipart = []): array
    {
        return self::make($route, self::METHOD_GET, $body, $query, $form_params, $multipart);
    }

    private function onFulfilled(ResponseInterface $result, $st): Result
    {
        $body = $result->getBody();
        $response = $body->getContents();
        $et = microtime(true);
        $tt = round($et - $st, 3);
        return new Result([
            'ok' => true,
            'response' => $response,
            'code' => $result->getStatusCode(),
            'header' => $result->getHeaders(),
            'size' => $body->getSize(),
            'time_taken' => $tt
        ]);
    }

    private function onRejected(ClientException|ServerException|RequestException|GuzzleException|ConnectException $error, $st): Result
    {
        if ($error instanceof ConnectException) {
            $et = microtime(true);
            $tt = round($et - $st, 3);
            return new Result([
                'ok' => false,
                'error' => $error->getMessage(),
                'code' => $error->getCode(),
                'header' => null,
                'response' => null,
                'size' => 0,
                'time_taken' => $tt
            ]);
        } else {
            $resp = $error->getResponse();
            $body = is_null($resp) ? null : $resp->getBody();
            $et = microtime(true);
            $tt = round($et - $st, 3);
            return new Result([
                'ok' => false,
                'error' => $error->getMessage(),
                'code' => $error->getCode(),
                'header' => is_null($resp) ? null : $resp->getHeaders(),
                'response' => is_null($body) ? null : $body->getContents(),
                'size' => is_null($body) ? 0 : $body->getSize(),
                'time_taken' => $tt
            ]);
        }
    }
}