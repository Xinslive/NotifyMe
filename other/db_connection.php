<?php
$mysqli = mysqli_connect(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: '',
    getenv('DB_PASSWORD') ?: '',
    getenv('DB_NAME') ?: ''
);
if ($mysqli->connect_error) {
    die('数据库连接失败 (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
