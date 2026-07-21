# vastark recaptcha PHP SDK

## 要求

- PHP >= 7.0
- ext-curl
- ext-json

## 安装

手动引入：

```php
require_once 'recaptcha/Types.php';
require_once 'recaptcha/Client.php';
```

## 快速开始

```php
use Vastark\Recaptcha\Client;
use Vastark\Recaptcha\Config;
use Vastark\Recaptcha\FailureMode;
use Vastark\Recaptcha\GenerateRequest;

$client = new Client(new Config(
    'http://127.0.0.1:8090',
    'your-api-key',
    3.0,
    FailureMode::STRICT_BLOCK,
    // 可选：传入当前请求的 trace ID，自动作为 X-Log-Id 透传
    function () { return 'trace-id-from-ctx'; }
));

$resp = $client->generate(new GenerateRequest(
    'partner-site-a',
    'text',
    320,
    ['device_fingerprint' => 'fp_xxx']
));

echo $resp->data['captcha_id'];
echo $resp->data['verify_ticket'];
```

## 核心能力

- `Client::generate(GenerateRequest)` — 调用开放平台接口生成验证码（后端使用）
- `Client::verify(VerifyRequest)` — 调用开放平台接口二次校验（后端使用）
- `GenerateRequest::$extra` / `VerifyRequest::$extra` — 可透传扩展字段（例如 `device_fingerprint`）
- `Client::normalizeClicks(array)` — 坐标序列标准化：`[[x, y], ...]` → `[["x" => x, "y" => y], ...]`
- `X-Log-Id` 自动透传（通过 `Config::$logIdFactory` 回调）
- 业务封禁可感知：`error_enum=E_BANNED`（通过 `SDKError` 异常抛出）
- 故障策略：
  - `FailureMode::STRICT_BLOCK`（严格阻断，默认）
  - `FailureMode::DEGRADE_ALLOW`（降级放行，返回 `["degraded" => true]` 的 `Envelope`）
  - 约束：`DEGRADE_ALLOW` 仅用于服务不可用场景；`E_BANNED` 不属于放行条件

## 兼容性

| PHP 版本 | 状态 |
|----------|------|
| 7.0 - 7.4 | 支持 |
| 8.0 - 8.x | 支持 |

## 声明条款

1. 商业授权与 API 调用：
本项目采用 MIT 协议开源。使用本项目代码连接到官方 API 服务，不受本协议限制，仅需遵循服务条款。

2. 本地运行与修改：
允许任何人下载、修改、自用本项目代码。但若将修改后的代码作为商业产品对外分发，请务必联系我们获得商业授权。

3. 免责条款：
本软件按"现状"提供，作者不对因使用本代码导致的任何数据丢失、服务器故障或法律纠纷承担任何责任。
