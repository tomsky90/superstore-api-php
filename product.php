<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

include 'db.php'; // Make sure this file connects to your database properly.

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$product_id) {
  echo json_encode(['error' => 'No product id provided']);
  exit;
}

$sql = "SELECT p.id, p.name AS product_name, p.price,p.description, p.img, p.img2, p.is_new, p.is_featured, p.is_trending, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$product_id]);

// Correctly fetch the product data
$product = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($product ?: ['error' => 'Product not found']);