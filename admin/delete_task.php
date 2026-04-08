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

if (isset($_GET['id'])) {
    $stmt = $mysqli->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);

    if ($stmt->execute()) {
        header("Location: index.php?deleted=1");
        exit;
    } else {
        echo "任务删除失败: " . $stmt->error;
    }

    $stmt->close();
}

$mysqli->close();
?>
