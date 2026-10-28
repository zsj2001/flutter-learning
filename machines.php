
<?php
require 'db.php';

$message = $_GET['message'] ?? '';
$editMachine = null;

// Get machine statuses for the dropdown
$statuses = $pdo->query(
    "SELECT * FROM Machine_Status ORDER BY macStatusName"
)->fetchAll();

// Load machine details when Edit is clicked
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM Machine WHERE macID = :id"
    );
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editMachine = $stmt->fetch();

    if (!$editMachine) {
        $message = 'Machine not found.';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add machine
    if (isset($_POST['add'])) {
        $location = trim($_POST['macLoc']);
        $statusID = (int) $_POST['macStatusID'];

        if ($location === '' || strlen($location) > 255) {
            $message = 'Please enter a valid machine location.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO Machine (macStatusID, macLoc)
                 VALUES (:statusID, :location)"
            );
            $stmt->execute([
                'statusID' => $statusID,
                'location' => $location
            ]);

            header('Location: machines.php?message=' .
                urlencode('Machine added successfully.'));
            exit;
        }
    }

    // Update machine
    if (isset($_POST['update'])) {
        $machineID = (int) $_POST['macID'];
        $location = trim($_POST['macLoc']);
        $statusID = (int) $_POST['macStatusID'];

        if ($location === '' || strlen($location) > 255) {
            $message = 'Please enter a valid machine location.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE Machine
                 SET macLoc = :location,
                     macStatusID = :statusID
                 WHERE macID = :id"
            );
            $stmt->execute([
                'location' => $location,
                'statusID' => $statusID,
                'id' => $machineID
            ]);

            header('Location: machines.php?message=' .
                urlencode('Machine updated successfully.'));
            exit;
        }
    }

    // Delete machine
    if (isset($_POST['delete'])) {
        try {
            $stmt = $pdo->prepare(
                "DELETE FROM Machine WHERE macID = :id"
            );
            $stmt->execute(['id' => (int) $_POST['macID']]);

            header('Location: machines.php?message=' .
                urlencode('Machine deleted successfully.'));
            exit;
        } catch (PDOException $e) {
            $message = 'Machine cannot be deleted because it is '
                . 'linked to trays or records.';
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
    <title>Machine Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <h1>Machine Management</h1>
    <p><a href="index.php">Back to Dashboard</a></p>

    <?php if ($message !== ''): ?>
        <p class="message"><?= e($message) ?></p>
    <?php endif; ?>

    <h2><?= $editMachine ? 'Update Machine' : 'Add Machine' ?></h2>

    <form method="POST" action="machines.php">
        <?php if ($editMachine): ?>
            <input type="hidden" name="macID"
                value="<?= e($editMachine['macID']) ?>">
        <?php endif; ?>

        <label>Machine Location</label>
        <input type="text" name="macLoc" maxlength="255"
            value="<?= e($editMachine['macLoc'] ?? '') ?>" required>

        <label>Machine Status</label>
        <select name="macStatusID" required>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= e($status['macStatusID']) ?>"
                    <?= (string) ($editMachine['macStatusID'] ?? '')
                        === (string) $status['macStatusID']
                        ? 'selected' : '' ?>>
                    <?= e($status['macStatusName']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($editMachine): ?>
            <button type="submit" name="update">Update Machine</button>
            <a href="machines.php">Cancel</a>
        <?php else: ?>
            <button type="submit" name="add">Add Machine</button>
        <?php endif; ?>
    </form>

    <h2>Existing Machines</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Location</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($machines as $machine): ?>
            <tr>
                <td><?= e($machine['macID']) ?></td>
                <td><?= e($machine['macLoc']) ?></td>
                <td><?= e($machine['macStatusName']) ?></td>
                <td>
                    <a href="machines.php?edit=<?= e($machine['macID']) ?>">
                        Edit
                    </a>

                    <form method="POST" action="machines.php"
                        onsubmit="return confirm('Delete this machine?');">
                        <input type="hidden" name="macID"
                            value="<?= e($machine['macID']) ?>">
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
