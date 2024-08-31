<?php
$mysqli = new mysqli("localhost", "数据库账号", "数据库密码", "数据库名");
if ($mysqli->connect_error) {
    die("连接失败: " . $mysqli->connect_error);
}