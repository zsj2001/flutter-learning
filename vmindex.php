<?php

declare(strict_types=1);

require_once 'db.php';

// Get all trays and their associated items for the machine.
$stmt = $pdo->prepare("
    SELECT
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
    ORDER BY t.trayID
");

$stmt->execute(['location' => 'Win Hotel Sibu']);
$trays = $stmt->fetchAll();

// Get the selected tray ID from the URL.
$search_tray = filter_input(
    INPUT_GET,
    'tray',
    FILTER_VALIDATE_INT
);

$selectedTray = null;

// Find the selected tray among the trays belonging to this machine.
if ($search_tray !== false && $search_tray !== null && $search_tray > 0) {
    foreach ($trays as $tray) {
        if ((int) $tray['trayID'] === $search_tray) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        h2 {text-align: center;}
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
        select {flex: 1; min-width: 0;}
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
        .stock {color: #555; margin: 15px 0 25px;}
        .message {
            text-align: center;
            color: #666;
            padding: 20px 0;
        }
        .quantity-section {margin-top: 20px;}
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
            width: 60px;
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
            justify-content: space-between;
            gap: 10px;
            margin-top: 25px;
            padding: 15px;
        }
        .total-amount label {
            font-size: 20px;
            font-weight: bold;
        }
        .total-amount .price {margin: 0;}
        .cart {margin-top: 20px; text-align: center;}
        .add-to-cart {
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
        .add-to-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Hi, welcome</h2>

        <form method="GET" id="trayForm">
            <div class="search-row">
                <select name="tray" id="tray" required>
                <option value="">Select tray</option>

                <?php foreach ($trays as $tray): ?>
                    <option
                        value="<?= (int) $tray['trayID'] ?>"
                        <?= ($selectedTray !== null && (int) $selectedTray['trayID'] === (int) $tray['trayID']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(
                            $tray['trayLabel'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
                </select>
                <button type="button" id="resetBtn">Reset</button>
            </div>
        </form>
    </div>

    <?php if ($selectedTray !== null): ?>
        <div class="card product">
            <?php if (!empty($selectedTray['itemPic'])): ?>
                <img
                    src="<?= htmlspecialchars(
                        'images/' . $selectedTray['itemPic'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    alt="<?= htmlspecialchars(
                        $selectedTray['itemName'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            <?php endif; ?>

            <p class="price">
                RM <?= number_format(
                    (float) $selectedTray['itemPrice'],
                    2
                ) ?>
            </p>

            <p class="stock">
                Stock: <?= (int) $selectedTray['trayItemQty'] ?> / <?= (int) $selectedTray['trayCapacity'] ?>
            </p>

            <div class="quantity-section">
                <div class="quantity-counter">
                    <label style="font-size: 20px; font-weight: bold;">Quantity:</label>
                    <button type="button" id="decreaseQty">−</button>
                    <input
                        type="number"
                        id="quantity"
                        value="<?= $selectedTray['trayItemQty'] > 0 ? 1 : 0 ?>"
                        min="<?= $selectedTray['trayItemQty'] > 0 ? 1 : 0 ?>"
                        max="<?= $selectedTray['trayItemQty'] ?>"
                        readonly
                    >
                    <button type="button" id="increaseQty"
                        <?= $selectedTray['trayItemQty'] < 1 ? 'disabled' : '' ?>>+</button>
                </div>
            </div>

            <div class="total-amount">
                <div></div><label>Total amount:</label>
                <p class="price" id="totalAmount">RM 0.00</p></div>div>
            </div>
 
            <form action="cart.php" method="POST" class="cart">
                <input type="hidden" name="trayID" value="<?= (int) $selectedTray['trayID'] ?>">
                <input type="hidden" name="quantity" id="cartQuantity">
                <button type="submit" class="add-to-cart" <?= $selectedTray['trayItemQty'] < 1 ? 'disabled' : '' ?>>
                    Add to Cart
                </button>
            </form>
        </div>

    <?php elseif ($search_tray !== null && $search_tray !== false): ?>

        <div class="card message">
            Invalid tray selection.
        </div>

    <?php endif; ?>

<script>
    const traySelect = document.getElementById('tray');
    const trayForm = document.getElementById('trayForm');
    const resetBtn = document.getElementById('resetBtn');

    // Display the selected item's details immediately.
    traySelect.addEventListener('change', function () {
        if (this.value !== '') {
            trayForm.requestSubmit();
        }
    });

    // Clear the selection and hide the product details.
    resetBtn.addEventListener('click', function () {
        window.location.href = window.location.pathname;
    });

    const qtyInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decreaseQty');
    const increaseBtn = document.getElementById('increaseQty');
    const totalAmount = document.getElementById('totalAmount');
    
    // Get the item price from PHP.
    const itemPrice = <?= json_encode((float) ($selectedTray['itemPrice'] ?? 0)) ?>;

    if (qtyInput && decreaseBtn && increaseBtn && totalAmount) {
        const maxQty = Number(qtyInput.max);
        const minQty = Number(qtyInput.min);

        function updateTotal() {
            const qty = Number(qtyInput.value);
            const total = itemPrice * qty;

            totalAmount.textContent = 'RM ' + total.toFixed(2);
        }

        function updateQty() {
            const qty = Number(qtyInput.value);

            decreaseBtn.disabled = qty <= minQty;
            increaseBtn.disabled = qty >= maxQty;
        }

        decreaseBtn.addEventListener('click', function () {
            let qty = Number(qtyInput.value);

            if (qty > minQty) {
                qtyInput.value = qty - 1;
            }

            updateQty();
        });

        increaseBtn.addEventListener('click', function () {
            let qty = Number(qtyInput.value);

            if (qty < maxQty) {
                qtyInput.value = qty + 1;
            }

            updateQty();
        });

        updateQty();
    }

    
const cartQuantity = document.getElementById('cartQuantity');

if (cartQuantity && qtyInput) {
    function syncCartQuantity() {
        cartQuantity.value = qtyInput.value;
    }
    decreaseBtn.addEventListener('click', syncCartQuantity);
    increaseBtn.addEventListener('click', syncCartQuantity);

    syncCartQuantity();
}
</script>
</body>
</html>
