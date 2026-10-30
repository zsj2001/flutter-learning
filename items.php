<?php
session_start();
require 'db.php';
include_once 'admindashboard.php';

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$editItem = null;

// Get all item statuses for the dropdown list
$statusQuery = $pdo->query("SELECT * FROM Item_Status ORDER BY itemStatusName");
$statuses = $statusQuery->fetchAll();

// Check whether the admin wants to edit an item
if (isset($_GET['edit'])) {
    $itemID = $_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM Item WHERE itemID = :id");
    $stmt->execute(['id' => $itemID]);
    $editItem = $stmt->fetch();

    if (!$editItem) {
        $message = 'Item not found';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add or create new item
    if (isset($_POST['save'])) {
        $itemID = $_POST['itemID'] ?? '';
        $name = trim($_POST['itemName'] ?? '');
        $picture = trim($_POST['itemPic'] ?? '');
        $price = $_POST['itemPrice'] ?? '';
        $statusID = $_POST['itemStatusID'] ?? '';

        // server-side picture filename validation
        $extension = strtolower(pathinfo($picture, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if ($picture === '') {
            $message = 'Picture filename is required.';
        } elseif (!in_array($extension, $allowed_extensions, true)) {
            $message = 'Invalid file type. Only .jpg, .jpeg, .png, .gif and .webp are allowed.';
        } elseif ($name === '' || !is_numeric($price) || (float) $price < 0 || (float) $price > 10.00 || $statusID === '') {
            $message = 'Please enter a valid name, price and status';
        } else {
            $normalizedName = str_replace(' ', '', $name);     // Normalize input name by removing all spaces for duplicate check
            // Check whether item name already exists
            if ($itemID !== '') {
                // (Edit) Check if another item already has the same name
                $stmtCheck = $pdo->prepare("SELECT itemID FROM Item WHERE REPLACE(itemName, ' ', '') = REPLACE(:name, ' ', '') AND itemID != :id");
                $stmtCheck->execute(['name' => $normalizedName, 'id' => $itemID]);
            } else {
                // (Add) Check if item name already exists
                $stmtCheck = $pdo->prepare("SELECT itemID FROM Item WHERE REPLACE(itemName, ' ', '') = REPLACE(:name, ' ', '')");
                $stmtCheck->execute(['name' => $normalizedName]);
            }

            if ($stmtCheck->fetch()) {
                $message = 'This item name already exists';
            } else {
                if ($itemID !== '') {
                    // Update existing item
                    $stmt = $pdo->prepare("UPDATE Item SET itemName = :name, itemPic = :picture, itemPrice = :price, itemStatusID = :statusID WHERE itemID = :id");
                    $stmt->execute([
                        'name' => $name,   // Saves the format user typed
                        'picture' => $picture,
                        'price' => $price,
                        'statusID' => $statusID,
                        'id' => $itemID
                    ]);
                    $successMessage = 'Item updated successfully';
                } else {
                    // Insert new item
                    $stmt = $pdo->prepare(
                        "INSERT INTO Item(itemName, itemPic, itemPrice, itemStatusID)
                        VALUES(:name, :picture, :price, :statusID)"
                    );
                    $stmt->execute([
                        'name' => $name,
                        'picture' => $picture,
                        'price' => $price,
                        'statusID' => $statusID
                    ]);
                    $successMessage = 'Item added successfully';
                }

                $_SESSION['message'] = $successMessage;
                header('Location: items.php');
                exit;
            }
        }
    }

    // Delete an item
    if (isset($_POST['delete'])) {
        $itemID = $_POST['itemID'];

        try {
            $stmt = $pdo->prepare(
                "DELETE FROM Item WHERE itemID = :id"
            );
            $stmt->execute(['id' => $itemID]);

            $_SESSION['message'] = 'Item deleted successfully';
            header('Location: items.php');
            exit;
        } catch (PDOException $e) {
            // The item may still be used by a tray or vending record.
            $message = 'This item cannot be deleted because it is still linked to other records';
        }
    }
}

// Get the items to display in the table
$stmt = $pdo->query(
    "SELECT Item.*, Item_Status.itemStatusName
     FROM Item
     JOIN Item_Status
       ON Item.itemStatusID = Item_Status.itemStatusID
     ORDER BY Item.itemID"
);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="grid-layout">
    <div class="card">
    <!--button type="button" onclick="location.href='admindashboard.html'">Back to Dashboard</button-->
    <?php if ($message !== ''): ?>
        <p class="message" id="statusMessage"><?= $message ?></p>

        <script>
        setTimeout(function() {
            const message = document.getElementById('statusMessage');

            if (message) {
                message.style.transition = 'opacity 0.5s ease'; // takes .5 seconds to blend the message into the background until completely invisible
                message.style.opacity = '0';

                setTimeout(function() {
                    message.remove();
                }, 500); // .5s = 500ms
            }
        }, 5000);  // 5s = 5000ms
        </script>
    <?php endif; ?>

    <h2><?= $editItem ? 'Update Item' : 'Add New Item' ?></h2>

    <form method="POST" action="items.php">
        <?php if ($editItem): ?>
            <input type="hidden" name="itemID"
                value="<?= $editItem['itemID'] ?>">
        <?php endif; ?>

        <label>Item Name<span class="required-asterisk">*</span></label>
        <input type="text" name="itemName"
            value="<?= $editItem['itemName'] ?? '' ?>"
            required>

        <label>Picture filename<span class="required-asterisk">*</span></label>
        <input type="text" name="itemPic"
            value="<?= $editItem['itemPic'] ?? '' ?>" 
            pattern="^.*\.(jpg|jpeg|png|gif|webp)$"
            title="The filename must end with .jpg/.jpeg/.png/.gif/.webp"
            required>

        <label>Item price (RM)<span class="required-asterisk">*</span></label>
        <input type="number" name="itemPrice"
            min="0" max="10.00" step="0.01"
            value="<?= $editItem['itemPrice'] ?? '' ?>"
            required>

        <label>Item Status<span class="required-asterisk">*</span></label>
        <select name="itemStatusID" required>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= $status['itemStatusID'] ?>"
                    <?= (string) ($editItem['itemStatusID'] ?? '') === (string) $status['itemStatusID'] ? 'selected' : '' ?>>
                    <?= $status['itemStatusName'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="save">
            <?= $editItem ? 'Update' : 'Add' ?>
        </button>
        <?php if ($editItem): ?>
            <button type="button" onclick='location.href="items.php"'>Cancel</button>
        <?php endif; ?>
    </form>
    </div>

    <div class="card">
    <h2>Item listing</h2>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Picture</th>
                <th>Price (RM)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['itemName'] ?></td>
                    <td><?= $item['itemPic'] ?? '' ?></td>
                    <td><?= number_format((float) $item['itemPrice'], 2) ?></td>
                    <td><?= $item['itemStatusName'] ?></td>
                    <td>
                        <form method="GET" action="items.php">
                            <input type="hidden" name="edit"
                                value="<?= $item['itemID'] ?>">
                            <button type="submit" class="btn-edit">Edit</button>
                        </form> 

                        <form method="POST" action="items.php"
                            onsubmit="return confirm('Delete this item?');">
                            <input type="hidden" name="itemID"
                                value="<?= $item['itemID'] ?>">
                            <button type="submit" class="btn-delete" name="delete">Delete</button>
                            <!-- use GET in edit to retrieve itemID and its information. POST in delete submit delete button action and itemID to database -->
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>
