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

// Your existing query logic
$category = isset($_GET['category']) ? $_GET['category'] : null;
$is_featured = isset($_GET['is_featured']) ? $_GET['is_featured'] : null;
$is_trending = isset($_GET['is_trending']) ? $_GET['is_trending'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : null;

// Construct SQL Query
$sql = "
    SELECT p.id, 
           p.name AS product_name, 
           p.is_new, 
           p.is_trending, 
           p.price, 
           p.img, 
           p.img2,
           c.name AS category_name
    FROM products p
    JOIN subcategories s ON p.subcategory_id = s.id
    JOIN subcategory_category sc ON s.id = sc.subcategory_id
    JOIN categories c ON sc.category_id = c.id
    WHERE 1 = 1
";

// Apply category filter
if ($category) {
  $sql .= " AND c.name = :category";
}

// Apply is_featured filter
if ($is_featured) {
  $sql .= " AND p.is_featured = 1";
}

if ($is_trending) {
  $sql .= " AND p.is_trending = 1";
}

// Sorting logic
if ($sort) {
  $sort_parts = explode(":", $sort);
  $sort_field = $sort_parts[0];
  $sort_direction = strtoupper($sort_parts[1]);
  $sql .= " ORDER BY p.$sort_field $sort_direction";
}

// Grouping to avoid duplicates (optional)
$sql .= " GROUP BY p.id"; // Ensures unique product IDs

$stmt = $pdo->prepare($sql);

// Bind category if provided
if ($category) {
  $stmt->bindParam(':category', $category);
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch unique products

echo json_encode($products); // Return data as JSON
?>