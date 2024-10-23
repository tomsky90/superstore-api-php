<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

include 'db.php';

$sql = "SELECT name from subcategories where 1=1";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($subcategories);


?>