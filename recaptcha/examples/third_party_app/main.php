<?php
/**
 * 第三方应用接入示例
 *
 * 运行方式：php examples/third_party_app/main.php
 */

require_once __DIR__ . '/../../../recaptcha/Types.php';
require_once __DIR__ . '/../../../recaptcha/Client.php';

use Vastark\Recaptcha\Client;
use Vastark\Recaptcha\Config;
use Vastark\Recaptcha\FailureMode;
use Vastark\Recaptcha\GenerateRequest;

$client = new Client(new Config(
    'http://127.0.0.1',
    'replace-with-api-key',
    3.0,
    FailureMode::STRICT_BLOCK
));

$resp = $client->generate(new GenerateRequest(
    'partner-site-a',
    'text',
    320,
    array('device_fingerprint' => 'fp_example_1234567890abcdef')
));

echo "captcha_id: " . ($resp->data['captcha_id'] ?? 'N/A') . "\n";
echo "verify_ticket: " . ($resp->data['verify_ticket'] ?? 'N/A') . "\n";
