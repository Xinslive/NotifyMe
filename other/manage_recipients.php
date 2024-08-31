<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $recipients_file = 'mail.txt';
    $message = '';
    if (!file_exists($recipients_file)) {
        $file_handle = fopen($recipients_file, 'w');
        if ($file_handle) {
            fclose($file_handle);
        } else {
            $message = "无法创建邮件列表文件，请稍后再试。";
            header('Location: index.html?message=' . urlencode($message));
            exit;
        }
    }

    if ($action == 'add' && !empty($_POST['new_email'])) {
        $new_email = filter_var($_POST['new_email'], FILTER_SANITIZE_EMAIL);
        if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $recipients = file($recipients_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (in_array($new_email, $recipients)) {
                $message = "邮箱 $new_email 已存在，无需重复登记，有消息第一时间通知你哦！";
            } else {
                $file_handle = fopen($recipients_file, 'a');
                if (flock($file_handle, LOCK_EX)) {
                    fwrite($file_handle, $new_email . PHP_EOL);
                    fflush($file_handle);
                    flock($file_handle, LOCK_UN);
                    fclose($file_handle);
                    $message = "邮箱 $new_email 已成功登记，有消息第一时间通知你哦！";
                } else {
                    fclose($file_handle);
                    $message = "无法添加邮箱，请稍后再试。";
                }
            }
        } else {
            $message = "无效的邮箱地址。";
        }
    } else {
        $message = "请填写你的邮箱";
    }
    header('Location: ../index.html?message=' . urlencode($message));
    exit;
}
?>

