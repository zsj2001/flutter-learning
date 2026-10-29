<?php
session_start();
require 'db.php';

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$editMachine = null;

// Get machine statuses for the dropdown
$statuses = $pdo->query("SELECT * FROM Machine_Status ORDER BY macStatusName")->fetchAll();

// Load machine details when Edit is clicked
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM Machine WHERE macID = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $editMachine = $stmt->fetch();

    if (!$editMachine) {
        $message = 'Machine not found';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add machine
    if (isset($_POST['save'])) {
        $machineID = $_POST['macID'] ?? '';
        $location = trim($_POST['macLoc'] ?? '');
        $statusID = $_POST['macStatusID'] ?? '';

        if ($location === '') {
            $message = 'Please enter a valid machine location';
        } else {
            if($machineID !== '') {
                // update machine
                $stmt = $pdo->prepare("UPDATE Machine SET macLoc = :location, macStatusID = :statusID WHERE macID = :id");
                $stmt->execute([
                    'location' => $location,
                    'statusID' => $statusID,
                    'id' => $machineID
                ]);

                $_SESSION['message'] = 'Machine updated successfully';
        } else {
        // Insert new machine
            $stmt = $pdo->prepare(
                "INSERT INTO Machine (macStatusID, macLoc)
                 VALUES (:statusID, :location)"
            );
            $stmt->execute([
                'statusID' => $statusID,
                'location' => $location
            ]);

            $_SESSION['message'] = 'Machine added successfully';
        }
            header('Location: machines.php');
            exit;
        }
    }

    // Delete machine
    if (isset($_POST['delete'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM Machine WHERE macID = :id");
            $stmt->execute(['id' => $_POST['macID']]);
            $_SESSION['message'] = 'Machine deleted successfully';
            header('Location: machines.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Machine cannot be deleted because it is linked to trays or records';
            header('Location: machines.php');
            exit;
        }
    }
}

// Get all machines and their status names
$machines = $pdo->query(
    "SELECT Machine.*, Machine_Status.macStatusName
     FROM Machine
     JOIN Machine_Status
       ON Machine.macStatusID = Machine_Status.macStatusID
     ORDER BY Machine.macID"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Machine</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1 style="text-align: center;">Machine Management</h1>
    <button type="button" onclick="location.href='admindashboard.php'">Back to Dashboard</button>

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

    <h2><?= $editMachine ? 'Update Machine' : 'Add Machine' ?></h2>

    <form method="POST" action="machines.php">
        <?php if ($editMachine): ?>
            <input type="hidden" name="macID"
                value="<?= $editMachine['macID'] ?>">
        <?php endif; ?>

        <label>Machine location<span class="required-asterisk">*</span></label>
        <input type="text" name="macLoc" maxlength="255"
            value="<?= $editMachine['macLoc'] ?? '' ?>" required>

        <label>Machine status<span class="required-asterisk">*</span></label>
        <select name="macStatusID" required>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= $status['macStatusID'] ?>"
                    <?= (string) ($editMachine['macStatusID'] ?? '') === (string) $status['macStatusID'] ? 'selected' : '' ?>>
                    <?= $status['macStatusName'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="save"><?= $editMachine ? 'Update' : 'Add' ?></button>
        <?php if ($editMachine): ?>
            <button type="button" onclick="location.href='machines.php'">Cancel</button>
        <?php endif; ?>
    </form>

    <h2>Machines</h2>
    <table>
        <thead>
            <tr>
                <th>Location</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($machines as $machine): ?>
            <tr>
                <td><?= $machine['macLoc'] ?></td>
                <td><?= $machine['macStatusName'] ?></td>
                <td>
                    <form method="GET" action="machines.php">
                        <input type="hidden" name="edit"
                            value="<?= $machine['macID'] ?>">
                        <button type="submit" class="btn-edit">Edit</button>
                    </form>

                    <form method="POST" action="machines.php"
                        onsubmit="return confirm('Delete this machine?');">
                        <input type="hidden" name="macID"
                            value="<?= $machine['macID'] ?>">
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
