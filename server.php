<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || ! (in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || php_sapi_name() === 'cli-server')
) {
    http_response_code(403);
    exit('Forbidden');
}

$filePath = __DIR__ . '/../../../' . ltrim(explode('?', $_SERVER['REQUEST_URI'])[0], '/');

if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|woff|tff)$/', $filePath)) {
    if (is_file($filePath)) {
        // Workaround for `mime_content_type` returning `text/plain` for CSS files.
        $mimeType = substr($filePath, -4) === '.css' ? 'text/css' : mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);

        readfile($filePath);

        exit(0);
    } else {
        http_response_code(404);
        exit('File not found: ' . $filePath);
    }
}

require_once __DIR__ . '/../../../shopware.php';
