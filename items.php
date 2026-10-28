
<?php
require 'db.php';

$message = '';
$editItem = null;

// Display a message after an action
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get all item statuses for the dropdown list
$statusQuery = $pdo->query(
    "SELECT * FROM Item_Status ORDER BY itemStatusName"
);
$statuses = $statusQuery->fetchAll();

// Check whether the admin wants to edit an item
if (isset($_GET['edit'])) {
    $itemID = (int) $_GET['edit'];

    $stmt = $pdo->prepare(
        "SELECT * FROM Item WHERE itemID = :id"
    );
    $stmt->execute(['id' => $itemID]);

    $editItem = $stmt->fetch();

    if (!$editItem) {
        $message = 'Item not found.';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add a new item
    if (isset($_POST['add'])) {
        $name = trim($_POST['itemName']);
        $picture = trim($_POST['itemPic']);
        $price = $_POST['itemPrice'];
        $statusID = (int) $_POST['itemStatusID'];

        if ($name === '' || !is_numeric($price)
            || (float) $price < 0 || !is_finite((float) $price)) {
            $message = 'Please enter a valid name and price.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO Item
                (itemName, itemPic, itemPrice, itemStatusID)
                VALUES (:name, :picture, :price, :statusID)"
            );

            $stmt->execute([
                'name' => $name,
                'picture' => $picture !== '' ? $picture : null,
                'price' => $price,
                'statusID' => $statusID
            ]);

            header('Location: items.php?message=' .
                urlencode('Item added successfully.'));
            exit;
        }
    }

    // Update an existing item
    if (isset($_POST['update'])) {
        $itemID = (int) $_POST['itemID'];
        $name = trim($_POST['itemName']);
        $picture = trim($_POST['itemPic']);
        $price = $_POST['itemPrice'];
        $statusID = (int) $_POST['itemStatusID'];

        if ($name === '' || !is_numeric($price)
            || (float) $price < 0 || !is_finite((float) $price)) {
            $message = 'Please enter a valid name and price.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE Item
                 SET itemName = :name,
                     itemPic = :picture,
                     itemPrice = :price,
                     itemStatusID = :statusID
                 WHERE itemID = :id"
            );

            $stmt->execute([
                'name' => $name,
                'picture' => $picture !== '' ? $picture : null,
                'price' => $price,
                'statusID' => $statusID,
                'id' => $itemID
            ]);

            header('Location: items.php?message=' .
                urlencode('Item updated successfully.'));
            exit;
        }
    }

    // Delete an item
    if (isset($_POST['delete'])) {
        $itemID = (int) $_POST['itemID'];

        try {
            $stmt = $pdo->prepare(
                "DELETE FROM Item WHERE itemID = :id"
            );
            $stmt->execute(['id' => $itemID]);

            header('Location: items.php?message=' .
                urlencode('Item deleted successfully.'));
            exit;
        } catch (PDOException $e) {
            // The item may still be used by a tray or vending record.
            $message = 'This item cannot be deleted because it is '
                . 'still linked to other records.';
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

// Escape values before displaying them in HTML
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Item Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h1>Item Management</h1>

    <p><a href="index.php">Back to Dashboard</a></p>

    <?php if ($message !== ''): ?>
        <p class="message"><?= e($message) ?></p>
    <?php endif; ?>

    <h2><?= $editItem ? 'Update Item' : 'Add New Item' ?></h2>

    <form method="POST" action="items.php">

        <?php if ($editItem): ?>
            <input type="hidden" name="itemID"
                value="<?= e($editItem['itemID']) ?>">
        <?php endif; ?>

        <label>Item Name</label>
        <input type="text" name="itemName" maxlength="150"
            value="<?= e($editItem['itemName'] ?? '') ?>"
            required>

        <label>Picture Filename</label>
        <input type="text" name="itemPic" maxlength="255"
            placeholder="Example: milo.jpg"
            value="<?= e($editItem['itemPic'] ?? '') ?>">

        <label>Item Price (RM)</label>
        <input type="number" name="itemPrice"
            min="0" max="99999999.99" step="0.01"
            value="<?= e($editItem['itemPrice'] ?? '') ?>"
            required>

        <label>Item Status</label>
        <select name="itemStatusID" required>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status['itemStatusID']) ?>"
                    <?= (string) ($editItem['itemStatusID'] ?? '')
                        === (string) $status['itemStatusID']
                        ? 'selected' : '' ?>>
                    <?= e($status['itemStatusName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($editItem): ?>
            <button type="submit" name="update">
                Update Item
            </button>
            <a href="items.php">Cancel</a>
        <?php else: ?>
            <button type="submit" name="add">
                Add Item
            </button>
        <?php endif; ?>

    </form>

    <h2>Existing Items</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
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
                    <td><?= e($item['itemID']) ?></td>
                    <td><?= e($item['itemName']) ?></td>
                    <td><?= e($item['itemPic'] ?? '') ?></td>
                    <td><?= number_format(
                        (float) $item['itemPrice'], 2
                    ) ?></td>
                    <td><?= e($item['itemStatusName']) ?></td>
                    <td>
                        <a href="items.php?edit=<?= e($item['itemID']) ?>">
                            Edit
                        </a>

                        <form method="POST" action="items.php"
                            onsubmit="return confirm('Delete this item?');">
                            <input type="hidden" name="itemID"
                                value="<?= e($item['itemID']) ?>">
                            <button type="submit" name="delete">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

</body>
</html>
