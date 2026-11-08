<p align="right"><em>Jump to any section with the table of contents below.</em></p>

<h1 align="center">PHP 3X-UI</h1>

<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="./media/php_3x-ui.png">
    <img alt="PHP 3X-UI" src="./media/php_3x-ui.png" width="420">
  </picture>
</p>

<p align="center">
  <strong>A typed, fully documented PHP client for the <a href="https://github.com/MHSanaei/3x-ui">MHSanaei 3x-ui</a> panel API.</strong>
</p>

<p align="center">
  <a href="https://packagist.org/packages/estaheri/3x-ui"><img alt="Latest Version" src="https://img.shields.io/packagist/v/estaheri/3x-ui.svg?style=flat-square"></a>
  <a href="https://packagist.org/packages/estaheri/3x-ui"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/estaheri/3x-ui.svg?style=flat-square"></a>
  <a href="https://www.php.net/releases/8.2/en.php"><img alt="PHP" src="https://img.shields.io/badge/php-%3E%3D8.2-blue?style=flat-square"></a>
  <a href="https://github.com/MHSanaei/3x-ui/releases"><img alt="3x-ui" src="https://img.shields.io/badge/3x--ui-v3.x-05564F?style=flat-square"></a>
  <a href="https://github.com/XTLS/Xray-core/releases"><img alt="Xray" src="https://img.shields.io/badge/Xray-v25.x-darkred?style=flat-square"></a>
  <a href="https://github.com/es-taheri/3x-ui/blob/master/LICENSE"><img alt="License" src="https://img.shields.io/badge/license-MIT-007EC6?style=flat-square"></a>
</p>

