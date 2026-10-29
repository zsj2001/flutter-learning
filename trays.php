
<?php
session_start();
require 'db.php';

// Get and clear the flash message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$editTray = null;

// Escape output before displaying it in HTML
function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Get dropdown data
$machines = $pdo->query(
    "SELECT macID, macLoc FROM Machine ORDER BY macLoc"
)->fetchAll();

$items = $pdo->query(
    "SELECT itemID, itemName FROM Item ORDER BY itemName"
)->fetchAll();

$statuses = $pdo->query(
    "SELECT trayStatusID, trayStatusName
     FROM Tray_Status
     ORDER BY trayStatusName"
)->fetchAll();

// Check whether the admin wants to edit a tray
if (isset($_GET['edit'])) {
    $trayID = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($trayID !== false && $trayID > 0) {
        $stmt = $pdo->prepare(
            "SELECT * FROM Tray WHERE trayID = :id"
        );
        $stmt->execute(['id' => $trayID]);
        $editTray = $stmt->fetch();
    }

    if (!$editTray) {
        $message = 'Tray not found.';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add or update a tray
    if (isset($_POST['save'])) {
        $trayID = $_POST['trayID'] ?? '';
        $macID = filter_var(
            $_POST['macID'] ?? null,
            FILTER_VALIDATE_INT
        );
        $itemID = filter_var(
            $_POST['itemID'] ?? null,
            FILTER_VALIDATE_INT
        );
        $label = trim($_POST['trayLabel'] ?? '');
        $capacity = filter_var(
            $_POST['trayCapacity'] ?? null,
            FILTER_VALIDATE_INT
        );
        $qty = filter_var(
            $_POST['trayItemQty'] ?? null,
            FILTER_VALIDATE_INT
        );
        $statusID = filter_var(
            $_POST['trayStatusID'] ?? null,
            FILTER_VALIDATE_INT
        );

        // Validate input
        if (
            $label === '' ||
            mb_strlen($label) > 20 ||
            $macID === false || $macID === null || $macID < 1 ||
            $itemID === false || $itemID === null || $itemID < 1 ||
            $capacity === false || $capacity === null || $capacity < 1 ||
            $qty === false || $qty === null ||
            $qty < 0 || $qty > $capacity ||
            $statusID === false || $statusID === null || $statusID < 1 ||
            ($trayID !== '' &&
                (filter_var($trayID, FILTER_VALIDATE_INT) === false ||
                 (int) $trayID < 1))
        ) {
            $message = 'Please enter valid tray details and quantities.';
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
                        'id' => (int) $trayID
                    ]);

                    $successMessage = 'Tray updated successfully.';
                } else {
                    // Insert new tray
                    $stmt = $pdo->prepare(
                        "INSERT INTO Tray
                            (macID, itemID, trayLabel, trayCapacity,
                             trayItemQty, trayStatusID)
                         VALUES
                            (:macID, :itemID, :label, :capacity,
                             :qty, :statusID)"
                    );

                    $stmt->execute([
                        'macID' => $macID,
                        'itemID' => $itemID,
                        'label' => $label,
                        'capacity' => $capacity,
                        'qty' => $qty,
                        'statusID' => $statusID
                    ]);

                    $successMessage = 'Tray added successfully.';
                }

                $_SESSION['message'] = $successMessage;
                header('Location: trays.php');
                exit;

            } catch (PDOException $e) {
                $message = 'Unable to save tray. Check that the selected '
                    . 'machine, item and status exist, and that the tray '
                    . 'label is not duplicated for this machine.';
            }
        }
    }

    // Delete a tray
    if (isset($_POST['delete'])) {
        $trayID = filter_var(
            $_POST['trayID'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($trayID === false || $trayID === null || $trayID < 1) {
            $message = 'Invalid tray ID.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "DELETE FROM Tray WHERE trayID = :id"
                );
                $stmt->execute(['id' => $trayID]);

                $_SESSION['message'] = 'Tray deleted successfully.';
                header('Location: trays.php');
                exit;

            } catch (PDOException $e) {
                // The tray may still be referenced by other records
                $message = 'This tray cannot be deleted because it is '
                    . 'still linked to movement or vending records.';
            }
        }
    }
}

