<?php
session_start();
require_once 'db.php';

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $listing_id = $_GET['id'];
    $buyer_id = $_SESSION['user_id'];

    // 1. Get the item details before we delete it
    $stmt = $conn->prepare("SELECT title, price FROM listings WHERE id = ?");
    $stmt->execute([$listing_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        // 2. Insert into the orders table
        $stmt = $conn->prepare("INSERT INTO orders (user_id, item_title, price) VALUES (?, ?, ?)");
        $stmt->execute([$buyer_id, $item['title'], $item['price']]);

        // 3. Delete from the active listings table
        $stmt = $conn->prepare("DELETE FROM listings WHERE id = ?");
        $stmt->execute([$listing_id]);
    }
}
?>