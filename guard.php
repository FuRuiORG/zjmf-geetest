<?php
/**
 * 极验插件 25YTheme API 守卫
 * 在 index.php 中 include 此文件，拦截登录/注册 API 的直接 POST 请求（无极验参数则拒绝）
 * 兼容 PHP 5.6+
 */

// 非 POST 放行
if (!isset($_SERVER['REQUEST_METHOD'])) return 1;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') return 1;

// 直接用正则匹配下游登录/注册/重置 API 端点，忽略多余的斜杠
$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
if (!preg_match('#/{1,}(v1/(login|register|pwreset|login_api)|login_pass|mobile_login|login_send|register_(phone|email)|reset_(phone|email)|phone_pass_login|email_login)(\b|$|\?)#', $uri)) return 1;

// 读取数据库配置
$dbConfigFile = CMF_ROOT . 'app/config/database.php';
if (!is_file($dbConfigFile)) return 1;
$dbConfig = include $dbConfigFile;
if (!is_array($dbConfig)) return 1;

try {
    $dsn = 'mysql:host=' . $dbConfig['hostname'] . ';port=' . $dbConfig['hostport'] . ';dbname=' . $dbConfig['database'] . ';charset=' . $dbConfig['charset'];
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ));
    $stmt = $pdo->query("SELECT config FROM " . $dbConfig['prefix'] . "plugin WHERE name = 'Geetest' AND status = 1 LIMIT 1");
    $row = $stmt->fetch();
    if (!$row) return 1;
    $pluginConfig = json_decode($row['config'], true);
    if (!is_array($pluginConfig)) return 1;
    if (empty($pluginConfig['enable_25y_theme'])) return 1;

    // 检查极验参数（兼容两种命名）
    $lot = null;
    if (isset($_POST['lot_number'])) $lot = $_POST['lot_number'];
    elseif (isset($_POST['geetest_lot_number'])) $lot = $_POST['geetest_lot_number'];

    $cap = null;
    if (isset($_POST['captcha_output'])) $cap = $_POST['captcha_output'];
    elseif (isset($_POST['geetest_captcha_output'])) $cap = $_POST['geetest_captcha_output'];

    $tok = null;
    if (isset($_POST['pass_token'])) $tok = $_POST['pass_token'];
    elseif (isset($_POST['geetest_pass_token'])) $tok = $_POST['geetest_pass_token'];

    $gen = null;
    if (isset($_POST['gen_time'])) $gen = $_POST['gen_time'];
    elseif (isset($_POST['geetest_gen_time'])) $gen = $_POST['geetest_gen_time'];

    if (!$lot || !$cap || !$tok || !$gen) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('status' => 400, 'msg' => '请完成人机验证'));
        exit;
    }
} catch (Exception $e) {
    // 数据库不可用时放行
    return 1;
}
