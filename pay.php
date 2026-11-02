<?php
declare(strict_types=1);
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        // get values
        $macID = $_POST['macID'];
        $trayID = $_POST['trayID']; 
        $remarks = trim($_POST['remarks']);

        // insert Tray_Item_Movement
        $stmt = $pdo->prepare("
            INSERT INTO Tray_item_Movement(movType, Qty, movTime, remarks)
            VALUES(:movType, :Qty, :movTime, :remarks)
        ");
        $stmt->execute(['id' => movID]);
        $stmt->fetchColumn();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving item";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
    </style>
</head>
<body>
    <p>Payment successful</p>
</body>
</html>
