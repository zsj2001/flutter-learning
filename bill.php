<?php
declare(strict_types=1);
session_start();
require_once 'db.php';

// make sure the bill exists
if (!isset($_SESSION['bill'])) {
    $_SESSION['bill'] = [];
}

// get how many are left in the tray right now
function getStock($pdo, $trayID)
{
    $stmt = $pdo->prepare("SELECT trayItemQty FROM Tray WHERE trayID = :id");
    $stmt->execute(['id' => $trayID]);
    $qty = $stmt->fetchColumn();

    if ($qty === false) {
        return 0;
    }
    return $qty;
}

// handle the plus, minus and remove buttons
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postTray = filter_input(INPUT_POST, 'trayID', FILTER_VALIDATE_INT);

    // only continue if this item is really in the bill
    if ($postTray !== false && $postTray !== null && isset($_SESSION['bill'][$postTray])) {

        $currentQty = $_SESSION['bill'][$postTray]['quantity'];

        if ($action === 'plus') {
            // cannot go higher than the stock
            if ($currentQty < getStock($pdo, $postTray)) {
                $_SESSION['bill'][$postTray]['quantity'] = $currentQty + 1;
            }
        } elseif ($action === 'minus') {
            // stop at 1, use Remove to take the item out
            if ($currentQty > 1) {
                $_SESSION['bill'][$postTray]['quantity'] = $currentQty - 1;
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['bill'][$postTray]);
        }
    }

    // reload the page so refreshing will not repeat the action
    header('Location: bill.php');
    exit;
}

$bill = $_SESSION['bill'];
$grandTotal = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f8f9f4;
            color: #333;
        }
        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 0 auto 20px;
            max-width: 600px;
            text-align: center;
        }
        .bill-item {
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: left;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .bill-item img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .bill-info {
            flex: 1;
        }
        .bill-info p {
            margin: 4px 0;
        }
        .qty-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 6px 0;
        }
        .qty-row button {
            width: 30px;
            height: 30px;
            font-size: 18px;
            background: #c22712;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .qty-row button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .remove-btn {
            background: none;
            border: none;
            color: #c22712;
            cursor: pointer;
            font-size: 14px;
        }
        .grand-total {
            font-size: 22px;
            font-weight: bold;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 4px;
            background: #888;
            color: #fff;
            text-decoration: none;
            font-size: 16px;
        }
        .btn-pay {
            background: #c22712;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Your items</h2>

        <?php if (count($bill) === 0) { ?>
            <p>No items yet</p>
        <?php } else { ?>
            <?php foreach ($bill as $trayID => $item) {
                $lineTotal = $item['price'] * $item['quantity'];
                $grandTotal = $grandTotal + $lineTotal;
                $stock = getStock($pdo, $trayID);
            ?>
                <div class="bill-item">
                    <?php if (!empty($item['pic'])) { ?>
                        <img src="images/<?php echo htmlspecialchars($item['pic']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <?php } ?>

                    <div class="bill-info">
                        <p><strong><?php echo htmlspecialchars($item['name']); ?></strong></p>
                        
                        <form method="POST" class="qty-row">
                            <input type="hidden" name="trayID" value="<?php echo $trayID; ?>">
                            <p> Quantity: <?php echo $item['quantity']; ?></p>
                            <button type="submit" name="action" value="minus"
                            <?php if ($item['quantity'] <= 1) { echo 'disabled'; } ?>>−</button>
                            <button type="submit" name="action" value="plus"
                            <?php if ($item['quantity'] >= $stock) { echo 'disabled'; } ?>>+</button>
                        </form>
                        <p>Total: RM <?php echo number_format($lineTotal, 2); ?></p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="trayID" value="<?php echo $trayID; ?>">
                        <button type="submit" name="action" value="remove" class="remove-btn">Remove</button>
                    </form>
                </div>
            <?php } ?>

            <p class="grand-total">Total amount: RM <?php echo number_format($grandTotal, 2); ?></p>

        <?php } ?>

        <a href="index.php" class="btn" style="margin-bottom: 15px;">Add more items</a>
        <?php if (count($bill) > 0) { ?>
            <a href="#" class="btn btn-pay">Pay</a>
        <?php } ?>
    </div>
</body>
</html>
