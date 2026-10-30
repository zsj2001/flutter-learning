<?php
session_start();
require 'db.php';

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$editTray = null;

// Get dropdown data
$machines = $pdo->query("SELECT macID, macLoc FROM Machine ORDER BY macLoc")->fetchAll();

$items = $pdo->query("SELECT itemID, itemName FROM Item ORDER BY itemName")->fetchAll();

$statuses = $pdo->query("SELECT trayStatusID, trayStatusName FROM Tray_Status ORDER BY trayStatusName")->fetchAll();

// tray edit
if (isset($_GET['edit'])) {
    $trayID = $_GET['edit'];

    $stmt = $pdo->prepare("SELECT * FROM Tray WHERE trayID = :id");
    $stmt->execute(['id' => $trayID]);
    $editTray = $stmt->fetch();

    if (!$editTray) {
        $message = 'Tray not found';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add or create new tray
    if (isset($_POST['save'])) {
        $trayID = $_POST['trayID'] ?? '';
        $macID = $_POST['macID'] ?? '';
        $itemID = $_POST['itemID'] ?? '';
        $label = trim($_POST['trayLabel'] ?? '');
        $capacity = filter_var(
            $_POST['trayCapacity'] ?? null,
            FILTER_VALIDATE_INT
        );
        $qty = filter_var(
            $_POST['trayItemQty'] ?? null,
            FILTER_VALIDATE_INT
        );
        $statusID = $_POST['trayStatusID'] ?? '';

        // Validate input
        if ($label === '' || $capacity < 1 || $qty < 0 || $qty > $capacity || 
            !is_numeric($macID) || $macID < 1 || !is_numeric($itemID) || $itemID < 1 || 
            !is_numeric($statusID) || $statusID < 1
        ) {
            $message = 'Please check the tray details and quantities';
        } else {
            try {
                if ($trayID !== '') {
                    // Update existing tray
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

                    $stmt->execute([
                        'macID' => $macID,
                        'itemID' => $itemID,
                        'label' => $label,
                        'capacity' => $capacity,
                        'qty' => $qty,
                        'statusID' => $statusID,
                        'id' => $trayID
                    ]);
                    $successMessage = 'Tray updated successfully';
                } else {
                    // Insert new tray
                    $stmt = $pdo->prepare(
                        "INSERT INTO Tray(macID, itemID, trayLabel, trayCapacity, trayItemQty, trayStatusID)
                        VALUES(:macID, :itemID, :label, :capacity, :qty, :statusID)"
                    );

                    $stmt->execute([
                        'macID' => $macID,
                        'itemID' => $itemID,
                        'label' => $label,
                        'capacity' => $capacity,
                        'qty' => $qty,
                        'statusID' => $statusID
                    ]);
                    $successMessage = 'Tray added successfully';
                }

                $_SESSION['message'] = $successMessage;
                header('Location: trays.php');
                exit;
            } catch (PDOException $e) {
                $message = 'Unable to save tray. Check that the selected records exist and tray label is not duplicated for this machine';
            }
        }
    }

    // Delete tray
    if (isset($_POST['delete'])) {
        $trayID = $_POST['trayID'];
        try {
            $stmt = $pdo->prepare("DELETE FROM Tray WHERE trayID = :id");
            $stmt->execute(['id' => $trayID]);

            $_SESSION['message'] = 'Tray deleted successfully';
            header('Location: trays.php');
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
     JOIN Tray_Status ON Tray.trayStatusID = Tray_Status.trayStatusID
     ORDER BY Tray.trayID"
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
            const message = document.getElementById('status-message');

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
        <input type="text" name="trayLabel"
            value="<?= $editTray['trayLabel'] ?? '' ?>" required>

        <label>Tray capacity<span class="required-asterisk">*</span></label>
        <input type="number" name="trayCapacity" min="1"
            value="<?= $editTray['trayCapacity'] ?? 10 ?>" required>

        <label>Item quantity<span class="required-asterisk">*</span></label>
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

        <button type="submit" name="save"><?= $editTray ? 'Update' : 'Add' ?></button>
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
            <th>Quantity / Capacity</th>
            <th>Tray status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($trays as $tray): ?>
            <tr>
                <td><?= $tray['macLoc'] ?></td>
                <td><?= $tray['trayLabel'] ?></td>
                <td><?= $tray['itemName'] ?></td>
                <td><?= $tray['trayItemQty'] ?> / <?= $tray['trayCapacity'] ?></td>
                <td><?= $tray['trayStatusName'] ?></td>
                <td>
                    <form method="GET" action="trays.php">
                        <input type="hidden" name="edit"
                            value="<?= $tray['trayID'] ?>">
                        <button type="submit" class="btn-edit">Edit</button>
                    </form>

                    <form method="POST" action="trays.php"
                        onsubmit="return confirm('Delete this tray?');">
                        <input type="hidden" name="trayID"
                            value="<?= $tray['trayID'] ?>">
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
