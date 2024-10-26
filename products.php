<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

include 'db.php'; // Include your DB connection

// Get query parameters
$category_name = isset($_GET['category']) ? $_GET['category'] : null;
$subcategory_ids = isset($_GET['subcategories']) ? $_GET['subcategories'] : []; // Array of subcategory IDs
$max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : null;

// Parse `is_featured` and `is_trending` as booleans from the query params
$featured = isset($_GET['is_featured']) ? (bool) $_GET['is_featured'] : null;
$trending = isset($_GET['is_trending']) ? (bool) $_GET['is_trending'] : null;

// Log the input values for debugging
error_log("Category Name: " . print_r($category_name, true));
error_log("Subcategory IDs: " . print_r($subcategory_ids, true));
error_log("Max Price: " . print_r($max_price, true));
error_log("Sort: " . print_r($sort, true));
error_log("Featured: " . print_r($featured, true));
error_log("Trending: " . print_r($trending, true));

// Initialize bind parameters
$bindParams = [];

// Base SQL query to fetch products, now including `is_new`
$sql = "SELECT p.id, p.name AS product_name, p.price, p.img, p.img2, p.is_new, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE 1 = 1";

// Apply category filter if provided
if ($category_name) {
  $sql .= " AND c.name = ?";
  $bindParams[] = $category_name;
}

// Apply subcategory filter by IDs if provided
if (!empty($subcategory_ids)) {
  $subcategory_placeholder_ids = implode(',', array_fill(0, count($subcategory_ids), '?'));
  $sql .= " AND p.subcategory_id IN ($subcategory_placeholder_ids)";
  $bindParams = array_merge($bindParams, $subcategory_ids);
}

// Apply max price filter if provided
if ($max_price !== null) {
  $sql .= " AND p.price <= ?";
  $bindParams[] = $max_price;
}

// Apply featured filter if requested
if ($featured === true) {
  $sql .= " AND p.is_featured = 1";
}

// Apply trending filter if requested
if ($trending === true) {
  $sql .= " AND p.is_trending = 1";
}

// Apply sorting
$sort_order = strtolower($sort) === 'desc' ? 'DESC' : 'ASC';
$sql .= " ORDER BY p.price $sort_order";

// Debug: Log the final SQL and bind parameters
error_log("Final SQL: $sql");
error_log("Bind Params: " . json_encode($bindParams));

// Prepare and execute the main query
$stmt = $pdo->prepare($sql);

// Bind positional parameters
foreach ($bindParams as $index => $value) {
  $stmt->bindValue($index + 1, $value); // Positional parameters start from 1 in PDO
}

// Execute the query and fetch results
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the products as a JSON response, including `is_new`
echo json_encode($products);

?>