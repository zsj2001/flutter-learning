<?php
declare(strict_types=1);
session_start();
require_once 'db.php';

// make sure bill exists
if (!isset($_SESSION['bill'])) {
    $_SESSION['bill'] = [];
}

$paymentMessage = '';

if (count($_SESSION['bill']) > 0) {
    try {
        $pdo->beginTransaction();

        // get SUCCESS status ID
        $stmt = $pdo->prepare(
            "SELECT vrStatusID FROM Vend_Record_Status
             WHERE vrStatusName = 'SUCCESS'"
        );
        $stmt->execute();
        $vrStatusID = $stmt->fetchColumn();

        // get tray status IDs
        $stmt = $pdo->prepare(
            "SELECT trayStatusID FROM Tray_Status
             WHERE trayStatusName = 'FULL'"
        );
        $stmt->execute();
        $fullStatusID = $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT trayStatusID FROM Tray_Status
             WHERE trayStatusName = 'LOW'"
        );
        $stmt->execute();
        $lowStatusID = $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT trayStatusID FROM Tray_Status
             WHERE trayStatusName = 'EMPTY'"
        );
        $stmt->execute();
        $emptyStatusID = $stmt->fetchColumn();

        // check whether the required statuses exist
        if ($vrStatusID === false || $fullStatusID === false || $lowStatusID === false || $emptyStatusID === false) {
            throw new Exception("Status information not found.");
        }

        // prepare SQL to get tray information
        $getTray = $pdo->prepare(
            "SELECT
                t.macID,
                t.itemID,
                t.trayItemQty,
                t.trayCapacity
             FROM Tray t
             INNER JOIN Machine m ON t.macID = m.macID
             WHERE t.trayID = :trayID
             AND m.macLoc = :location
             FOR UPDATE"
        );

        // prepare SQL to update tray stock
        $updateTray = $pdo->prepare(
            "UPDATE Tray
             SET trayItemQty = :qty,
                 trayStatusID = :statusID
             WHERE trayID = :trayID"
        );

        // prepare SQL to insert vending record
        $insertVend = $pdo->prepare(
            "INSERT INTO Vend_Record(macID, trayID, itemID, vrStatusID, Qty)
             VALUES(:macID, :trayID, :itemID, :statusID, :qty)"
        );

        // prepare SQL to insert tray movement record
        $insertMovement = $pdo->prepare(
            "INSERT INTO Tray_Item_Movement(macID, trayID, movType, Qty, remarks)
             VALUES(:macID, :trayID, :movType, :qty, :remarks)"
        );

        // process every item in the bill
        foreach ($_SESSION['bill'] as $trayID => $item) {
            $trayID = $trayID;
            $qty = $item['quantity'];

            // check the quantity
            if ($trayID <= 0 || $qty <= 0) {
                throw new Exception("Invalid item quantity");
            }

            // get the current tray information
            $getTray->execute([
                'trayID' => $trayID,
                'location' => 'Win Hotel Sibu'
            ]);
            $tray = $getTray->fetch();

            // check whether the tray exists
            if (!$tray) {
                throw new Exception("Tray not found");
            }

            // check whether there is enough stock
            if ($qty > $tray['trayItemQty']) {
                throw new Exception("Not enough stock available");
            }

            // calculate the remaining stock
            $remainingQty = $tray['trayItemQty'] - $qty;

            // determine the new tray status
            if ($remainingQty == 0) {
                $newStatusID = $emptyStatusID;
            } elseif ($remainingQty == $tray['trayCapacity']) {
                $newStatusID = $fullStatusID;
            } else {
                $newStatusID = $lowStatusID;
            }

            // update tray stock and status
            $updateTray->execute([
                'qty' => $remainingQty,
                'statusID' => $newStatusID,
                'trayID' => $trayID
            ]);

            // insert vending record
            $insertVend->execute([
                'macID' => $tray['macID'],
                'trayID' => $trayID,
                'itemID' => $tray['itemID'],
                'statusID' => $vrStatusID,
                'qty' => $qty
            ]);

            // insert tray item movement record
            $insertMovement->execute([
                'macID' => $tray['macID'],
                'trayID' => $trayID,
                'movType' => 'DISPENSE',
                'qty' => $qty,
                'remarks' => 'Item purchased by customer'
            ]);
        }

        // save all database changes
        $pdo->commit();

        // clear the bill after successful payment
        $_SESSION['bill'] = [];

        $paymentMessage = 'Payment successful';
    } catch (Throwable $error) {
        // undo all database changes if something goes wrong
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // keep the bill so the customer can try again
        $paymentMessage = 'Payment failed. Please try again.';

        // save the error for debugging
        error_log($error->getMessage());
    }
} else {
    $paymentMessage = 'Your bill is empty.';
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
    <p><?php echo $paymentMessage; ?></p>
</body>
</html>
