<?php

namespace XUI\Handler;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Utils;
use JSON\json;
use Psr\Http\Message\ResponseInterface;
use XUI\Helper\Statics;

/**
 * Thin Guzzle wrapper that knows how to talk to the 3x-ui panel.
 *
 * It owns the HTTP client, the optional Bearer token, the cookie jar used for
 * browser-style sessions and the CSRF token required by cookie sessions.
 * Every endpoint class receives one instance through its constructor.
 */
class Request
{
    /** HTTP GET method. */
    public const METHOD_GET = 'GET';

    /** HTTP POST method. */
    public const METHOD_POST = 'POST';

    /** HTTP DELETE method. */
    public const METHOD_DELETE = 'DELETE';

    /** Configured Guzzle client. */
    private Client $client;

    /** Normalised base URI, always ending with a slash. */
    private string $base_uri;

    /** Bearer token, when the caller configured token authentication. */
    private ?string $api_token = null;

    /** Current CSRF token, when using a cookie session. */
    private ?string $csrf_token = null;

    /**
     * @param array $config Client configuration produced by {@see Request::config()}.
     */
    public function __construct(array $config)
    {
        $this->base_uri = rtrim((string)$config['base_uri'], '/') . '/';
        $this->api_token = $config['api_token'] ?? null;
        unset($config['api_token']);
        $this->client = new Client($config);
    }

    /**
     * Build the Guzzle configuration used by {@see Request}.
     *
     * @param string $host Panel host, without scheme.
     * @param int $port Panel port.
     * @param string $uri_path Panel base path, e.g. `ctrladmin` (leading/trailing slashes optional).
     * @param bool $has_ssl Use HTTPS instead of HTTP.
     * @param string|null $api_token Optional scoped API token. When set, cookies are not used.
     * @param string $cookie_dir Directory that persists the session cookie between PHP processes.
     * @param int $timeout Request timeout in seconds.
     * @param string|null $proxy Optional proxy URI.
     * @param array $extra Extra Guzzle options (`verify`, `connect_timeout`, ...).
     * @return array
     */
    public static function config(
        string $host,
        int $port,
        string $uri_path = '/',
        bool $has_ssl = false,
        ?string $api_token = null,
        string $cookie_dir = Statics::COOKIE_DIR,
        int $timeout = 10,
        ?string $proxy = null,
        array $extra = []
    ): array
    {
        $uri_path = trim($uri_path, '/');
        $base_uri = ($has_ssl ? 'https://' : 'http://') . $host . ':' . $port . '/' . ($uri_path === '' ? '' : $uri_path . '/');

        if (!is_dir($cookie_dir)) {
            mkdir($cookie_dir, 0700, true);
        }

        $config = $extra;
        $config['base_uri'] = $base_uri;
        $config['timeout'] = $timeout;
        if (!is_null($proxy)) {
            $config['proxy'] = $proxy;
        }

        $headers = $config['headers'] ?? [];
        if (!empty($api_token)) {
            $headers['Authorization'] = 'Bearer ' . $api_token;
            $config['api_token'] = $api_token;
        } else {
            $config['cookies'] = new FileCookieJar($cookie_dir . '/' . self::cookie_name($host, $port) . '.cookie', true);
        }
        $config['headers'] = $headers;

        return $config;
    }

    /**
     * Normalised base URI of the panel, always ending with a slash.
     *
     * @return string
     */
    public function base_uri(): string
    {
        return $this->base_uri;
    }

    /**
     * WebSocket URL of the panel event stream (`/ws`).
     *
     * Browsers open it with the session cookie; PHP clients need a WebSocket
     * library. The URL is returned so any client can use it directly.
     *
     * @return string
     */
    public function websocket_url(): string
    {
        $url = $this->base_uri . 'ws';
        return preg_replace('/^http/', 'ws', $url) ?? $url;
    }

