<?php
$db_port = 3307;
$host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "storeapi";

try {
  $pdo = new PDO("mysql:host=$host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_password, );
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo 'Connection failed: ' . $e->getMessage();
}