<?php
session_start();
require 'db.php';

// Only these tables and columns can be used by this page
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
$type = $_GET['type'] ?? 'machine';

if (!isset($statusTables[$type])) {
    exit('Invalid status category.');
}

$config = $statusTables[$type];

// Get and clear the flash message
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$editStatus = null;

// Escape output before displaying it in HTML
if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

// Check whether the admin wants to edit a status
if (isset($_GET['edit'])) {
    $statusID = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    );

    if ($statusID !== false && $statusID > 0) {
        $stmt = $pdo->prepare(
            "SELECT * FROM {$config['table']}
             WHERE {$config['id']} = :id"
        );

        $stmt->execute(['id' => $statusID]);
        $editStatus = $stmt->fetch();
    }

    if (!$editStatus) {
        $message = 'Status not found.';
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add or update a status
    if (isset($_POST['save'])) {
        $statusID = $_POST['statusID'] ?? '';
        $name = strtoupper(trim($_POST['statusName'] ?? ''));

        // Validate status name
        if ($name === '' || strlen($name) > 100) {
            $message = 'Please enter a valid status name.';
        } elseif (
            $statusID !== '' &&
            (
                filter_var($statusID, FILTER_VALIDATE_INT) === false ||
                (int) $statusID < 1
            )
        ) {
            $message = 'Invalid status ID.';
        } else {
            try {
                if ($statusID !== '') {
                    // Update existing status
                    $stmt = $pdo->prepare(
                        "UPDATE {$config['table']}
                         SET {$config['name']} = :name
                         WHERE {$config['id']} = :id"
                    );

                    $stmt->execute([
                        'name' => $name,
                        'id' => (int) $statusID
                    ]);

                    $successMessage = 'Status updated successfully.';
                } else {
                    // Insert new status
                    $stmt = $pdo->prepare(
                        "INSERT INTO {$config['table']}
                            ({$config['name']})
                         VALUES (:name)"
                    );

                    $stmt->execute([
                        'name' => $name
                    ]);

                    $successMessage = 'Status added successfully.';
                }

                // Store the success message and redirect
                $_SESSION['message'] = $successMessage;

                header(
                    'Location: statuses.php?type=' .
                    urlencode($type)
                );
                exit;

            } catch (PDOException $e) {
                // A duplicate status name may violate a unique constraint
                $message = 'Unable to save the status. '
                    . 'It may already exist or be linked to a database constraint.';
            }
        }
    }
}

// Get statuses to display in the table
$stmt = $pdo->query(
    "SELECT * FROM {$config['table']}
     ORDER BY {$config['id']}"
);

$statuses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1">
    <title>Status Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <h1 style="text-align: center;">Status Management</h1>

    <button type="button"
        onclick="location.href='admindashboard.php'">
        Back to Dashboard
    </button>

    <label>Select Status Category</label>
    <select
        onchange="location.href='statuses.php?type='+this.value">

        <?php foreach ($statusTables as $key => $value): ?>
            <option value="<?= e($key) ?>"
                <?= $type === $key ? 'selected' : '' ?>>
                <?= e($value['title']) ?>
            </option>
        <?php endforeach; ?>

    </select>

    <?php if ($message !== ''): ?>
        <p class="message" id="statusMessage">
            <?= e($message) ?>
        </p>

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

    <h2>
        <?= $editStatus ? 'Update Status' : 'Add New Status' ?>
    </h2>

    <form method="POST"
        action="statuses.php?type=<?= e($type) ?>">

        <?php if ($editStatus): ?>
            <input type="hidden" name="statusID"
                value="<?= e($editStatus[$config['id']]) ?>">
        <?php endif; ?>

        <label>
            Status Name<span class="required-asterisk">*</span>
        </label>

        <input type="text" name="statusName"
            maxlength="100"
            value="<?= e($editStatus[$config['name']] ?? '') ?>"
            required>

        <button type="submit" name="save">
            <?= $editStatus ? 'Update' : 'Add' ?>
        </button>

        <?php if ($editStatus): ?>
            <button type="button"
                onclick="location.href='statuses.php?type=<?= e($type) ?>'">
                Cancel
            </button>
        <?php endif; ?>

    </form>

    <h2><?= e($config['title']) ?> List</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Status Name</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($statuses as $status): ?>
                <tr>
                    <td><?= e($status[$config['id']]) ?></td>
                    <td><?= e($status[$config['name']]) ?></td>
                    <td>
                        <form method="GET" action="statuses.php">
                            <input type="hidden" name="type"
                                value="<?= e($type) ?>">

                            <input type="hidden" name="edit"
                                value="<?= e($status[$config['id']]) ?>">

                            <button type="submit" class="btn-edit">
                                Edit
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
