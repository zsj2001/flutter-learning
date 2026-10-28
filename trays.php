
<?php
require 'db.php';

$message = $_GET['message'] ?? '';
$editTray = null;

// Get dropdown data
$machines = $pdo->query(
    "SELECT macID, macLoc FROM Machine ORDER BY macLoc"
)->fetchAll();

$items = $pdo->query(
    "SELECT itemID, itemName FROM Item ORDER BY itemName"
)->fetchAll();

$statuses = $pdo->query(
    "SELECT trayStatusID, trayStatusName
     FROM Tray_Status ORDER BY trayStatusName"
)->fetchAll();

// Load tray for editing
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM Tray WHERE trayID = :id"
    );
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editTray = $stmt->fetch();

    if (!$editTray) {
        $message = 'Tray not found.';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $isAdd = isset($_POST['add']);
    $isUpdate = isset($_POST['update']);

    if ($isAdd || $isUpdate) {
        $trayID = (int) ($_POST['trayID'] ?? 0);
        $macID = (int) ($_POST['macID'] ?? 0);
        $itemID = (int) ($_POST['itemID'] ?? 0);
        $label = trim($_POST['trayLabel'] ?? '');
        $capacity = filter_var(
            $_POST['trayCapacity'] ?? null,
            FILTER_VALIDATE_INT
        );
        $qty = filter_var(
            $_POST['trayItemQty'] ?? null,
            FILTER_VALIDATE_INT
        );
        $statusID = (int) ($_POST['trayStatusID'] ?? 0);

        if (
            $label === '' || strlen($label) > 20 ||
            $capacity === false || $capacity === null ||
            $capacity < 1 ||
            $qty === false || $qty === null ||
            $qty < 0 || $qty > $capacity ||
            $macID < 1 || $itemID < 1 || $statusID < 1
        ) {
            $message = 'Please check the tray details and quantities.';
        } else {
            try {
                if ($isAdd) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO Tray
                         (macID, itemID, trayLabel, trayCapacity,
                          trayItemQty, trayStatusID)
                         VALUES
                         (:macID, :itemID, :label, :capacity,
                          :qty, :statusID)"
                    );
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE Tray
                         SET macID = :macID,
                             itemID = :itemID,
                             trayLabel = :label,
                             trayCapacity = :capacity,
                             trayItemQty = :qty,
                             trayStatusID = :statusID
                         WHERE trayID = :id"
                    );
                    $stmt->bindValue(':id', $trayID, PDO::PARAM_INT);
                }

                $stmt->bindValue(':macID', $macID, PDO::PARAM_INT);
                $stmt->bindValue(':itemID', $itemID, PDO::PARAM_INT);
                $stmt->bindValue(':label', $label);
                $stmt->bindValue(':capacity', $capacity, PDO::PARAM_INT);
                $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
                $stmt->bindValue(':statusID', $statusID, PDO::PARAM_INT);
                $stmt->execute();

                $text = $isAdd
                    ? 'Tray added successfully.'
                    : 'Tray updated successfully.';

                header('Location: trays.php?message=' . urlencode($text));
                exit;
            } catch (PDOException $e) {
                $message = 'Unable to save tray. Check that the selected '
                    . 'records exist and that the tray label is not duplicated '
                    . 'for this machine.';
            }
        }
    }

    // Delete tray
    if (isset($_POST['delete'])) {
        try {
            $stmt = $pdo->prepare(
                "DELETE FROM Tray WHERE trayID = :id"
            );
            $stmt->execute(['id' => (int) $_POST['trayID']]);

            header('Location: trays.php?message=' .
                urlencode('Tray deleted successfully.'));
            exit;
        } catch (PDOException $e) {
            $message = 'This tray cannot be deleted because it has '
                . 'movement or vending records.';
        }
    }
}

// Get tray details
$trays = $pdo->query(
    "SELECT Tray.*, Machine.macLoc, Item.itemName,
            Tray_Status.trayStatusName
     FROM Tray
     JOIN Machine ON Tray.macID = Machine.macID
     JOIN Item ON Tray.itemID = Item.itemID
     JOIN Tray_Status
       ON Tray.trayStatusID = Tray_Status.trayStatusID
     ORDER BY Tray.macID, Tray.trayLabel"
)->fetchAll();

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
    <title>Tray Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <h1>Tray Management</h1>
    <p><a href="index.php">Back to Dashboard</a></p>

    <?php if ($message !== ''): ?>
        <p class="message"><?= e($message) ?></p>
    <?php endif; ?>

    <h2><?= $editTray ? 'Update Tray' : 'Add Tray' ?></h2>

    <form method="POST" action="trays.php">
        <?php if ($editTray): ?>
            <input type="hidden" name="trayID"
                value="<?= e($editTray['trayID']) ?>">
        <?php endif; ?>

        <label>Machine</label>
        <select name="macID" required>
            <?php foreach ($machines as $machine): ?>
                <option value="<?= e($machine['macID']) ?>"
                    <?= (string) ($editTray['macID'] ?? '')
                        === (string) $machine['macID']
                        ? 'selected' : '' ?>>
                    <?= e($machine['macLoc']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Item</label>
        <select name="itemID" required>
            <?php foreach ($items as $item): ?>
                <option value="<?= e($item['itemID']) ?>"
                    <?= (string) ($editTray['itemID'] ?? '')
                        === (string) $item['itemID']
                        ? 'selected' : '' ?>>
                    <?= e($item['itemName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Tray Label</label>
        <input type="text" name="trayLabel" maxlength="20"
            value="<?= e($editTray['trayLabel'] ?? '') ?>" required>

        <label>Tray Capacity</label>
        <input type="number" name="trayCapacity" min="1"
            value="<?= e($editTray['trayCapacity'] ?? 10) ?>" required>

        <label>Current Quantity</label>
        <input type="number" name="trayItemQty" min="0"
            value="<?= e($editTray['trayItemQty'] ?? 0) ?>" required>

        <label>Tray Status</label>
        <select name="trayStatusID" required>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status['trayStatusID']) ?>"
                    <?= (string) ($editTray['trayStatusID'] ?? '')
                        === (string) $status['trayStatusID']
                        ? 'selected' : '' ?>>
                    <?= e($status['trayStatusName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($editTray): ?>
            <button type="submit" name="update">Update Tray</button>
            <a href="trays.php">Cancel</a>
        <?php else: ?>
            <button type="submit" name="add">Add Tray</button>
        <?php endif; ?>
    </form>

    <h2>Existing Trays</h2>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Machine</th>
            <th>Label</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($trays as $tray): ?>
            <tr>
                <td><?= e($tray['trayID']) ?></td>
                <td><?= e($tray['macLoc']) ?></td>
                <td><?= e($tray['trayLabel']) ?></td>
                <td><?= e($tray['itemName']) ?></td>
                <td>
                    <?= e($tray['trayItemQty']) ?> /
                    <?= e($tray['trayCapacity']) ?>
                </td>
                <td><?= e($tray['trayStatusName']) ?></td>
                <td>
                    <a href="trays.php?edit=<?= e($tray['trayID']) ?>">
                        Edit
                    </a>

                    <form method="POST" action="trays.php"
                        onsubmit="return confirm('Delete this tray?');">
                        <input type="hidden" name="trayID"
                            value="<?= e($tray['trayID']) ?>">
                        <button type="submit" name="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>
</body>
</html>
