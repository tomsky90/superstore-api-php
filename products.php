<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

include 'db.php'; // Include your DB connection

// Get query parameters
$category_name = isset($_GET['category']) ? $_GET['category'] : null;
$subcategories = isset($_GET['subcategories']) ? $_GET['subcategories'] : [];
$max_price = isset($_GET['max_price']) ? (float) $_GET['max_price'] : null; // Cast to float
$sort = isset($_GET['sort']) ? $_GET['sort'] : null;

// Log the input values
error_log("Category Name: " . print_r($category_name, true));
error_log("Subcategories: " . print_r($subcategories, true));
error_log("Max Price: " . print_r($max_price, true));
error_log("Sort: " . print_r($sort, true));

// Initialize bind parameters
$bindParams = [];

// Base SQL query to fetch products
$sql = "SELECT p.id, p.name AS product_name, p.price, p.img, p.img2, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE 1 = 1";

// If a category name is provided, fetch the corresponding category ID
if ($category_name) {
  $sql .= " AND c.name = ?";  // Use positional placeholder for category name
  $bindParams[] = $category_name;  // Add to bind parameters
}

// Convert subcategory names to IDs (if names are passed)
$subcategory_ids = [];
if (!empty($subcategories)) {
  // Prepare to fetch subcategory IDs
  $subcategory_placeholders = implode(',', array_fill(0, count($subcategories), '?'));
  $subcategory_sql = "SELECT id FROM subcategories WHERE name IN ($subcategory_placeholders)";

  // Prepare and execute the query to get subcategory IDs
  $subcategory_stmt = $pdo->prepare($subcategory_sql);
  $subcategory_stmt->execute($subcategories); // Execute with the subcategory names
  $subcategory_ids = $subcategory_stmt->fetchAll(PDO::FETCH_COLUMN); // Fetch IDs

  // Log fetched subcategory IDs
  error_log("Fetched Subcategory IDs: " . print_r($subcategory_ids, true));

  // If no valid subcategory IDs were found, return empty
  if (empty($subcategory_ids)) {
    echo json_encode([]);
    exit;
  }

  // Add subcategory ID filter to the main query
  $subcategory_placeholder_ids = implode(',', array_fill(0, count($subcategory_ids), '?'));
  $sql .= " AND p.subcategory_id IN ($subcategory_placeholder_ids)";

  // Merge subcategory IDs into bindParams
  $bindParams = array_merge($bindParams, $subcategory_ids);
}

// Apply max price filter if provided
if ($max_price !== null) { // Check explicitly against null
  $sql .= " AND p.price <= ?";
  $bindParams[] = $max_price; // Add max price to bind parameters
}

// Apply sorting
if ($sort && in_array(strtolower($sort), ['asc', 'desc'])) {
  $sql .= " ORDER BY p.price " . strtoupper($sort);
} else {
  $sql .= " ORDER BY p.id ASC";
}

// Debug: Log the final SQL and bind parameters
error_log("Final SQL: $sql");
error_log("Bind Params: " . json_encode($bindParams));

// Prepare and execute the query
$stmt = $pdo->prepare($sql);

// Bind positional parameters
foreach ($bindParams as $index => $value) {
  $stmt->bindValue($index + 1, $value); // Positional parameters start from 1 in PDO
}

// Execute the query and fetch results
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the products as a JSON response
echo json_encode($products);
