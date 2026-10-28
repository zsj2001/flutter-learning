<?php
session_start();
require_once 'db.php';

// the bill is kept in the session, make an empty one if there is none yet
if (!isset($_SESSION['bill'])) {
    $_SESSION['bill'] = [];
}

// Get all trays from the vending machine
$sql = "SELECT
            t.trayID,
            t.trayLabel,
            t.trayItemQty,
            t.trayCapacity,
            i.itemName,
            i.itemPic,
            i.itemPrice
        FROM Tray t
        INNER JOIN Item i ON t.itemID = i.itemID
        INNER JOIN Machine m ON t.macID = m.macID
        WHERE m.macLoc = :location
        ORDER BY t.trayID";

$stmt = $pdo->prepare($sql);
$stmt->execute(['location' => 'Win Hotel Sibu']);

$trays = $stmt->fetchAll();

// When "Add to Bill" is pressed, the form comes back to this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postTray = filter_input(INPUT_POST, 'trayID', FILTER_VALIDATE_INT);
    $postQty  = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if ($postTray && $postQty && $postQty > 0) {
        // look for the tray in the list from the database
        // (use the price and picture from the database, not from the form)
        foreach ($trays as $tray) {
            if ($tray['trayID'] == $postTray) {
                $newQty = $postQty;

                // cannot be more than the stock
                if ($newQty > $tray['trayItemQty']) {
                    $newQty = $tray['trayItemQty'];
                }

                if ($newQty > 0 && !isset($_SESSION['bill'][$postTray])) {
                    $_SESSION['bill'][$postTray] = [
                        'name'     => $tray['itemName'],
                        'pic'      => $tray['itemPic'],
                        'price'    => (float) $tray['itemPrice'],
                        'quantity' => $newQty
                    ];
                }
                break;
            }
        }
    }

    // go back to the page so refreshing will not add the item again
    header('Location: index.php');
    exit;
}

// how many different items are in the bill (shown on the icon) superscript
$billCount = count($_SESSION['bill']);

// Get the tray ID from the URL
$trayID = filter_input(INPUT_GET, 'tray', FILTER_VALIDATE_INT);

$selectedTray = null;