    /**
     * Fetch a fresh CSRF token for the current cookie session, remember it and
     * return the underlying result.
     *
     * The token is bound to the session cookie, so this also establishes the
     * session before the first unsafe request. Bearer-token callers do not
     * need CSRF and get a synthetic success result without a network call.
     *
     * @return Result
     */
    public function fetch_csrf_token(): Result
    {
        if (!is_null($this->api_token)) {
            return Result::make_ok(Result::make_response(true, null, 'CSRF token is not required for Bearer authentication'));
        }

        $result = $this->request(self::get('csrf-token'));
        $token = $result->obj();
        if ($result->ok && $result->success && is_string($token) && $token !== '') {
            $this->csrf_token = $token;
        }
        return $result;
    }

    /**
     * Fetch a fresh CSRF token for the current cookie session and remember it.
     *
     * @return string|null The token, or null when it could not be obtained.
     */
    public function refresh_csrf_token(): ?string
    {
        $this->fetch_csrf_token();
        return $this->csrf_token;
    }

    /**
     * Send one request to a root-relative route (login, csrf, subscription server).
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function root(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('', $request, $requests);
    }

    /**
     * Send one request to the panel login routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function authentication(array $request = [], array $requests = []): Result|array
    {
        return $this->root($request, $requests);
    }

    /**
     * Send one request to the panel inbound routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function inbounds(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/inbounds/', $request, $requests);
    }

    /**
     * Send one request to the panel host routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function hosts(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/hosts/', $request, $requests);
    }

    /**
     * Send one request to the panel server routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function server(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/server/', $request, $requests);
    }

    /**
     * Send one request to the panel client routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function clients(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/clients/', $request, $requests);
    }

    /**
     * Send one request to the panel node routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function nodes(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/nodes/', $request, $requests);
    }

    /**
     * Send one request to the panel subscription-balancer routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function sub_balancers(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/sub-balancers/', $request, $requests);
    }

    /**
     * Send one request to the custom-geo routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function custom_geo(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/custom-geo/', $request, $requests);
    }

    /**
     * Send one request to the generic `/panel/api/` routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function panel(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/', $request, $requests);
    }

    /**
     * Send one request to the panel backup routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function backup(array $request = [], array $requests = []): Result|array
    {
        return $this->panel($request, $requests);
    }

    /**
     * Send one request to the panel setting routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function settings(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/setting/', $request, $requests);
    }

    /**
     * Send one request to the API token routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function api_tokens(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/setting/apiTokens/', $request, $requests);
    }

    /**
     * Send one request to the xray settings routes.
     *
     * @param array $request Request built by {@see Request::post()} / {@see Request::get()}.
     * @param array $requests Multiple requests to run in parallel.
     * @return Result|Result[]
     */
    public function xray(array $request = [], array $requests = []): Result|array
    {
        return $this->dispatch('panel/api/xray/', $request, $requests);
    }

    /**
     * Route a set of requests below a shared prefix.
     *
     * @param string $prefix Path fragment prepended to every route.
     * @param array $request Single request.
     * @param array $requests Multiple requests.
     * @return Result|Result[]
     */
    private function dispatch(string $prefix, array $request, array $requests): Result|array
    {
        $prefix = rtrim($prefix, '/');
        return !empty($request)
            ? $this->request($this->with_route($prefix, $request))
            : $this->multi_request(array_map(
                fn(array $item): array => $this->with_route($prefix, $item),
                $requests
            ));
    }

    /**
     * Attach the endpoint family prefix to a prepared request.
     *
     * The Xray template endpoint lives at `/panel/api/xray/` (with a trailing
     * slash), every other empty route is the collection endpoint without one.
     *
     * @param string $prefix Endpoint family prefix, without trailing slash.
     * @param array $request Prepared request.
     * @return array
     */
    private function with_route(string $prefix, array $request): array
    {
        $route = (string)($request['route'] ?? '');
        if ($prefix === '') {
            $request['route'] = $route;
        } elseif ($route === '') {
            $request['route'] = $prefix === 'panel/api/xray' ? $prefix . '/' : $prefix;
        } else {
            $request['route'] = $prefix . '/' . ltrim($route, '/');
        }
        return $request;
    }

