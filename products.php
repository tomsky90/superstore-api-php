<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');
// CORS headers, DB connection, etc.
include 'db.php';

// Check if the category parameter is provided
$category_name = isset($_GET['category']) ? $_GET['category'] : null;

// Initialize category_id
$category_id = null;

// If a category name is provided, get the category ID
if ($category_name) {
  $category_sql = "SELECT id FROM categories WHERE name = :category_name";
  $category_stmt = $pdo->prepare($category_sql);
  $category_stmt->bindParam(':category_name', $category_name);
  $category_stmt->execute();
  $category = $category_stmt->fetch(PDO::FETCH_ASSOC);

  // Check if category exists
  if ($category) {
    $category_id = $category['id'];
  } else {
    // If category name is invalid, return an empty array
    echo json_encode([]);
    exit;
  }
}

// Base SQL query to fetch products
$sql = "SELECT p.id, p.name AS product_name, p.price, p.img, p.img2, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE 1 = 1";

// Apply category filter if a valid category ID is present
if ($category_id) {
  $sql .= " AND p.category_id = :category_id";
}

// Prepare and execute the query
$stmt = $pdo->prepare($sql);

// Bind category ID if provided
if ($category_id) {
  $stmt->bindParam(':category_id', $category_id);
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the products as a JSON response
echo json_encode($products);
?>