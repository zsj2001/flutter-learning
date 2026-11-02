<?php
declare(strict_types=1);
session_start();
require_once 'db.php';

// Ensure the bill exists and is not empty
if (!isset($_SESSION['bill']) || empty($_SESSION['bill'])) {
    header('Location: index.php');
    exit;
}

$success = false;
$errorMessage = '';

try {
    // Start database transaction to ensure data integrity
    $pdo->beginTransaction();

    // 1. Get the 'SUCCESS' status ID from Vend_Record_Status
    $stmtStatus = $pdo->prepare("SELECT vrStatusID FROM Vend_Record_Status WHERE vrStatusName = 'SUCCESS'");
    $stmtStatus->execute();
    $vrStatusID = $stmtStatus->fetchColumn();

    if ($vrStatusID === false) {
        throw new Exception("Vend record status 'SUCCESS' not found in database.");
    }

    // Prepare statements for stock check, updates, and logs
    $stmtTrayInfo = $pdo->prepare("SELECT macID, itemID, trayItemQty FROM Tray WHERE trayID = :trayID FOR UPDATE");
    $stmtUpdateStock = $pdo->prepare("UPDATE Tray SET trayItemQty = trayItemQty - :qty WHERE trayID = :trayID");
    
    $stmtVendRecord = $pdo->prepare("
        INSERT INTO Vend_Record (macID, trayID, itemID, vrStatusID, Qty)
        VALUES (:macID, :trayID, :itemID, :vrStatusID, :qty)
    ");
    
    $stmtMovement = $pdo->prepare("
        INSERT INTO Tray_Item_Movement (macID, trayID, movType, Qty, remarks)
        VALUES (:macID, :trayID, 'DISPENSE', :qty, 'Dispensed via successful payment')
    ");

    // Loop through each item in the session bill
    foreach ($_SESSION['bill'] as $trayID => $item) {
        $quantityToBuy = (int)$item['quantity'];

        // Lock and fetch current tray details
        $stmtTrayInfo->execute(['trayID' => $trayID]);
        $tray = $stmtTrayInfo->fetch();

        if (!$tray) {
            throw new Exception("Tray ID $trayID no longer exists.");
        }

        $macID = (int)$tray['macID'];
        $itemID = (int)$tray['itemID'];
        $currentStock = (int)$tray['trayItemQty'];

        // Validate that stock is still sufficient
        if ($currentStock < $quantityToBuy) {
            throw new Exception("Sorry, item '" . htmlspecialchars($item['name']) . "' is out of stock or has insufficient quantity.");
        }

        // 2. Deduct stock from the tray
        $stmtUpdateStock->execute([
            'qty' => $quantityToBuy,
            'trayID' => $trayID
        ]);

        // 3. Insert record into Vend_Record
        $stmtVendRecord->execute([
            'macID' => $macID,
            'trayID' => $trayID,
            'itemID' => $itemID,
            'vrStatusID' => $vrStatusID,
            'qty' => $quantityToBuy
        ]);

        // 4. Insert record into Tray_Item_Movement
        $stmtMovement->execute([
            'macID' => $macID,
            'trayID' => $trayID,
            'qty' => $quantityToBuy
        ]);
    }

    // Commit transaction if all items process successfully
    $pdo->commit();

    // Clear the cart session after a successful transaction
    unset($_SESSION['bill']);
    $success = true;

} catch (Throwable $e) {
    // Rollback changes if any error occurs
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errorMessage = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f8f9f4;
            color: #333;
            text-align: center;
        }
        .card {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 40px auto;
            max-width: 500px;
        }
        .success {
            color: #2e7d32;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .error {
            color: #c22712;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #c22712;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="card">
        <?php if ($success): ?>
            <div class="success">Payment Successful!</div>
            <p>Thank you for your purchase. Your items are being dispensed.</p>
            <a href="index.php" class="btn">Return to Home</a>
        <?php else: ?>
            <div class="error">Transaction Failed</div>
            <p><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="bill.php" class="btn">Back to Bill</a>
        <?php endif; ?>
    </div>
</body>
</html>
