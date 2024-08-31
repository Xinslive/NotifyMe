<?php
include 'other/db_connection.php';
require 'other/SMTP/Exception.php';
require 'other/SMTP/PHPMailer.php';
require 'other/SMTP/SMTP.php';

function sendEmail($subject, $message) {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.qq.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'yeuers@foxmail.com';
    $mail->Password = 'smtp密码';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->setFrom('yeuers@foxmail.com', '浮生纪幸');
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->CharSet = 'UTF-8';
    $mail->Body = $message;

    $recipients = file('other/mail.txt', FILE_IGNORE_NEW_LINES);
    $batchSize = 20;
    $totalRecipients = count($recipients);
    
    for ($i = 0; $i < $totalRecipients; $i += $batchSize) {
        $batchRecipients = array_slice($recipients, $i, $batchSize);
        foreach ($batchRecipients as $recipient) {
            $mail->addAddress($recipient);
        }

        if ($mail->send()) {
            echo "批次 " . ($i / $batchSize + 1) . " 邮件已发送！";
        } else {
            logError("批次 " . ($i / $batchSize + 1) . " 邮件发送失败：" . $mail->ErrorInfo);
        }

        $mail->clearAddresses();
        sleep(4);
    }
}


$tasks = getActiveTasks();

foreach ($tasks as $task) {
    $taskId = $task['id'];
    $taskUrl = $task['url'];
    $taskKeywords = array_filter(array_map('trim', explode(' ', $task['content_keywords'])));
    $taskFrequency = $task['frequency'];
    $lastRunTime = $task['last_run_time'];
    $currentTime = time();

    $timeDifference = $currentTime - $lastRunTime;

    if ($timeDifference >= $taskFrequency) {
        $result = checkUrl($taskUrl, $taskKeywords);

        if ($result['notify']) {
            sendEmail('注意：关键词出现啦', $result['message']);
        }

        updateTaskStatus($taskId, $currentTime, $result['notify']);
    }
}

function getActiveTasks() {
    global $mysqli;
    $tasks = [];

    $stmt = $mysqli->prepare("SELECT id, url, content_keywords, frequency, last_run_time FROM tasks WHERE status = ?");
    $status = '0';
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        $result->free();
    } else {
        logError("无法获取任务: " . $mysqli->error);
    }

    $stmt->close();
    return $tasks;
}


function checkUrl($url, $keywords) {
    $curl = curl_init($url);

    if (!$curl) {
        logError("初始化cURL失败 for URL: $url");
        return ['notify' => false, 'message' => "初始化cURL失败"];
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_NOBODY, false);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, false);  // 允许重用连接
    curl_setopt($curl, CURLOPT_FORBID_REUSE, false);   // 禁止禁止重用连接
    curl_setopt($curl, CURLOPT_TCP_NODELAY, true);     // 减少网络延迟
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cache-Control: no-cache', 'Pragma: no-cache'));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);

    $response = curl_exec($curl);
    if ($response === false) {
        $errorMsg = curl_error($curl);
        logError("cURL错误 for URL: $url: " . $errorMsg);
        curl_close($curl);
        return ['notify' => false, 'message' => "cURL错误: " . $errorMsg];
    }

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode !== 200) {
        logError("HTTP请求失败 for URL: $url，状态码: $httpCode");
        return ['notify' => false, 'message' => "HTTP状态码: $httpCode"];
    }

    $baseUri = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
    $linkData = checkKeywordsInLinks($response, $keywords, $baseUri);

    if ($linkData) {
        return [
            'notify' => true,
            'message' => "{$linkData['text']}：{$linkData['url']}"
        ];
    }

    return ['notify' => false, 'message' => "未找到包含所有关键词的链接"];
}


function checkKeywordsInLinks($html, $keywords, $baseUrl) {
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $links = $dom->getElementsByTagName('a');

    foreach ($links as $link) {
        $textContent = $link->textContent;
        $href = $link->getAttribute('href');
        $fullUrl = resolveUrl($href, $baseUrl);

        $matches = true;
        foreach ($keywords as $keyword) {
            if (stripos($textContent, $keyword) === false) {
                $matches = false;
                break;
            }
        }

        if ($matches) {
            return [
                'text' => $textContent,
                'url' => $fullUrl
            ];
        }
    }

    return false;
}


function extractBaseUri($html) {
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $base = $dom->getElementsByTagName('base')->item(0);
    $baseUri = '';

    if ($base && $base->hasAttribute('href')) {
        $baseUri = $base->getAttribute('href');
    } else {
        $urlParts = parse_url($url);
        $baseUri = isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' . $urlParts['host'] : '';
    }

    return rtrim($baseUri, '/');
}

function resolveUrl($href, $baseUri) {
    $parsedBaseUri = parse_url($baseUri);
    $baseScheme = $parsedBaseUri['scheme'] ?? 'http';
    $baseHost = $parsedBaseUri['host'] ?? '';
    $basePath = $parsedBaseUri['path'] ?? '';

    if (preg_match('/^https?:\/\//i', $href)) {
        return $href;
    }

    if (preg_match('/^\/\//i', $href)) {
        return $baseScheme . ':' . $href;
    }

    if (preg_match('/^\//', $href)) {
        return $baseScheme . '://' . $baseHost . $href;
    }

    $pathParts = array_filter(explode('/', $basePath));
    $hrefParts = array_filter(explode('/', $href));

    foreach ($hrefParts as $part) {
        if ($part === '..') {
            array_pop($pathParts);
        } elseif ($part !== '.' && $part !== '') {
            $pathParts[] = $part;
        }
    }

    $resolvedPath = implode('/', $pathParts);
    return $baseScheme . '://' . $baseHost . '/' . ltrim($resolvedPath, '/');
}


function updateTaskStatus($taskId, $currentTime, $notify) {
    global $mysqli;
    $status = $notify ? '1' : '0';
    $stmt = $mysqli->prepare("UPDATE tasks SET status = ?, last_run_time = ? WHERE id = ?");
    $stmt->bind_param("sii", $status, $currentTime, $taskId);
    $stmt->execute();
    $stmt->close();
}

function logError($message) {
    error_log($message);
    echo $message . '<br>';
}
?>
