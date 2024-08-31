<?php
$mysqli = mysqli_connect("localhost", "数据库账号", "数据库密码", "数据库名");
if ($mysqli->connect_error) {
    die('数据库连接失败 (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