> [!IMPORTANT]
> This library is intended for personal and administrative use. Do not use it for illegal purposes, and test it
> against a staging panel before running it against production. You are responsible for the traffic and users you
> manage.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Authentication](#authentication)
  - [Cookie session](#cookie-session)
  - [API tokens](#api-tokens)
  - [Session helpers](#session-helpers)
- [Responses and errors](#responses-and-errors)
- [Configuration objects](#configuration-objects)
  - [Inbound protocols](#inbound-protocols)
  - [Inbound transports](#inbound-transports)
  - [Outbound protocols](#outbound-protocols)
  - [Clients](#clients-config-object)
  - [Outbounds helper](#outbounds-helper)
- [Real-world example](#real-world-example)
- [Endpoint reference](#endpoint-reference)
  - [Inbounds](#inbounds)
  - [Clients](#clients)
  - [Hosts](#hosts)
  - [Nodes](#nodes)
  - [Subscription balancers](#subscription-balancers)
  - [Server](#server)
  - [Settings and API tokens](#settings-and-api-tokens)
  - [Xray](#xray)
  - [Routing](#routing)
  - [Reverse](#reverse)
  - [Subscription server](#subscription-server)
  - [WebSocket events](#websocket-events)
- [Coding style](#coding-style)
- [Project structure](#project-structure)
- [Compatibility](#compatibility)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)
- [Special Thanks](#special-thanks)
- [Support project](#support-project)

---

## Features

- **Complete panel API coverage** — inbounds, clients, hosts, nodes, subscription balancers, server, settings,
  API tokens, Xray config, routing, subscriptions and the WebSocket event stream.
- **Typed configuration builders** for inbound/outbound protocols and transports, so you never hand-write raw
  Xray JSON.
- **Two authentication modes** — cookie session with automatic CSRF handling, or scoped Bearer API tokens.
- **Parallel requests** — pass an array of ids/emails to supported methods and get `Result[]` back.
- **Immutable result objects** — one predictable shape for HTTP status, panel envelope, payload and errors.
- **IDE-first** — every class, property, method and parameter is PHPDoc-documented; PHPStorm completion and quick
  documentation work out of the box.
- **Small dependency surface** — Guzzle 7 and a tiny JSON helper. No framework required.
- **Safe defaults** — random UUIDs and WireGuard keys, explicit nullable types, JSON bodies, no hidden globals.

---

## Requirements

| Requirement | Version / notes |
| --- | --- |
| PHP | **8.2 or newer** |
| PHP extensions | `json`, `curl`, `openssl`, `mbstring` (Guzzle requirements) |
| Composer | 2.x |
| 3x-ui panel | API as shipped with **v3.x** (older panels simply lack newer endpoints) |
| Xray-core | v25.x (or whatever your panel runs) |

A writable cookie directory is required when you authenticate with a username and password. The default is
`src/.cookie` (already git-ignored) and is created automatically.

---

## Installation

```bash
composer require estaheri/3x-ui
```

Then require the Composer autoloader:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

No service provider, bootstrap or configuration file is needed.

---

## Quick Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use XUI\Handler\Request;
use XUI\Xui;

// 1. Build the HTTP transport.
$request = new Request(Request::config(
    host: 'panel.example.com',
    port: 2053,
    uri_path: 'xxxxx',   // panel base path, '' or '/' when it is at the root
    has_ssl: true,
));

// 2. Create the client.
$xui = new Xui($request);

// 3. Log in (skipped automatically when a valid cookie session exists).
$login = $xui->login('admin', 'password');
if ($login !== true && (!$login->ok || !$login->success)) {
    throw new RuntimeException('Login failed: ' . ($login->error ?? $login->msg()));
}

// 4. Use any panel area.
$status = $xui->server->status();
var_dump($status->obj());
```

Every panel area is a public property:

```php
$xui->inbounds;       // Inbounds
$xui->clients;        // Clients (first-class entities, attachable to many inbounds)
$xui->hosts;          // Host override groups
$xui->nodes;          // Remote nodes managed by a central panel
$xui->sub_balancers;  // JSON-subscription balancers
$xui->server;         // Status, logs, backups, certificates, Xray binary
$xui->xray;           // Xray config, routing, reverse, integrations
$xui->settings;       // Panel settings and API tokens
$xui->outbounds;      // Typed helper over the Xray outbound list
```

---

## Authentication

There are two supported modes. Both work for every endpoint under `/panel/api/*`.

### Cookie session

This is the default when no token is supplied. The library stores the session cookie in
`src/.cookie/<host>_<port>.cookie`, reuses it across PHP processes, and handles CSRF automatically:

- the login POST is sent only after a `X-CSRF-Token` has been obtained for the session cookie;
- the token is refreshed after login because the panel rotates the session;
- on a reused session (new PHP process) the token is fetched lazily before the first unsafe
  (`POST`/`PUT`/`PATCH`/`DELETE`) request.

```php
$request = new Request(Request::config(
    host: 'panel.example.com',
    port: 2053,
    uri_path: 'xxxxx',
    has_ssl: true,
    cookie_dir: sys_get_temp_dir() . '/xui-cookies', // optional
    timeout: 15,                                    // optional, seconds
    proxy: 'http://127.0.0.1:8080',                 // optional
    extra: ['verify' => '/etc/ssl/certs/ca.pem'],   // optional Guzzle TLS options
));
```

### API tokens

Mint a token in **Settings → Security → API Token**, then pass it to `Request::config()`. No login call is
required and no cookie file is written.

```php
$request = new Request(Request::config(
    host: 'panel.example.com',
    port: 2053,
    uri_path: 'xxxxx',
    has_ssl: true,
    api_token: getenv('XUI_API_TOKEN'),
));

$xui = new Xui($request);
```

| Scope constant | Value | Permitted operations |
| --- | --- | --- |
| `XUI\Settings::SCOPE_ADMIN` | `admin` | Full panel API. Treat it like the panel password. |
| `XUI\Settings::SCOPE_MONITOR` | `monitor` | Read-only status and metrics. |
| `XUI\Settings::SCOPE_NODE_SYNC` | `node-sync` | Manage node configuration, but not panel updates. |
| `XUI\Settings::SCOPE_NODE_ADMIN` | `node-admin` | `node-sync` plus panel self-update. |

The plaintext token is returned only once at creation time:

```php
$created = $xui->settings->create_api_token(
    name: 'monitoring',
    scope: XUI\Settings::SCOPE_MONITOR,
    expires_at: 0, // 0 = never; otherwise Unix milliseconds
);
$plaintext = $created->obj()->token;
```

### Session helpers

```php
$xui->is_login();                 // bool
$xui->csrf_token();               // Result, refreshes the CSRF token
$xui->get_two_factor_enabled();   // Result (obj is bool)
$xui->logout();                   // Result
$xui->sponsors();                 // Result, public sponsor placements
```

---

## Responses and errors

Every endpoint returns an immutable `XUI\Handler\Result` (or `Result[]` for parallel fan-out).

```php
$result = $xui->inbounds->list();

$result->ok;          // bool   HTTP round-trip succeeded
$result->success;     // bool   panel envelope has success:true
$result->code;        // int    HTTP status code
$result->header;      // array|null
$result->size;        // int    body size in bytes
$result->time_taken;  // float  seconds
$result->error;       // string|null transport error

$result->response();                     // stdClass envelope {success, obj, msg}
$result->response(Statics::OUTPUT_ARRAY); // array
$result->obj();                          // obj member as stdClass
$result->obj(Statics::OUTPUT_ARRAY);      // obj member as array
$result->msg();                          // envelope message
$result->raw();                          // raw body (database backups, YAML, ...)
$result->fail();                         // {code, error} or null
```

Recommended error handling:

```php
use XUI\Helper\Statics;

$result = $xui->clients->create($client, [3]);

if (!$result->ok) {
    // Network / TLS / DNS problem.
    error_log('Transport: ' . $result->error);
} elseif (!$result->success) {
    // The panel rejected the call.
    error_log('Panel: ' . $result->msg());
} else {
    $payload = $result->obj(Statics::OUTPUT_ARRAY);
}
```

`XUI\Helper\Statics` also provides small helpers:

```php
Statics::OUTPUT_JSON;        // JSON string
Statics::OUTPUT_OBJECT;      // stdClass
Statics::OUTPUT_ARRAY;       // array

Statics::random(32);         // cryptographically secure random string
Statics::uuid();             // UUID v4
Statics::now();              // Unix milliseconds
Statics::days_from_now(30);  // Unix milliseconds, 30 days ahead
Statics::to_millis($seconds);
Statics::to_seconds($millis);

Statics::UNIT_BYTE;
Statics::UNIT_KILOBYTE;
Statics::UNIT_MEGABYTE;
Statics::UNIT_GIGABYTE;
Statics::UNIT_TERABYTE;
```

---

## Configuration objects

All protocol and transport settings are real classes with typed properties, so PHPStorm can complete them and
you never edit raw JSON.

### Inbound protocols

| Protocol | Class |
| --- | --- |
| `vless` | `XUI\Inbounds\Protocols\Vless\Vless` |
| `vmess` | `XUI\Inbounds\Protocols\Vmess\Vmess` |
| `trojan` | `XUI\Inbounds\Protocols\Trojan\Trojan` |
| `shadowsocks` | `XUI\Inbounds\Protocols\Shadowsocks\Shadowsocks` |
| `socks` | `XUI\Inbounds\Protocols\Socks\Socks` |
| `http` | `XUI\Inbounds\Protocols\Http\Http` |
| `dokodemo-door` / `tunnel` | `XUI\Inbounds\Protocols\Tunnel\Tunnel` |
| `wireguard` | `XUI\Inbounds\Protocols\WireGuard\WireGuard` |
| `hysteria` | `XUI\Inbounds\Protocols\Hysteria\Hysteria` |
| `mtproto` | `XUI\Inbounds\Protocols\Mtproto\Mtproto` |
| `mixed` | `XUI\Inbounds\Protocols\Mixed\MixedInbound` |
| `amneziawg` | `XUI\Inbounds\Protocols\AmneziaWG\AmneziaWG` |
| anything else (`tuic`, `tun`, `masque`, ...) | `XUI\Inbounds\Settings` (generic container) |

```php
use XUI\Inbounds\Protocols\Vless\Settings;
use XUI\Inbounds\Protocols\Vless\Vless;
use XUI\Inbounds\Sniffing;
use XUI\Inbounds\StreamSettings;

// Protocol settings + one client.
$settings = new Settings();
$settings->add_client(email: 'alice@example.com', flow: 'xtls-rprx-vision');

// Transport + security.
$stream = new StreamSettings('tcp', 'reality');
$stream->tcp_settings();
$stream->reality_settings(
    private_key: $private_key,
    public_key: $public_key,
    dest: 'www.cloudflare.com:443',
    server_names: ['www.cloudflare.com'],
);

// Assemble and create it.
$inbound = new Vless();
$inbound->generate(port: 443, settings: $settings, stream_settings: $stream, sniffing: new Sniffing());

$xui->inbounds->add($inbound, 'VLESS-443');
```

Generic container for protocols without a dedicated class:

```php
use XUI\Inbounds\Settings as GenericSettings;

$settings = GenericSettings::make('tuic', [
    'clients' => [],
    'congestion_control' => 'bbr',
]);
```

### Inbound transports

`XUI\Inbounds\StreamSettings` exposes:

```php
$stream->tcp_settings();          // tcp
$stream->kcp_settings();          // kcp
$stream->ws_settings();           // ws
$stream->http_settings();         // http/2
$stream->quic_settings();         // quic
$stream->grpc_settings();         // grpc
$stream->httpupgrade_settings();  // httpupgrade
$stream->xhttp_settings();        // xhttp (formerly splithttp)
$stream->ds_settings();           // domainsocket
$stream->sockopt();               // socket options

$stream->tls_settings();          // tls
$stream->reality_settings();      // reality
$stream->final_mask();            // finalmask
```

Every helper accepts named arguments and has documented defaults. See
[Xray transports](https://xtls.github.io/en/config/transport.html) and
[REALITY](https://github.com/XTLS/REALITY/blob/main/README.en.md).

### Outbound protocols

`XUI\Outbounds\Protocols\{Vless,Vmess,Trojan,Shadowsocks,Socks,Http,Freedom,Dns,Blackhole}` plus
`XUI\Outbounds\StreamSettings` for client-side transports.

```php
use XUI\Outbounds\Protocols\Vless\Settings;
use XUI\Outbounds\Protocols\Vless\Vless;
use XUI\Outbounds\StreamSettings;

$settings = new Settings('server.example.com', 443);
$settings->add_user('00000000-0000-4000-8000-000000000000', flow: 'xtls-rprx-vision');

$stream = new StreamSettings('ws', 'tls');
$stream->ws_settings(path: '/ws', host: 'cdn.example.com');
$stream->tls_settings('cdn.example.com');

$outbound = new Vless();
$outbound->generate($settings, $stream);

$xui->outbounds->add('proxy', $outbound);
```

### Clients config object

`XUI\Clients\Client` maps the panel `Client` schema one-to-one.

```php
use XUI\Clients\Client;

$client = new Client('alice@example.com');
$client->quota_gb(50);              // 50 GB in bytes
$client->expiry_in_days(30);       // Unix milliseconds, 0 = never
$client->limit_ip = 2;
$client->limit_hwid = 3;
$client->comment = 'Primary device';
$client->group = 'staff';
$client->reset_day = 1;            // optional calendar renewal

$xui->clients->create($client, [3, 5]);
```

### Outbounds helper

```php
$xui->outbounds->list();
$xui->outbounds->get('proxy');
$xui->outbounds->exist('proxy');
$xui->outbounds->add('proxy', $config);
$xui->outbounds->update('proxy', config: $newConfig);
$xui->outbounds->delete('proxy');

// Payload conversion.
$config = XUI\Outbounds::read($outboundPayload);
$inbound = XUI\Outbounds::to_inbound($config, listen: '0.0.0.0', port: 443);
```

---

## Real-world example

Create a client on an existing inbound, then read its share links:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use XUI\Clients\Client;
use XUI\Handler\Request;
use XUI\Helper\Statics;
use XUI\Xui;

$xui = new Xui(new Request(Request::config(
    host: 'panel.example.com',
    port: 2053,
    uri_path: 'xxxxx',
    has_ssl: true,
)));

$xui->login('admin', 'password');

// Find the target inbound by remark.
$inbounds = $xui->inbounds->list()->obj(Statics::OUTPUT_ARRAY);
$inboundId = null;
foreach ($inbounds as $inbound) {
    if (($inbound['remark'] ?? '') === 'VLESS-443') {
        $inboundId = (int)$inbound['id'];
        break;
    }
}
if ($inboundId === null) {
    throw new RuntimeException('Inbound not found.');
}

// Create the client on it.
$client = new Client('alice@example.com');
$client->quota_gb(50)->expiry_in_days(30);

$created = $xui->clients->create($client, [$inboundId]);
if (!$created->ok || !$created->success) {
    throw new RuntimeException('Create failed: ' . ($created->error ?? $created->msg()));
}

// Read every URL for the client.
$links = $xui->clients->links('alice@example.com')->obj(Statics::OUTPUT_ARRAY);
foreach ($links as $link) {
    echo $link, PHP_EOL;
}
```

---

## Endpoint reference

All methods return `Result` unless stated otherwise. Methods documented with `array` arguments are the parallel
variants and return `Result[]`.

### Inbounds

```php
$xui->inbounds->list();                       // full inbound list
$xui->inbounds->list(slim: true);             // compact client list
$xui->inbounds->options();                    // picker projection (id, remark, protocol, ...)
$xui->inbounds->all_links();                  // every share link across all inbounds
$xui->inbounds->amneziawg_kernel_readiness(); // kernel-mode capability

$xui->inbounds->get(3);                       // Result
$xui->inbounds->get([3, 5]);                  // Result[]
$xui->inbounds->add($config, 'VLESS-443', port: 443);
$xui->inbounds->bulk_add([
    ['config' => $vless, 'remark' => 'VLESS-443', 'port' => 443],
]);
$xui->inbounds->update(3, $fullPayload);      // body mirrors add()
$xui->inbounds->set_enable(3, false);
$xui->inbounds->set_sub_sort_index(3, 2);
$xui->inbounds->reset_traffic([3, 5]);
$xui->inbounds->delete_all_clients(3);
$xui->inbounds->reset_all_traffics();
$xui->inbounds->delete(3);                    // pass an array for bulk delete
$xui->inbounds->import($json);
$xui->inbounds->fallbacks(3);                 // read
$xui->inbounds->fallbacks(3, $replace);       // replace
$xui->inbounds->push_client_traffics($guid, $rows);

// Conversion helpers.
$config = XUI\Inbounds::read($inboundPayload);
$outbound = XUI\Inbounds::to_outbound('1.2.3.4', $config);
```

### Clients

```php
// Listing and lookup.
$xui->clients->list();
$xui->clients->paged(page: 1, page_size: 25, search: 'alice', filter: 'active');
$xui->clients->get('alice@example.com');
$xui->clients->get_by_sub_id($sub_id);
$xui->clients->get_by_tg_id(123456789);

// Create / update / delete.
$xui->clients->create($client, [3, 5]);
$xui->clients->bulk_create([
    ['client' => $clientA, 'inboundIds' => [3]],
    ['client' => $clientB, 'inboundIds' => [3, 5]],
]);
$xui->clients->update('alice@example.com', $client);
$xui->clients->renewal_preview([
    'expiryTime' => 0, 'reset' => 30, 'resetDay' => 0,
    'resetWeekday' => 0, 'resetMax' => 0, 'resetCount' => 0,
]);
$xui->clients->delete('alice@example.com', keep_traffic: true);

// Attach / detach.
$xui->clients->attach('alice@example.com', [7]);
$xui->clients->bulk_attach(['a', 'b'], [7, 9]);
$xui->clients->detach('alice@example.com', [5]);
$xui->clients->bulk_detach(['a', 'b'], [5]);

// Bulk operations.
$xui->clients->bulk_adjust(['a', 'b'], add_days: 30, add_bytes: 53687091200);
$xui->clients->bulk_enable(['a', 'b']);
$xui->clients->bulk_disable(['a', 'b']);
$xui->clients->bulk_delete(['a', 'b']);
$xui->clients->reset_traffic('alice@example.com');
$xui->clients->reset_all_traffics();
$xui->clients->delete_depleted();
$xui->clients->delete_orphans();
$xui->clients->export();
$xui->clients->import($json);

// Groups.
$xui->clients->groups();
$xui->clients->group_emails('staff');
$xui->clients->group_create('staff');
$xui->clients->group_rename('staff', 'tier-1');
$xui->clients->group_bulk_add(['a', 'b'], 'staff');
$xui->clients->group_bulk_remove(['a']);
$xui->clients->group_reset_traffic('staff');
$xui->clients->group_delete('staff');

// Traffic, IPs and devices.
$xui->clients->traffic('alice@example.com');
$xui->clients->update_traffic('alice@example.com', 1024, 2048);
$xui->clients->ips('alice@example.com');
$xui->clients->clear_ips('alice@example.com');
$xui->clients->hwids('alice@example.com');
$xui->clients->delete_hwids('alice@example.com');
$xui->clients->delete_hwid('alice@example.com', 1);

// Presence and links.
$xui->clients->onlines();
$xui->clients->onlines_by_guid();
$xui->clients->client_ips_by_guid();
$xui->clients->active_inbounds();
$xui->clients->last_online();
$xui->clients->links('alice@example.com');
$xui->clients->sub_links($sub_id);
$xui->clients->happ_link(12);
$xui->clients->external_links('alice@example.com', $links);
```

### Hosts

```php
$xui->hosts->list();
$xui->hosts->raw();
$xui->hosts->get($group_id);
$xui->hosts->by_inbound(1);
$xui->hosts->tags();
$xui->hosts->add([
    'inboundIds' => [1],
    'hosts' => ['cdn.example.com'],
    'port' => 8443,
    'security' => 'same',
    'remark' => 'cdn-front',
]);
$xui->hosts->update($group_id, $host);
$xui->hosts->set_enable($group_id, false);
$xui->hosts->reorder([$group_id]);
$xui->hosts->bulk_add($host);
$xui->hosts->bulk_set_enable([$group_id], false);
$xui->hosts->bulk_delete([$group_id]);
$xui->hosts->delete($group_id);
```

### Nodes

```php
$xui->nodes->list();
$xui->nodes->get(1);
$xui->nodes->add([
    'name' => 'de-fra-1',
    'scheme' => 'https',
    'address' => 'node1.example.com',
    'port' => 2053,
    'basePath' => '/',
    'apiToken' => '...',
    'enable' => true,
]);
$xui->nodes->update(1, $node);
$xui->nodes->set_enable(1, false);
$xui->nodes->probe(1);
$xui->nodes->test($node);
$xui->nodes->cert_fingerprint($node);
$xui->nodes->inbounds($node);
$xui->nodes->update_panel([1, 2], dev: false);
$xui->nodes->history(1, 'cpu', 60);
$xui->nodes->web_cert(1);
$xui->nodes->mtls_ca();
$xui->nodes->mtls_trust_ca($pem);
$xui->nodes->mtls_reload_client();
$xui->nodes->delete(1);
```

### Subscription balancers

```php
$xui->sub_balancers->list();
$xui->sub_balancers->create(
    remark: 'auto-fastest',
    inbound_ids: [1, 3],
    strategy: XUI\SubBalancers::STRATEGY_LEAST_PING,
    sort_order: 1,
);
$xui->sub_balancers->update(1, 'auto-fastest', [1, 3], 'leastLoad', 1, true, [1 => 0.5]);
$xui->sub_balancers->delete(1);
```

Strategies: `leastLoad`, `leastPing`, `random`, `roundRobin` (constants `STRATEGY_*`).

### Server

```php
// Status and metrics.
$xui->server->status();
$xui->server->fail2ban_status();
$xui->server->history('cpu', 60);
$xui->server->cpu_history(60);
$xui->server->xray_metrics_state();
$xui->server->xray_metrics_history('xrAlloc', 60);
$xui->server->xray_observatory();
$xui->server->xray_observatory_history('proxy', 60);

// Versions and updates.
$xui->server->xray_versions();
$xui->server->panel_update_info();
$xui->server->update_status();
$xui->server->update_panel();
$xui->server->set_update_channel(dev: false);

// Config, backups and database.
$xui->server->config_json();
$xui->server->get_db();          // raw bytes in Result::raw()
$xui->server->get_migration();
$xui->server->import_db('/tmp/backup.db');
$xui->server->new_uuid();
$xui->server->web_cert_files();
$xui->server->descendants();

// Key and certificate helpers.
$xui->server->new_x25519_cert();
$xui->server->new_mldsa65();
$xui->server->new_mlkem768();
$xui->server->new_vless_enc();
$xui->server->new_ech_cert('example.com');
$xui->server->cert_hash(cert_file: '/etc/ssl/cert.pem');
$xui->server->remote_cert_hash('cloudflare-dns.com');
$xui->server->scan_reality_target('www.cloudflare.com:443');
$xui->server->scan_reality_targets('104.16.0.0/24');

// Logs and services.
$xui->server->logs(100, level: 'warning');
$xui->server->xray_logs(100, filter: 'alice');
$xui->server->amneziawg_logs(100);
$xui->server->install_xray('latest');
$xui->server->stop_xray();
$xui->server->restart_xray();
$xui->server->update_geofile('geosite.dat');

// Cluster IP sync and OpenAPI.
$xui->server->client_ips();
$xui->server->push_client_ips($rows);
$xui->server->openapi();
```

Backup to Telegram:

```php
$xui->send_backup_to_tg_bot();
```

### Settings and API tokens

```php
$xui->settings->settings();
$xui->settings->get('webPort');
$xui->settings->update(['webPort' => 2053]);
$xui->settings->default_settings();
$xui->settings->factory_defaults();
$xui->settings->default_xray_config();
$xui->settings->validate_regex('(?m)^general-purpose$');
$xui->settings->update_user('admin', 'old', 'admin2', 'new');
$xui->settings->restart();
$xui->settings->test_smtp();
$xui->settings->test_tg_bot();
$xui->settings->test_discord();
$xui->settings->tg_bot_capabilities();

$xui->settings->api_tokens();
$xui->settings->create_api_token('monitoring', XUI\Settings::SCOPE_MONITOR);
$xui->settings->set_api_token_enabled(2, false, XUI\Settings::SCOPE_MONITOR);
$xui->settings->delete_api_token(2, XUI\Settings::SCOPE_MONITOR);
```

### Xray

```php
// Config template.
$xui->xray->template();
$xui->xray->settings();                 // stdClass config tree
$xui->xray->get('routing');
$xui->xray->update(['log' => ['loglevel' => 'warning']]);
$xui->xray->set($fullConfig);
$xui->xray->default_config();
$xui->xray->outbounds_traffic();
$xui->xray->result();
$xui->xray->reset_outbounds_traffic('proxy');

// Config testing and live routing state.
$xui->xray->test_outbound($outbound, $allOutbounds, 'tcp');
$xui->xray->test_outbounds($outbounds, $allOutbounds, 'real');
$xui->xray->balancer_status('b1,b2');
$xui->xray->balancer_override('b1', 'proxy');
$xui->xray->route_test(['domain' => 'example.com', 'port' => 443]);

// Third-party integrations.
$xui->xray->warp('reg', ['privateKey' => $priv, 'publicKey' => $pub]);
$xui->xray->nord('countries');
$xui->xray->pia('countries');

// Geodata.
$xui->xray->geodata_files();
$xui->xray->geodata_categories('geosite.dat', q: 'goog');
$xui->xray->geodata_entries('geosite.dat', 'google', limit: 100);
$xui->xray->geodata_validate(['geosite:google', 'geosite:missing']);

// Outbound subscriptions.
$xui->xray->outbound_subs();
$xui->xray->create_outbound_sub('https://example.com/sub');
$xui->xray->update_outbound_sub(1, 'https://example.com/sub', ['remark' => 'upstream']);
$xui->xray->refresh_outbound_sub(1);
$xui->xray->move_outbound_sub(1, 'up');
$xui->xray->parse_outbound_sub('https://example.com/sub');
$xui->xray->delete_outbound_sub(1);
```

### Routing

```php
use XUI\Xray\Routing\Routing;

$routing = $xui->xray->routing();
if ($routing !== false) {
    $rule = Routing::rule('in-443', 'proxy')
        ->domain(['geosite:google', 'domain:example.com'])
        ->network('tcp');

    $routing->add_rule($rule);
    $routing->get_rule('in-443', 'proxy');
    $routing->update_rule('in-443', 'proxy', $rule);
    $routing->delete_rule('in-443', 'proxy');
    $routing->has_rule('in-443', 'proxy');

    $balancer = Routing::balancer('auto')
        ->selector(['proxy-'])
        ->strategy_type('leastPing');
    $routing->add_balancer($balancer);
}
```

`Rule` and `Balancer` expose fluent getters/setters documented through `@method` blocks, so PHPStorm completes
`domain()`, `port()`, `selector()`, `strategy_type()` and friends.

### Reverse

Removed from 3x-ui v2.9.4, kept for older panels.

```php
$reverse = $xui->xray->reverse();
if ($reverse !== false) {
    $reverse->add_bridge('bridge-1', 'reverse.xui');
    $reverse->add_portal('portal-1', 'reverse.xui');
    $reverse->update();
}
```

### Subscription server

The subscription server runs on its own port (default `2096`). Point a second `Request` at it and use
`XUI\Subscriptions` with the path prefixes configured in **Settings → Subscription**.

```php
use XUI\Handler\Request;
use XUI\Subscriptions;

$subscriptions = new Subscriptions(new Request(Request::config('panel.example.com', 2096)));

$subscriptions->links('/sub/', $sub_id);        // base64 body
$subscriptions->links_array('/sub/', $sub_id);  // decoded link list
$subscriptions->info('/sub/', $sub_id);         // ?format=info
$subscriptions->page('/sub/', $sub_id);         // HTML info page
$subscriptions->hwid_status('/sub/', $sub_id);
$subscriptions->json('/json/', $sub_id);        // Xray JSON subscription
$subscriptions->clash('/clash/', $sub_id);      // Clash/Mihomo YAML
```

### WebSocket events

The panel pushes real-time events on `/ws` (session cookie required):

```php
$xui->websocket_url(); // ws://panel.example.com:2053/xxxxx/ws
```

Each message uses `{type, payload, time}` with `time` in Unix milliseconds.
`XUI\Handler\WebSocketEvent` names every event and decodes one message:

```php
use XUI\Handler\WebSocketEvent;

$message = WebSocketEvent::decode($rawMessage);

switch ($message['type']) {
    case WebSocketEvent::STATUS:
        $status = WebSocketEvent::payload($message['payload']);
        break;

    case WebSocketEvent::TRAFFIC:
        $traffic = WebSocketEvent::payload($message['payload']);
        break;

    case WebSocketEvent::INVALIDATE:
        // Re-fetch the REST resource named by $message['payload']['type'].
        break;
}
```

Event types: `status`, `traffic`, `client_stats`, `inbounds`, `outbounds`, `nodes`, `notification`,
`xray_state`, `invalidate`. Xray states: `running`, `stop`, `error`.

---

## Coding style

The library follows one consistent style. Contributions must match it.

- **PHP 8.2**, PSR-4 autoloading, namespace root `XUI\` mapped to `src/`.
- **4 spaces**, no tabs, one blank line between methods, no closing `?>` tag.
- **Class names** are `StudlyCase`. **Methods, properties and variables** are `snake_case`.
- **Constants** are `UPPER_SNAKE_CASE` (`UNIT_GIGABYTE`, `SCOPE_ADMIN`). Protocol cipher constants keep the
  cipher name (`METHOD_aes_128_gcm`) because the constant name is the wire value.
- **Explicit nullable types**: `?string $value = null`, never the implicit `string $value = null`.
- **Every class, property and method has a PHPDoc block.** Public methods document `@param`, `@return`, and
  `@link` to the matching Xray documentation when one exists.
- **Return types are declared**, and unions are used where the panel returns different shapes.
- **Magic accessors declare `@method` blocks** so the fluent API stays discoverable.
- **One responsibility per class.** Protocol/transport configuration lives in `XUI\Inbounds\Protocols\*` and
  `XUI\Outbounds\Protocols\*`, never in endpoint classes.
- **Endpoint classes only build requests** through the private `_request()` / `_requests()` helpers and return
  `Result` (or `Result[]`).
- **Panel names stay camelCase on the wire.** PHP maps snake_case to the API key in one place, so request
  bodies are easy to audit.
- **`match`** is used for protocol/transport dispatch instead of long expression `switch` blocks.
- **No `declare(strict_types=1)`** and no static-analysis suppressions.
- **No hidden side effects**: configuration builders are pure; network calls happen only in endpoint classes.

---

## Project structure

```text
src/
├── Clients.php                     Clients endpoints
├── Clients/
│   └── Client.php                  Typed client payload
├── Handler/
│   ├── Request.php                 Guzzle wrapper, auth, CSRF, routing
│   ├── Result.php                  Immutable response object
│   └── WebSocketEvent.php          WebSocket event names + decoder
├── Helper/
│   ├── Happ.php                    Happ share-link generator
│   └── Statics.php                 Constants and small helpers
├── Hosts.php                       Host override groups
├── Inbounds.php                    Inbounds endpoints + conversions
├── Inbounds/
│   ├── InboundConfig.php           Contract for protocol builders
│   ├── Settings.php                Generic protocol settings
│   ├── Sniffing.php                Sniffing builder
│   ├── StreamSettings.php          Inbound transport builder
│   └── Protocols/
│       ├── AmneziaWG/              Settings, ServerSettings, AmneziaWG
│       ├── Http/                   Settings, Http
│       ├── Hysteria/               Settings, Hysteria
│       ├── Mixed/                  Settings, MixedInbound
│       ├── Mtproto/                Settings, Mtproto
│       ├── Shadowsocks/            Settings, Shadowsocks
│       ├── Socks/                  Settings, Socks
│       ├── Trojan/                 Settings, Trojan
│       ├── Tunnel/                 Settings, Tunnel
│       ├── Vless/                  Settings, Vless
│       ├── Vmess/                  Settings, Vmess
│       ├── WireGuard/              Settings, WireGuard
│       └── InboundTrait.php        Shared InboundConfig implementation
├── Nodes.php                       Remote node endpoints
├── Outbounds.php                   Typed outbound helper
├── Outbounds/
│   ├── StreamSettings.php          Outbound transport builder
│   └── Protocols/                  Blackhole, Dns, Freedom, Http, Shadowsocks,
│                                   Socks, Trojan, Vless, Vmess
├── Server.php                      Status, logs, backups, certificates
├── Settings.php                    Panel settings + API tokens
├── Settings/
│   └── Settings.php                Panel settings container
├── SubBalancers.php                JSON-subscription balancers
├── Subscriptions.php               Subscription server client
├── Xray.php                        Xray config, integrations, tests
├── Xray/
│   ├── Reverse/Reverse.php         Reverse section
│   ├── Routing/                    Routing, Rule, Balancer
│   └── Settings.php                Xray config container
└── Xui.php                         Entry point
```

---

## Compatibility

| Component | Tested / supported |
| --- | --- |
| PHP | 8.2, 8.3, 8.4 |
| 3x-ui | v3.x API (some features such as nodes, AmneziaWG and subscription balancers are panel-version dependent) |
| Xray-core | v25.x config schema (transports and security as documented by XTLS) |
| Guzzle | `^7.11` |
| estaheri/json | `^1.1.3` |

Older 3x-ui releases may return `404` for newer endpoints; the corresponding methods will then return a failed
`Result` instead of throwing.

---

## Troubleshooting

**Login fails with `success: false`**
Check the panel path and port. If 2FA is enabled, pass the OTP as the third argument:
`$xui->login($user, $password, $code)`.

**`401` / `403` after the session worked before**
The cookie expired. Call `$xui->login()` again or switch to a Bearer token. Cookie files live in
`src/.cookie` by default; make sure the directory is writable by the PHP process.

**CSRF errors on cookie sessions**
The token is obtained before login, refreshed after login, and fetched lazily before the first unsafe request
on a reused session. If you manage the session cookie yourself, call `$xui->csrf_token()` once to force a
refresh.

**TLS verification fails on a self-signed panel**
Prefer pinning the certificate:

```php
$request = new Request(Request::config(
    host: 'panel.local',
    port: 2053,
    has_ssl: true,
    extra: ['verify' => '/path/to/ca.pem'],
));
```

Disabling verification (`extra: ['verify' => false]`) is intended for local development only.

**Requests hang**
Pass `timeout: 15` to `Request::config()` and make sure the panel is reachable from the PHP host (the panel
may be firewalled to the browser network only).

**A monitor token cannot create clients**
That is expected: `monitor` is read-only. Use an `admin` token or a cookie session.

**Database restore uploads fail**
`import_db()` streams the file as multipart `db`. Check PHP `upload_max_filesize` / `post_max_size` on the
panel host.

---

## Contributing

1. Fork the repository and create a branch.
2. Match the [coding style](#coding-style) and keep PHPDoc complete.
3. Run `php -l` on every changed file and `composer validate`.
4. Keep pull requests focused: one feature/fix per PR.
5. Describe the affected 3x-ui version and, when relevant, the Xray transport/protocol.

Bug reports are most useful with: PHP version, 3x-ui version, the endpoint/method called, the request payload
(with secrets removed) and the returned `Result` fields (`code`, `msg`, `error`).

---

## Security

- **Never commit credentials, API tokens or cookie files.** `.cookie/` is git-ignored; keep `.env` files out of
  the repository.
- **Treat `admin` API tokens like the panel password.** The panel stores only their SHA-256 hash, but the
  plaintext is returned once at creation, so store it in a secret manager or environment variable.
- **Prefer scoped tokens** (`monitor`, `node-sync`, `node-admin`) for automation that does not need full access.
- **Keep TLS verification enabled** outside local development, and pin certificates when possible.
- **Back up the panel database before destructive calls** such as `import_db()`, `delete_orphans()`,
  `delete_depleted()`, `reset_all_traffics()` or panel/node updates.

---

## License

This library is released under the [MIT License](./LICENSE).

---

## Special Thanks

- [MHSanaei](https://github.com/MHSanaei) — 3x-ui panel
- [alireza0](https://github.com/alireza0/) — 3x-ui contributors

---

## Support project

### Give Star ⭐

**If this library is helpful to you, please consider giving it a star.**

### Donate 💵

**Help improve this library with a donation** ❤️

- TRX: `TXFE1je6Ed7fADvxAQXXo2g45eQtXvwith`
- TON: `UQDb44qyae9n0hmgay3Bs_oom6RR8cZbLF5_9UCei0q13T0b`
- USDT (TRON): `TCTyFGJVkCgruAYmvPpetF6jVybuZSpTg6`
- USDT (TRX): `UQBnnLMdbAH6Pq86lsH9jEySH-D5___ctqUFKiuBXnd74FTD`
