<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Handle OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Include your database connection file
include 'db.php';

// Get the category name from the query string, if provided
$category_name = isset($_GET['category']) ? $_GET['category'] : null;

// SQL query to fetch category data
$sql = "SELECT id, name, description, img FROM categories WHERE 1 = 1";

// Apply category name filter if provided
if ($category_name) {
  $sql .= " AND name = :category_name";  // Filter by category name
}

// Prepare and execute the query
$stmt = $pdo->prepare($sql);

// Bind the category name if it exists
if ($category_name) {
  $stmt->bindParam(':category_name', $category_name);
}

$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch categories

// Return the category data in JSON format
echo json_encode($categories);
?>