<?php
/**
 * recaptcha SDK 客户端
 *
 * 兼容 PHP >= 7.0，使用 curl 扩展发起 HTTP 请求
 */

namespace Vastark\Recaptcha;

/**
 * recaptcha 客户端，用于调用开放平台的生成和校验接口
 */
class Client
{
    const GENERATE_PATH = '/saas/sdk/v1/recaptcha/secure/generate';
    const VERIFY_PATH   = '/saas/sdk/v1/recaptcha/secure/verify';

    /** @var Config */
    private $cfg;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->cfg = $config;
    }

    /**
     * 调用开放平台接口生成验证码
     *
     * @param GenerateRequest $req
     * @return Envelope
     * @throws SDKError
     */
    public function generate(GenerateRequest $req)
    {
        $payload = array('site_id' => $req->siteId);
        if ($req->type !== '') {
            $payload['type'] = $req->type;
        }
        if ($req->width > 0) {
            $payload['width'] = $req->width;
        }
        $payload = \array_merge($payload, $req->extra);
        return $this->postJson(self::GENERATE_PATH, $payload);
    }

    /**
     * 调用开放平台接口二次校验
     *
     * @param VerifyRequest $req
     * @return Envelope
     * @throws SDKError
     */
    public function verify(VerifyRequest $req)
    {
        $payload = array(
            'captcha_id'    => $req->captchaId,
            'verify_ticket' => $req->verifyTicket,
            'clicks'        => $req->clicks,
        );
        $payload = \array_merge($payload, $req->extra);
        return $this->postJson(self::VERIFY_PATH, $payload);
    }

    /**
     * 坐标序列标准化
     *
     * @param array<int, array{0: int, 1: int}> $points  [(x, y), ...]
     * @return array<int, array{x: int, y: int}>
     */
    public static function normalizeClicks(array $points)
    {
        $out = array();
        foreach ($points as $p) {
            $out[] = array('x' => (int) $p[0], 'y' => (int) $p[1]);
        }
        return $out;
    }

    /**
     * @param string $path
     * @param array  $body
     * @return Envelope
     * @throws SDKError
     */
    private function postJson($path, array $body)
    {
        $url = $this->cfg->baseUrl . $path;
        $raw = \json_encode($body);
        if ($raw === false) {
            throw new SDKError(ErrorKind::NETWORK, 'json_encode failed');
        }

        $ch = \curl_init($url);
        if ($ch === false) {
            throw new SDKError(ErrorKind::NETWORK, 'curl_init failed');
        }

        $headers = array(
            'Content-Type: application/json',
            'X-API-Key: ' . $this->cfg->apiKey,
        );
        if ($this->cfg->logIdFactory !== null) {
            $headers[] = 'X-Log-Id: ' . \call_user_func($this->cfg->logIdFactory);
        }

        \curl_setopt_array($ch, array(
            \CURLOPT_POST           => true,
            \CURLOPT_POSTFIELDS     => $raw,
            \CURLOPT_HTTPHEADER     => $headers,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_TIMEOUT        => (int) \ceil($this->cfg->timeout),
            \CURLOPT_CONNECTTIMEOUT => (int) \ceil($this->cfg->timeout),
            // 内网/自签名场景跳过 SSL 验证
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_SSL_VERIFYHOST => 0,
        ));

        $respBody = \curl_exec($ch);
        $httpCode = (int) \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $curlErr  = \curl_error($ch);
        $curlErrNo = \curl_errno($ch);
        \curl_close($ch);

        if ($respBody === false) {
            $kind = ($curlErrNo === \CURLE_OPERATION_TIMEDOUT)
                ? ErrorKind::TIMEOUT
                : ErrorKind::NETWORK;
            return $this->handleError(new SDKError($kind, $curlErr ?: 'curl error'));
        }

        $rawDict = \json_decode($respBody, true);
        if (!\is_array($rawDict)) {
            return $this->handleError(new SDKError(ErrorKind::UPSTREAM, 'invalid json', $httpCode));
        }

        $env = Envelope::fromArray($rawDict);

        if ($httpCode >= 500) {
            return $this->handleError(new SDKError(ErrorKind::UPSTREAM, $env->message, $httpCode));
        }

        if ($env->code !== 0) {
            if (self::isBannedEnvelope($env)) {
                $env->data['ban_blocked'] = true;
                $env->data['error_enum'] = ErrorEnum::BANNED;
            }
            throw new SDKError(ErrorKind::BUSINESS, $env->message, $env->code);
        }

        return $env;
    }

    /**
     * @param Envelope $env
     * @return bool
     */
    private static function isBannedEnvelope(Envelope $env)
    {
        if ($env->code === 1002) {
            return true;
        }
        if (isset($env->data['ban_blocked'])) {
            $v = $env->data['ban_blocked'];
            if ($v === true || $v === 1 || $v === '1' || \strtolower((string) $v) === 'true') {
                return true;
            }
        }
        if (isset($env->data['error_enum'])) {
            if (\strcasecmp(\trim((string) $env->data['error_enum']), ErrorEnum::BANNED) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param SDKError $err
     * @return Envelope
     * @throws SDKError
     */
    private function handleError(SDKError $err)
    {
        if ($this->cfg->failureMode === FailureMode::DEGRADE_ALLOW) {
            return new Envelope(0, 'degraded_allow', array('degraded' => true));
        }
        throw $err;
    }
}
