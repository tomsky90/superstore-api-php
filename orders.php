<?php

// CORS headers
header("Access-Control-Allow-Origin: http://localhost:5173"); // Update this to your frontend's URL
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allowed HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allowed headers

// Handle OPTIONS requests for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

include 'db.php'; // Make sure this file connects to your database properly.
// Load Composer's autoloader
require 'vendor/autoload.php';

// Initialize Dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Access environment variables
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['products'])) {
  echo json_encode(['error' => 'No products in order']);
  exit;
}

// Prepare line items for Stripe
$lineItems = [];
$totalAmount = 0;

foreach ($data['products'] as $product) {
  $lineItems[] = [
    'price_data' => [
      'currency' => 'gbp',
      'product_data' => [
        'name' => $product['title'],
        'images' => [$product['img']],
      ],
      'unit_amount' => $product['price'] * 100, // Stripe expects prices in cents
    ],
    'quantity' => $product['qty'],
  ];
  $totalAmount += $product['price'] * $product['qty']; // Calculate total amount
}

try {
  // Create a new Stripe checkout session
  $session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => $lineItems,
    'mode' => 'payment',
    'success_url' => 'http://localhost:5173', // Your success page
    'cancel_url' => 'http://localhost:5173',   // Your cancel page
  ]);

  // Save the order to the database
  $stripeSessionId = $session->id;
  $customerEmail = $data['customer_email'] ?? null; // Assuming customer email can be passed from the frontend

  // Prepare SQL statement to insert order into the database
  $stmt = $pdo->prepare("INSERT INTO orders (stripe_session_id, total_amount, customer_email) VALUES (?, ?, ?)");
  $stmt->execute([$stripeSessionId, $totalAmount, $customerEmail]);

  // Get the order ID of the newly inserted order
  $orderId = $pdo->lastInsertId();

  // Save each order item to the database
  foreach ($data['products'] as $product) {
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$orderId, $product['id'], $product['title'], $product['price'], $product['qty']]);
  }

  // Return the session ID and order ID to the frontend
  echo json_encode(['stripeSession' => ['id' => $session->id], 'orderId' => $orderId]);
} catch (Exception $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
