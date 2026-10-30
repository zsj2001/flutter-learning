<?php
session_start();
require 'db.php';
include_once 'admindashboard.php';

$statusTables = [
    'machine' => [
        'table' => 'Machine_Status',
        'id' => 'macStatusID',
        'name' => 'macStatusName',
        'title' => 'Machine Status'
    ],
    'tray' => [
        'table' => 'Tray_Status',
        'id' => 'trayStatusID',
        'name' => 'trayStatusName',
        'title' => 'Tray Status'
    ],
    'item' => [
        'table' => 'Item_Status',
        'id' => 'itemStatusID',
        'name' => 'itemStatusName',
        'title' => 'Item Status'
    ],
    'vend' => [
        'table' => 'Vend_Record_Status',
        'id' => 'vrStatusID',
        'name' => 'vrStatusName',
        'title' => 'Vending Record Status'
    ]
];

// Get the selected status category
$statusTableType = $_GET['type'] ?? '';

if (!isset($statusTables[$statusTableType])) {
    exit('Invalid status category');
}

$selectedStatusTableConfig = $statusTables[$statusTableType];

// Get and clear the flash message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$editStatus = null;

// Check whether the admin wants to edit a status
if (isset($_GET['edit'])) {
    $statusID = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    );

    if ($statusID !== false && $statusID > 0) {
        $stmt = $pdo->prepare(
            "SELECT * FROM {$selectedStatusTableConfig['table']}
             WHERE {$selectedStatusTableConfig['id']} = :id"
        );

        $stmt->execute(['id' => $statusID]);
        $editStatus = $stmt->fetch();
    }

    if (!$editStatus) {
        $message = 'Status not found';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add or update a status
    if (isset($_POST['save'])) {
        $statusID = $_POST['statusID'] ?? '';
        $name = strtoupper(trim($_POST['statusName'] ?? ''));

        // Validate status name
        if ($name === '') {
            $message = 'Please enter valid status name';
        } elseif ($statusID !== '' && (filter_var($statusID, FILTER_VALIDATE_INT) === false || $statusID < 1)
        ) {
            $message = 'Invalid status ID';
        } else {
            try {
                if ($statusID !== '') {
                    // Update existing status
                    $stmt = $pdo->prepare(
                        "UPDATE {$selectedStatusTableConfig['table']}
                         SET {$selectedStatusTableConfig['name']} = :name
                         WHERE {$selectedStatusTableConfig['id']} = :id"
                    );

                    $stmt->execute([
                        'name' => $name,
                        'id' => $statusID
                    ]);

                    $successMessage = 'Status updated successfully';
                } else {
                    // Insert new status
                    $stmt = $pdo->prepare(
                        "INSERT INTO {$selectedStatusTableConfig['table']}
                            ({$selectedStatusTableConfig['name']})
                         VALUES (:name)"
                    );

                    $stmt->execute(['name' => $name]);

                    $successMessage = 'Status added successfully';
                }

                // Store the success message and redirect
                $_SESSION['message'] = $successMessage;

                header('Location: statuses.php?type=' . urlencode($statusTableType));
                exit;
            } catch (PDOException $e) {
                // A duplicate status name may violate a unique constraint
                $message = 'Unable to save the status. It may already exist or be linked to a database constraint';
            }
        }
    }

    // Delete status
    if (isset($_POST['delete'])) {
        $statusID = $_POST['statusID'];

        try {
            $stmt = $pdo->prepare(
                "DELETE FROM {$selectedStatusTableConfig['table']} WHERE {$selectedStatusTableConfig['id']} = :id"
            );
            $stmt->execute(['id' => $statusID]);

            $_SESSION['message'] = 'Status deleted successfully';
            header('Location: statuses.php?type=' . urlencode($statusTableType));
            exit;
        } catch (PDOException $e) {
            $message = 'Status cannot be deleted because it is still linked to other records';
        }
    }
}

// Get statuses to display in the table
$stmt = $pdo->query(
    "SELECT * FROM {$selectedStatusTableConfig['table']}
     ORDER BY {$selectedStatusTableConfig['id']}"
);

$statuses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Status</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1 style="text-align: center;">Statuses Management</h1>
    <!--button type="button" onclick="location.href='admindashboard.html'">Back to Dashboard</button-->

    <label>Select status category<span class="required-asterisk">*</span></label>
    <select onchange="location.href='statuses.php?type='+this.value">
        <?php foreach ($statusTables as $key => $value): ?>
            <option value="<?= $key ?>"
                <?= $statusTableType === $key ? 'selected' : '' ?>>
                <?= $value['title'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($message !== ''): ?>
        <p class="message" id="statusMessage"><?= $message ?></p>

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

    <h2><?= $editStatus ? 'Update Status' : 'Add New Status' ?></h2>

    <form method="POST" action="statuses.php?type=<?= $statusTableType ?>">
        <?php if ($editStatus): ?>
            <input type="hidden" name="statusID"
                value="<?= $editStatus[$selectedStatusTableConfig['id']] ?>">
        <?php endif; ?>

        <label>Status name<span class="required-asterisk">*</span></label>
        <input type="text" name="statusName"
            value="<?= $editStatus[$selectedStatusTableConfig['name']] ?? '' ?>"
            required>

        <button type="submit" name="save"><?= $editStatus ? 'Update' : 'Add' ?></button>

        <?php if ($editStatus): ?>
            <button type="button" onclick="location.href='statuses.php?type=<?= $statusTableType ?>'">Cancel</button>
        <?php endif; ?>
    </form>

    <h2><?= $selectedStatusTableConfig['title'] ?> List</h2>
    <table>
        <thead>
            <tr>
                <th>Status Name</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($statuses as $status): ?>
                <tr>
                    <td><?= $status[$selectedStatusTableConfig['name']] ?></td>
                    <td>
                        <form method="GET" action="statuses.php">
                            <!-- which status table (item/machine/statuses) is editing -->
                            <input type="hidden" name="type"
                                value="<?= $statusTableType ?>">

                            <!-- particular statusID wanted to edit (e.g.: edit=1)-->
                            <input type="hidden" name="edit"
                                value="<?= $status[$selectedStatusTableConfig['id']] ?>">

                            <button type="submit" class="btn-edit">Edit</button>
                        </form>

                        <form method="POST" action="statuses.php?type=<?= urlencode($statusTableType) ?>"
                            onsubmit="return confirm('Delete this status?');">
                            <input type="hidden" name="statusID"
                                value="<?= $status[$selectedStatusTableConfig['id']] ?>">
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
