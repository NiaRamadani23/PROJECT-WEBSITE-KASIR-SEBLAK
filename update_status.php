<?php
require 'config/database.php';
session_start();
if (!isset($_SESSION['id_penjual'])) { 
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pesanan = $_POST['id_pesanan'];
    $status_pesanan = $_POST['status_pesanan'];

    $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?");
    $stmt->bind_param("si", $status_pesanan, $id_pesanan);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    $stmt->close();
}
?>