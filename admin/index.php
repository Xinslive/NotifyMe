<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>监控任务设置</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        function convertFrequency() {
            var frequencyMin = document.getElementById('frequency').value;
            var frequencySec = frequencyMin * 60;
            document.getElementById('frequency_sec').value = frequencySec;
        }

        function populateForm(task) {
            if (task) {
                document.getElementById('id').value = task.id;
                document.getElementById('url').value = task.url;
                document.getElementById('content_keywords').value = task.content_keywords;
                document.getElementById('frequency').value = task.frequency / 60;
                document.getElementById('frequency_sec').value = task.frequency;
                document.getElementById('submit-button').value = '更新任务';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>监控任务设置</h1>
        
        <form action="add_task.php" method="POST" onsubmit="convertFrequency()">
            <input type="hidden" name="id" id="id" value="">
            <input type="hidden" name="frequency_sec" id="frequency_sec" value="">
            <label for="url">URL</label>
            <input type="text" name="url" id="url" required>

            <label for="content_keywords">关键词 (空格分隔)</label>
            <input type="text" name="content_keywords" id="content_keywords" required>

            <label for="frequency">监控频率（分钟）</label>
            <input type="number" name="frequency" id="frequency" value="1" min="1" required>

            <input type="submit" id="submit-button" value="保存任务">
        </form>

        <table>
            <tr>
                <th>URL</th>
                <th>关键词</th>
                <th>频率</th>
                <th>状态</th>
                <th>操作选项</th>
            </tr>
            <?php
            include '../other/db_connection.php';
            $result = $mysqli->query("SELECT * FROM tasks");
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['url'] . "</td>";
                echo "<td>" . $row['content_keywords'] . "</td>";
                echo "<td>" . ($row['frequency'] / 60) . "</td>";  // 显示为分钟
                echo "<td class='status'>" . ($row['status'] == 0 ? '监控中' : '已完成') . "</td>";
                echo "<td>";
                echo "<a href='#' onclick='populateForm(" . json_encode($row) . ");' style='color: #007bff;'>编辑</a>";
                echo " | ";
                echo "<a href='delete_task.php?id=" . $row['id'] . "' style='color: #dc3545;'>删除</a>";
                echo "</td>";
                echo "</tr>";
            }
            $mysqli->close();
            ?>
        </table>
    </div>
</body>
</html>