    /**
     * Turn decoded JSON data into an HTTP body, preserving objects as objects.
     *
     * @param array|object|string $body
     * @return string
     */
    private static function encode_body(array|object|string $body): string
    {
        if (is_string($body)) {
            return $body;
        }
        return json::_out($body) ?: '{}';
    }

    /**
     * Execute a single prepared request.
     *
     * @param array $request Prepared request array.
     * @return Result
     */
    private function request(array $request): Result
    {
        $this->ensure_csrf($request);
        $start = microtime(true);
        $options = $this->options($request);
        try {
            $response = $this->client->request(
                $request['method'],
                $this->url($request['route']),
                $options
            );
            return $this->onFulfilled($response, $start);
        } catch (GuzzleException $error) {
            return $this->onRejected($error, $start);
        }
    }

    /**
     * Execute several prepared requests concurrently.
     *
     * @param array $requests Prepared request arrays.
     * @return Result[]
     */
    private function multi_request(array $requests): array
    {
        foreach ($requests as $request) {
            $this->ensure_csrf($request);
        }

        $promises = [];
        foreach ($requests as $request) {
            $start = microtime(true);
            $promises[] = $this->client
                ->requestAsync($request['method'], $this->url($request['route']), $this->options($request))
                ->then(
                    fn(ResponseInterface $response): Result => $this->onFulfilled($response, $start),
                    fn(GuzzleException $error): Result => $this->onRejected($error, $start)
                );
        }

        $settled = Utils::settle($promises)->wait();
        return array_values(array_map(
            static fn(array $item) => $item['value'] ?? null,
            $settled
        ));
    }

    /**
     * Lazily obtain the CSRF token before the first unsafe cookie-session request.
     *
     * A fresh PHP process that reuses a persisted session cookie starts without
     * a CSRF token, so every unsafe request would be rejected with `403` until
     * one is fetched. Fetching it here keeps the public API unchanged.
     *
     * @param array $request Prepared request about to be sent.
     * @return void
     */
    private function ensure_csrf(array $request): void
    {
        if (!is_null($this->api_token) || !is_null($this->csrf_token)) {
            return;
        }

        $method = strtoupper((string)($request['method'] ?? self::METHOD_GET));
        if (!in_array($method, [self::METHOD_POST, 'PUT', 'PATCH', self::METHOD_DELETE], true)) {
            return;
        }

        if (ltrim((string)($request['route'] ?? ''), '/') === 'csrf-token') {
            return;
        }

        $this->fetch_csrf_token();
    }

    /**
     * Build the Guzzle option array for one prepared request.
     *
     * @param array $request Prepared request array.
     * @return array
     */
    private function options(array $request): array
    {
        $options = [];

        $query = array_filter(
            $request['query'] ?? [],
            static fn(mixed $value): bool => !is_null($value)
        );
        if (!empty($query)) {
            $options['query'] = $query;
        }

        if (!empty($request['multipart'])) {
            $options['multipart'] = $request['multipart'];
        } elseif (!empty($request['form_params'])) {
            if (self::has_array_value($request['form_params'])) {
                $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
                $options['body'] = self::form_encode($request['form_params']);
            } else {
                $options['form_params'] = $request['form_params'];
            }
        } elseif (!empty($request['body'])) {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = self::encode_body($request['body']);
        }

        if (!is_null($this->csrf_token)) {
            $options['headers']['X-CSRF-Token'] = $this->csrf_token;
        }

        return $options;
    }

