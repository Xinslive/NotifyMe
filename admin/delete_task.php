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