// Get tray details to display in the table
$stmt = $pdo->query(
    "SELECT Tray.*,
            Machine.macLoc,
            Item.itemName,
            Tray_Status.trayStatusName
     FROM Tray
     JOIN Machine ON Tray.macID = Machine.macID
     JOIN Item ON Tray.itemID = Item.itemID
     JOIN Tray_Status
       ON Tray.trayStatusID = Tray_Status.trayStatusID
     ORDER BY Tray.macID, Tray.trayLabel"
);
$trays = $stmt->fetchAll();
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

    <button type="button"
        onclick="location.href='admindashboard.php'">
        Back to Dashboard
    </button>

    <?php if ($message !== ''): ?>
        <p class="message" id="statusMessage"><?= e($message) ?></p>

        <script>
        setTimeout(function() {
            const message = document.getElementById('statusMessage');

            if (message) {
                message.style.transition = 'opacity 0.5s ease';
                message.style.opacity = '0';

                setTimeout(function() {
                    message.remove();
                }, 500);
            }
        }, 5000);
        </script>
    <?php endif; ?>

    <h2><?= $editTray ? 'Update Tray' : 'Add New Tray' ?></h2>

    <form method="POST" action="trays.php">

        <?php if ($editTray): ?>
            <input type="hidden" name="trayID"
                value="<?= e($editTray['trayID']) ?>">
        <?php endif; ?>

        <label>
            Machine<span class="required-asterisk">*</span>
        </label>
        <select name="macID" required>
            <?php foreach ($machines as $machine): ?>
                <option value="<?= e($machine['macID']) ?>"
                    <?= (string) ($editTray['macID'] ?? '') ===
                        (string) $machine['macID'] ? 'selected' : '' ?>>
                    <?= e($machine['macLoc']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>
            Item<span class="required-asterisk">*</span>
        </label>
        <select name="itemID" required>
            <?php foreach ($items as $item): ?>
                <option value="<?= e($item['itemID']) ?>"
                    <?= (string) ($editTray['itemID'] ?? '') ===
                        (string) $item['itemID'] ? 'selected' : '' ?>>
                    <?= e($item['itemName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>
            Tray label<span class="required-asterisk">*</span>
        </label>
        <input type="text" name="trayLabel" maxlength="20"
            value="<?= e($editTray['trayLabel'] ?? '') ?>"
            required>

        <label>
            Tray capacity<span class="required-asterisk">*</span>
        </label>
        <input type="number" name="trayCapacity" min="1"
            value="<?= e($editTray['trayCapacity'] ?? 10) ?>"
            required>

        <label>
            Current quantity<span class="required-asterisk">*</span>
        </label>
        <input type="number" name="trayItemQty" min="0"
            max="<?= e($editTray['trayCapacity'] ?? '') ?>"
            value="<?= e($editTray['trayItemQty'] ?? 0) ?>"
            required>

        <label>
            Tray status<span class="required-asterisk">*</span>
        </label>
        <select name="trayStatusID" required>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status['trayStatusID']) ?>"
                    <?= (string) ($editTray['trayStatusID'] ?? '') ===
                        (string) $status['trayStatusID'] ? 'selected' : '' ?>>
                    <?= e($status['trayStatusName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="save">
            <?= $editTray ? 'Update' : 'Add' ?>
        </button>

        <?php if ($editTray): ?>
            <button type="button"
                onclick="location.href='trays.php'">
                Cancel
            </button>
        <?php endif; ?>

    </form>

    <h2>Trays</h2>

    <table>
        <thead>
            <tr>
                <th>Machine</th>
                <th>Tray Label</th>
                <th>Item</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($trays as $tray): ?>
                <tr>
                    <td><?= e($tray['macLoc']) ?></td>
                    <td><?= e($tray['trayLabel']) ?></td>
                    <td><?= e($tray['itemName']) ?></td>
                    <td>
                        <?= e($tray['trayItemQty']) ?> /
                        <?= e($tray['trayCapacity']) ?>
                    </td>
                    <td><?= e($tray['trayStatusName']) ?></td>
                    <td>
                        <form method="GET" action="trays.php">
                            <input type="hidden" name="edit"
                                value="<?= e($tray['trayID']) ?>">
                            <button type="submit" class="btn-edit">
                                Edit
                            </button>
                        </form>

                        <form method="POST" action="trays.php"
                            onsubmit="return confirm('Delete this tray?');">
                            <input type="hidden" name="trayID"
                                value="<?= e($tray['trayID']) ?>">
                            <button type="submit" class="btn-delete"
                                name="delete">
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