    /**
     * Check whether a form payload contains array values that must be sent as
     * repeated keys rather than PHP's `key[0]=...` style.
     *
     * @param array $data Form parameters.
     * @return bool
     */
    private static function has_array_value(array $data): bool
    {
        foreach ($data as $value) {
            if (is_array($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Encode a form payload, repeating keys for array values.
     *
     * @param array $data Form parameters.
     * @return string
     */
    private static function form_encode(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            foreach (is_array($value) ? $value : [$value] as $item) {
                if (is_null($item)) {
                    continue;
                }
                $parts[] = rawurlencode((string)$key) . '=' . rawurlencode(is_bool($item) ? ($item ? 'true' : 'false') : (string)$item);
            }
        }
        return implode('&', $parts);
    }

    /**
     * Resolve a route against the configured base URI.
     *
     * @param string $route Relative route.
     * @return string
     */
    private function url(string $route): string
    {
        return $this->base_uri . ltrim($route, '/');
    }

    /**
     * Build a cookie file name that is safe for every host syntax.
     *
     * @param string $host Panel host.
     * @param int $port Panel port.
     * @return string
     */
    private static function cookie_name(string $host, int $port): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $host) ?: 'panel';
        return $safe . '_' . $port;
    }

    /**
     * Build one request definition.
     *
     * @param string $route Route relative to the endpoint family prefix.
     * @param string $method HTTP method.
     * @param array $body JSON request body.
     * @param array $query Query string parameters.
     * @param array $form_params URL-encoded form parameters.
     * @param array $multipart Multipart form parts.
     * @return array{route:string,method:string,body:array,query:array,form_params:array,multipart:array}
     */
    private static function make(
        string $route,
        string $method,
        array $body = [],
        array $query = [],
        array $form_params = [],
        array $multipart = []
    ): array
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

    /**
     * Build a POST request.
     *
     * @param string $route Route relative to the endpoint family prefix.
     * @param array $body JSON request body.
     * @param array $form_params URL-encoded form parameters.
     * @param array $query Query string parameters.
     * @param array $multipart Multipart form parts.
     * @return array
     */
    public static function post(string $route, array $body = [], array $form_params = [], array $query = [], array $multipart = []): array
    {
        return self::make($route, self::METHOD_POST, $body, $query, $form_params, $multipart);
    }

    /**
     * Build a GET request.
     *
     * @param string $route Route relative to the endpoint family prefix.
     * @param array $query Query string parameters.
     * @param array $body JSON request body (rarely used with GET).
     * @param array $form_params URL-encoded form parameters (rarely used with GET).
     * @param array $multipart Multipart form parts (rarely used with GET).
     * @return array
     */
    public static function get(string $route, array $query = [], array $body = [], array $form_params = [], array $multipart = []): array
    {
        return self::make($route, self::METHOD_GET, $body, $query, $form_params, $multipart);
    }

    /**
     * Build a DELETE request.
     *
     * @param string $route Route relative to the endpoint family prefix.
     * @param array $query Query string parameters.
     * @param array $body JSON request body.
     * @param array $form_params URL-encoded form parameters.
     * @param array $multipart Multipart form parts.
     * @return array
     */
    public static function delete(string $route, array $query = [], array $body = [], array $form_params = [], array $multipart = []): array
    {
        return self::make($route, self::METHOD_DELETE, $body, $query, $form_params, $multipart);
    }

    /**
     * Wrap a Guzzle response into a {@see Result}.
     *
     * @param ResponseInterface $result Guzzle response.
     * @param float $start Request start time.
     * @return Result
     */
    private function onFulfilled(ResponseInterface $result, float $start): Result
    {
        $body = $result->getBody();
        $contents = $body->getContents();
        return new Result([
            'ok' => true,
            'response' => $contents,
            'code' => $result->getStatusCode(),
            'header' => $result->getHeaders(),
            'size' => $body->getSize() ?? strlen($contents),
            'time_taken' => round(microtime(true) - $start, 3),
        ]);
    }

    /**
     * Wrap a Guzzle failure into a {@see Result}.
     *
     * @param GuzzleException $error Guzzle exception.
     * @param float $start Request start time.
     * @return Result
     */
    private function onRejected(GuzzleException $error, float $start): Result
    {
        $response = $error instanceof RequestException ? $error->getResponse() : null;
        $body = $response?->getBody();
        $contents = $body?->getContents();

        return new Result([
            'ok' => false,
            'error' => $error->getMessage(),
            'code' => $response?->getStatusCode() ?? (int)$error->getCode(),
            'header' => $response?->getHeaders(),
            'response' => $contents,
            'size' => $body?->getSize() ?? ($contents === null ? 0 : strlen($contents)),
            'time_taken' => round(microtime(true) - $start, 3),
        ]);
    }
}
