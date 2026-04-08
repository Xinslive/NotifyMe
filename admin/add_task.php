<?php
// HTTP Basic Auth
$adminUser = getenv('ADMIN_USER') ?: 'admin';
$adminPass = getenv('ADMIN_PASSWORD') ?: '';

if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== $adminUser ||
    $_SERVER['PHP_AUTH_PW'] !== $adminPass) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo '认证失败';
    exit;
}
?>
<?php
include '../other/db_connection.php';

$id = $_POST['id'] ?? null;
$url = $_POST['url'];
$content_keywords = $_POST['content_keywords'];
$frequency_sec = $_POST['frequency_sec'];

if ($id) {
    $stmt = $mysqli->prepare("UPDATE tasks SET url=?, content_keywords=?, frequency=?, status=0, last_run_time=0 WHERE id=?");
    $stmt->bind_param("ssii", $url, $content_keywords, $frequency_sec, $id);
} else {
    $stmt = $mysqli->prepare("INSERT INTO tasks (url, content_keywords, frequency, status, last_run_time) VALUES (?, ?, ?, 0, 0)");
    $stmt->bind_param("ssi", $url, $content_keywords, $frequency_sec);
}

$stmt->execute();
$stmt->close();
$mysqli->close();

header("Location: index.php");
?>
