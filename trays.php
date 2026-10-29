<?php
require 'db.php';

$message = $_GET['message'] ?? '';
$editTray = null;

// Get dropdown data
$machines = $pdo->query("SELECT macID, macLoc FROM Machine ORDER BY macLoc")->fetchAll();

$items = $pdo->query("SELECT itemID, itemName FROM Item ORDER BY itemName")->fetchAll();

$statuses = $pdo->query("SELECT trayStatusID, trayStatusName FROM Tray_Status ORDER BY trayStatusName")->fetchAll();

// Load tray for editing
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM Tray WHERE trayID = :id");
    $stmt->execute(['id' => $_GET['edit']]);
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
        $trayID = ($_POST['trayID'] ?? 0);
        $macID = ($_POST['macID'] ?? 0);
        $itemID = ($_POST['itemID'] ?? 0);
        $label = trim($_POST['trayLabel'] ?? '');
        $capacity = filter_var(
            $_POST['trayCapacity'] ?? null,
            FILTER_VALIDATE_INT
        );
        $qty = filter_var(
            $_POST['trayItemQty'] ?? null,
            FILTER_VALIDATE_INT
        );
        $statusID = ($_POST['trayStatusID'] ?? 0);

        if ($label === '' || $capacity === false || $capacity === null ||
            $capacity < 1 || $qty === false || $qty === null ||
            $qty < 0 || $qty > $capacity || $macID < 1 || $itemID < 1 || $statusID < 1
        ) {
            $message = 'Please check the tray details and quantities';
        } else {
            try {
                if ($isAdd) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO Tray(macID, itemID, trayLabel, trayCapacity, trayItemQty, trayStatusID)
                         VALUES(:macID, :itemID, :label, :capacity, :qty, :statusID)"
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

                $text = $isAdd ? 'Tray added successfully' : 'Tray updated successfully';

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
            $stmt = $pdo->prepare("DELETE FROM Tray WHERE trayID = :id");
            $stmt->execute(['id' => $_POST['trayID']]);

            header('Location: trays.php?message=' .
                urlencode('Tray deleted successfully.'));
            exit;
        } catch (PDOException $e) {
            $message = 'This tray cannot be deleted because it has movement or vending records';
        }
    }
}

// Get tray details
$trays = $pdo->query(
    "SELECT Tray.*, Machine.macLoc, Item.itemName, Tray_Status.trayStatusName
     FROM Tray
     JOIN Machine ON Tray.macID = Machine.macID
     JOIN Item ON Tray.itemID = Item.itemID
     JOIN Tray_Status
       ON Tray.trayStatusID = Tray_Status.trayStatusID
     ORDER BY Tray.macID, Tray.trayLabel"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tray</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1 style="text-align: center;">Tray Management</h1>
    <button type="button" onclick="location.href='admindashboard.php'">Back to Dashboard</button>

    <?php if ($message !== ''): ?>
        <p class="message" id="status-message"><?= $message ?></p>

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

    <h2><?= $editTray ? 'Update Tray' : 'Add Tray' ?></h2>

    <form method="POST" action="trays.php">
        <?php if ($editTray): ?>
            <input type="hidden" name="trayID"
                value="<?= $editTray['trayID'] ?>">
        <?php endif; ?>

        <label>Machine<span class="required-asterisk">*</span></label>
        <select name="macID" required>
            <?php foreach ($machines as $machine): ?>
                <option value="<?= $machine['macID'] ?>"
                    <?= (string) ($editTray['macID'] ?? '') === (string) $machine['macID'] ? 'selected' : '' ?>>
                    <?= $machine['macLoc'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Item<span class="required-asterisk">*</span></label>
        <select name="itemID" required>
            <?php foreach ($items as $item): ?>
                <option value="<?= $item['itemID'] ?>"
                    <?= (string) ($editTray['itemID'] ?? '') === (string) $item['itemID'] ? 'selected' : '' ?>>
                    <?= $item['itemName'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Tray label<span class="required-asterisk">*</span></label>
        <input type="text" name="trayLabel" maxlength="20"
            value="<?= $editTray['trayLabel'] ?? '' ?>" required>

        <label>Tray capacity<span class="required-asterisk">*</span></label>
        <input type="number" name="trayCapacity" min="1"
            value="<?= $editTray['trayCapacity'] ?? 10 ?>" required>

        <label>Current quantity<span class="required-asterisk">*</span></label>
        <input type="number" name="trayItemQty" min="0"
            value="<?= $editTray['trayItemQty'] ?? 0 ?>" required>

        <label>Tray status<span class="required-asterisk">*</span></label>
        <select name="trayStatusID" required>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= $status['trayStatusID'] ?>"
                    <?= (string) ($editTray['trayStatusID'] ?? '') === (string) $status['trayStatusID'] ? 'selected' : '' ?>>
                    <?= $status['trayStatusName'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="update"><?= $editTray ? 'Update' : 'Add' ?></button>
        <?php if ($editTray): ?> 
            <button type="button" onclick="location.href='trays.php'">Cancel</button>
        <?php endif; ?>
    </form>

    <h2>Trays</h2>
    <table>
        <thead>
        <tr>
            <th>Machine</th>
            <th>Tray label</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($trays as $tray): ?>
            <tr>
                <td><?= $tray['macLoc'] ?></td>
                <td><?= $tray['trayLabel'] ?></td>
                <td><?= $tray['itemName'] ?></td>
                <td>
                    <?= $tray['trayItemQty'] ?> /
                    <?= $tray['trayCapacity'] ?>
                </td>
                <td><?= $tray['trayStatusName'] ?></td>
                <td>
                    <form method="GET" action="trays.php">
                        <input type="hidden" name="trayID"
                            value="<?= $tray['trayID'] ?>">
                        <button type="submit" class="btn-edit">Edit</button>
                    </form>

                    <form method="POST" action="trays.php"
                        onsubmit="return confirm('Delete this tray?');">
                        <input type="hidden" name="trayID"
                            value="<?= e($tray['trayID']) ?>">
                        <button type="submit" class="btn-delete" name="delete">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