// Check whether the selected tray exists
if ($trayID !== false && $trayID !== null && $trayID > 0) {
    foreach ($trays as $tray) {
        if ($tray['trayID'] == $trayID) {
            $selectedTray = $tray;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vending Machine Interface</title>

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

        h2 {
            text-align: center;
        }

        .title-row {
            position: relative;
            margin-bottom: 20px;
        }

        .bill-icon {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 28px;
            text-decoration: none;
        }

        .bill-count {
            position: absolute;
            top: -8px;
            right: -10px;
            background: #c22712;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            border-radius: 9px;
            text-align: center;
        }

        .search-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        select, button {
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        select {
            flex: 1;
            min-width: 0;
        }

        button {
            background: #c22712;
            color: white;
            border: none;
            cursor: pointer;
        }

        .product img {
            width: 180px;
            height: 180px;
            object-fit: contain;
            margin: 10px auto;
        }

        .price {
            color: #181818;
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stock {
            color: #555;
            margin: 15px 0 25px;
        }

        .message {
            text-align: center;
            color: #666;
            padding: 20px 0;
        }

        .quantity-section {
            margin-top: 20px;
        }

        .quantity-counter {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .quantity-counter button {
            width: 42px;
            height: 42px;
            font-size: 22px;
        }

        .quantity-counter input {
            width: 35px;
            padding: 10px;
            text-align: center;
            font-size: 18px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .quantity-counter button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .total-amount {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
            padding: 15px;
        }

        .total-amount label {
            font-size: 20px;
            font-weight: bold;
        }

        .total-amount .price {
            margin: 0;
        }

        .bill-form {
            margin-top: 20px;
            text-align: center;
        }

        .add-to-bill {
            width: 100%;
            max-width: 300px;
            padding: 14px 20px;
            background: #c22712;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }

        .add-to-bill:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="title-row">
            <h2>Hi, welcome</h2>
            <a href="bill.php" class="bill-icon" title="View bill">&#129534;
                <?php if ($billCount > 0) { ?>
                    <span class="bill-count"><?php echo $billCount; ?></span>
                <?php } ?>
            </a>
        </div>

        <form method="GET" id="trayForm">
            <div class="search-row">
                <select name="tray" id="tray" required>
                    <option value="">Select the tray for the item you want</option>

                    <?php foreach ($trays as $tray): ?>
                        <option
                            value="<?= $tray['trayID'] ?>"
                            <?= ($selectedTray !== null && $selectedTray['trayID'] === $tray['trayID']) ? 'selected' : '' ?>>
                            <?= $tray['trayLabel']?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="button" id="resetBtn">Reset</button>
            </div>
        </form>
    </div>

    <?php if ($selectedTray !== null) { ?>
        <div class="card product">
            <?php if (!empty($selectedTray['itemPic'])) { ?>
                <img src="<?php echo 'images/' . $selectedTray['itemPic']; ?>" alt="<?php echo $selectedTray['itemName']; ?>">
            <?php } ?>

            <p class="price">
                RM <?php echo number_format((float) $selectedTray['itemPrice'], 2); ?>
            </p>

            <p class="stock">
                Stock: <?php echo $selectedTray['trayItemQty']; ?> / <?php echo $selectedTray['trayCapacity']; ?>
            </p>

            <div class="quantity-section">
                <div class="quantity-counter">
                    <label style="font-size: 20px; font-weight: bold;">Quantity:</label>

                    <button type="button" id="decreaseQty">−</button>
                    <input
                        type="number"
                        id="quantity"
                        value="<?php echo $selectedTray['trayItemQty'] > 0 ? 1 : 0; ?>"
                        min="<?php echo $selectedTray['trayItemQty'] > 0 ? 1 : 0; ?>"
                        max="<?php echo $selectedTray['trayItemQty']; ?>"   <?php // cannot select quantity higher than what is actually available in stock ?>
                        readonly
                    >
                    <button
                        type="button"
                        id="increaseQty"
                        <?php
                            if ($selectedTray['trayItemQty'] < 1) {
                                echo 'disabled';                     // If out of stock, disabled + button
                            }
                        ?>
                    >+</button>
                </div>
            </div>

            <div class="total-amount">
                <label>Total amount:</label>
                <p class="price" id="totalAmount">RM 0.00</p>
            </div>

            <form action="index.php" method="POST" class="bill-form">
                <input type="hidden" name="trayID" value="<?php echo $selectedTray['trayID']; ?>">
                <input type="hidden" name="quantity" id="billQuantity">
                <button type="submit" class="add-to-bill"
                    <?php
                        if ($selectedTray['trayItemQty'] < 1) {
                            echo 'disabled';
                        }
                    ?>
                >Add to Bill
                </button>
            </form>
        </div>
    <?php } elseif ($trayID !== null && $trayID !== false) { ?>
        <div class="card message">Invalid tray selection</div>
    <?php } ?>

    <script>
        // Get the elements from the page
        const traySelect = document.getElementById('tray');    // <select name="tray" id="tray"
        const trayForm = document.getElementById('trayForm');
        const resetBtn = document.getElementById('resetBtn');

        // Show the selected tray
        traySelect.addEventListener('change', function () {
            if (traySelect.value !== '') {
                trayForm.requestSubmit();
            }
        });

        // Reset the page
        resetBtn.addEventListener('click', function () {
            window.location.href = window.location.pathname;
        });

        // Get the quantity buttons and inputs
        const quantityInput = document.getElementById('quantity');
        const decreaseBtn = document.getElementById('decreaseQty');
        const increaseBtn = document.getElementById('increaseQty');
        const totalAmount = document.getElementById('totalAmount');
        const billQuantity = document.getElementById('billQuantity');

        // Get the item price from PHP
        const itemPrice = <?php echo json_encode((float) ($selectedTray['itemPrice'] ?? 0)); ?>;

        // Run this code only when a tray has been selected
        const maxQuantity = Number(quantityInput.max);
        const minQuantity = Number(quantityInput.min);

        // Calculate the total price
        function updateTotal() {
            const quantity = Number(quantityInput.value);
            const total = itemPrice * quantity;

            totalAmount.textContent = 'RM ' + total.toFixed(2);
        }

        // Enable or disable the quantity buttons
        function updateButtons() {
            const quantity = Number(quantityInput.value);

            if (quantity <= minQuantity) {
                decreaseBtn.disabled = true;
            } else {
                decreaseBtn.disabled = false;
            }

            if (quantity >= maxQuantity) {
                increaseBtn.disabled = true;
            } else {
                increaseBtn.disabled = false;
            }
        }

        // Decrease the quantity
        decreaseBtn.addEventListener('click', function () {
            let quantity = Number(quantityInput.value);

            if (quantity > minQuantity) {
                quantity = quantity - 1;
                quantityInput.value = quantity;
            }

            updateButtons();
            updateTotal();

            if (billQuantity) {
                billQuantity.value = quantityInput.value;
            }
        });

        // Increase the quantity
        increaseBtn.addEventListener('click', function () {
            let quantity = Number(quantityInput.value);

            if (quantity < maxQuantity) {
                quantity = quantity + 1;
                quantityInput.value = quantity;
            }

            updateButtons();
            updateTotal();

            if (billQuantity) {
                billQuantity.value = quantityInput.value;
            }
        });

        // Set the initial quantity and total
        updateButtons();
        updateTotal();

        // Set the quantity that will be sent to the bill
        if (billQuantity) {
            billQuantity.value = quantityInput.value;
        }
    </script>
</body>
</html>
