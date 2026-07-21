<?php
/**
 * recaptcha SDK 常量与数据类定义
 *
 * 兼容 PHP >= 7.0
 */

namespace Vastark\Recaptcha;

/**
 * 故障策略
 */
final class FailureMode
{
    /** @var string 严格阻断 */
    const STRICT_BLOCK = 'strict_block';
    /** @var string 降级放行 */
    const DEGRADE_ALLOW = 'degrade_allow';
}

/**
 * 错误类型
 */
final class ErrorKind
{
    /** @var string 网络错误 */
    const NETWORK = 'network_error';
    /** @var string 超时错误 */
    const TIMEOUT = 'timeout_error';
    /** @var string 上游错误 */
    const UPSTREAM = 'upstream_error';
    /** @var string 业务错误 */
    const BUSINESS = 'business_error';
}

/**
 * 业务封禁错误枚举值
 */
final class ErrorEnum
{
    const BANNED = 'E_BANNED';
}

/**
 * SDK 统一异常
 */
class SDKError extends \Exception
{
    /** @var string */
    public $kind;

    /**
     * @param string $kind    错误类型（ErrorKind 常量）
     * @param string $message 错误信息
     * @param int    $code    错误码
     */
    public function __construct($kind, $message, $code = 0)
    {
        $this->kind = $kind;
        parent::__construct("{$kind}: {$message}", $code);
    }
}

/**
 * 客户端配置
 */
class Config
{
    /** @var string */
    public $baseUrl;
    /** @var string */
    public $apiKey;
    /** @var float */
    public $timeout;
    /** @var string */
    public $failureMode;
    /** @var callable|null */
    public $logIdFactory;

    /**
     * @param string        $baseUrl      服务端地址
     * @param string        $apiKey       API 密钥
     * @param float         $timeout      超时时间（秒），默认 3.0
     * @param string        $failureMode  故障策略
     * @param callable|null $logIdFactory X-Log-Id 生成回调
     */
    public function __construct($baseUrl, $apiKey, $timeout = 3.0, $failureMode = null, $logIdFactory = null)
    {
        $this->baseUrl = \rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = (float) $timeout;
        $this->failureMode = $failureMode ?: FailureMode::STRICT_BLOCK;
        $this->logIdFactory = $logIdFactory;
    }
}

/**
 * 统一响应信封
 */
class Envelope
{
    /** @var int */
    public $code;
    /** @var string */
    public $message;
    /** @var array<string, mixed> */
    public $data;

    /**
     * @param int    $code
     * @param string $message
     * @param array  $data
     */
    public function __construct($code = 0, $message = '', array $data = array())
    {
        $this->code = (int) $code;
        $this->message = (string) $message;
        $this->data = $data;
    }

    /**
     * @param array $raw JSON 解码后的关联数组
     * @return self
     */
    public static function fromArray(array $raw)
    {
        return new self(
            isset($raw['code']) ? (int) $raw['code'] : 0,
            isset($raw['message']) ? (string) $raw['message'] : '',
            isset($raw['data']) && is_array($raw['data']) ? $raw['data'] : array()
        );
    }
}

/**
 * 生成验证码请求
 */
class GenerateRequest
{
    /** @var string */
    public $siteId;
    /** @var string */
    public $type;
    /** @var int */
    public $width;
    /** @var array<string, mixed> */
    public $extra;

    /**
     * @param string $siteId 站点 ID
     * @param string $type   验证码类型（可选，如 "text"）
     * @param int    $width  宽度（可选）
     * @param array  $extra  扩展字段
     */
    public function __construct($siteId, $type = '', $width = 0, array $extra = array())
    {
        $this->siteId = $siteId;
        $this->type = (string) $type;
        $this->width = (int) $width;
        $this->extra = $extra;
    }
}

/**
 * 二次校验请求
 */
class VerifyRequest
{
    /** @var string */
    public $captchaId;
    /** @var string */
    public $verifyTicket;
    /** @var array<int, array{x: int, y: int}> */
    public $clicks;
    /** @var array<string, mixed> */
    public $extra;

    /**
     * @param string $captchaId    验证码 ID
     * @param string $verifyTicket 验证票据
     * @param array  $clicks       点击坐标列表 [{"x": int, "y": int}, ...]
     * @param array  $extra        扩展字段
     */
    public function __construct($captchaId, $verifyTicket, array $clicks, array $extra = array())
    {
        $this->captchaId = $captchaId;
        $this->verifyTicket = $verifyTicket;
        $this->clicks = $clicks;
        $this->extra = $extra;
    }
}
