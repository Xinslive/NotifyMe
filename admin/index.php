<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>监控任务设置</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        h1, h2 {
            text-align: center;
            color: #444;
        }

        .container {
            width: 90%;
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            box-sizing: border-box;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }

        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #218838;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            min-width: 150px;
            word-wrap: break-word;
        }

        th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: bold;
        }

        td {
            white-space: normal;
        }

        .left-align {
            text-align: left;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #e9ecef;
        }

        .status {
            font-weight: bold;
            color: #28a745;
        }

        .status.completed {
            color: #dc3545;
        }

        a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                padding: 10px;
            }

            table, th, td {
                font-size: 14px;
                padding: 8px;
            }

            input[type="submit"] {
                font-size: 14px;
            }
        }
    </style>
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

        function confirmDelete(url) {
            if (confirm("确定删除此任务吗？")) {
                window.location.href = url;
            }
        }

        function formatKeywords() {
            const cells = document.querySelectorAll('td:nth-child(2)');
            cells.forEach(cell => {
                cell.innerHTML = cell.textContent.replace(/ /g, '<br>');
            });
        }

        window.onload = function() {
            formatKeywords();
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

        <div class="table-wrapper">
            <table>
                <tr>
                    <th class="left-align" style="width: 40%;">URL</th>
                    <th style="width: 20%;">关键词</th>
                    <th style="width: 8%;">频率</th>
                    <th style="width: 8%;">状态</th>
                    <th style="width: 12%;">操作选项</th>
                </tr>
                <?php
                include '../other/db_connection.php';
                $result = $mysqli->query("SELECT * FROM tasks");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td class='left-align'>" . $row['url'] . "</td>";
                    echo "<td>" . $row['content_keywords'] . "</td>";
                    echo "<td>" . ($row['frequency'] / 60) . "</td>";
                    echo "<td class='status'>" . ($row['status'] == 0 ? '监控中' : '已完成') . "</td>";
                    echo "<td>";
                    echo "<a href='#' onclick='populateForm(" . json_encode($row) . ");' style='color: #007bff;'>编辑</a>";
                    echo "  ";
                    echo "<a href='#' onclick='confirmDelete(\"delete_task.php?id=" . $row['id'] . "\")' style='color: #dc3545;'>删除</a>";
                    echo "</td>";
                    echo "</tr>";
                }
                $mysqli->close();
                ?>
            </table>
        </div>
    </div>
</body>
</html>
