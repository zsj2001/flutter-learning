
<?php
require 'db.php';

// Only these tables and columns can be used by this page.
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

$type = $_GET['type'] ?? 'machine';

if (!isset($statusTables[$type])) {
    exit('Invalid status category.');
}

$config = $statusTables[$type];
$message = $_GET['message'] ?? '';
$editStatus = null;

// Load the status being edited
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM {$config['table']}
         WHERE {$config['id']} = :id"
    );
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editStatus = $stmt->fetch();

    if (!$editStatus) {
        $message = 'Status not found.';
    }
}

// Add or update a status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = strtoupper(trim($_POST['statusName'] ?? ''));
    $id = (int) ($_POST['statusID'] ?? 0);

    if ($name === '' || strlen($name) > 100) {
        $message = 'Please enter a status name.';
    } else {
        try {
            if (isset($_POST['add'])) {
                $stmt = $pdo->prepare(
                    "INSERT INTO {$config['table']}
                     ({$config['name']}) VALUES (:name)"
                );
                $stmt->execute(['name' => $name]);
                $text = 'Status added successfully.';
            } elseif (isset($_POST['update'])) {
                $stmt = $pdo->prepare(
                    "UPDATE {$config['table']}
                     SET {$config['name']} = :name
                     WHERE {$config['id']} = :id"
                );
                $stmt->execute([
                    'name' => $name,
                    'id' => $id
                ]);
                $text = 'Status updated successfully.';
            } else {
                $text = '';
            }

            if ($text !== '') {
                header('Location: statuses.php?type=' .
                    urlencode($type) . '&message=' . urlencode($text));
                exit;
            }
        } catch (PDOException $e) {
            $message = 'Unable to save the status. It may already exist.';
        }
    }
}

// Display statuses
$stmt = $pdo->query(
    "SELECT * FROM {$config['table']}
     ORDER BY {$config['id']}"
);
$statuses = $stmt->fetchAll();

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
    <title>Status Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

    <h1>Status Management</h1>
    <p><a href="index.php">Back to Dashboard</a></p>

    <label>Select Status Category</label>
    <select onchange="location.href='statuses.php?type='+this.value">
        <?php foreach ($statusTables as $key => $value): ?>
            <option value="<?= e($key) ?>"
                <?= $type === $key ? 'selected' : '' ?>>
                <?= e($value['title']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if ($message !== ''): ?>
        <p class="message"><?= e($message) ?></p>
    <?php endif; ?>

    <h2><?= $editStatus ? 'Edit Status' : 'Add Status' ?></h2>

    <form method="POST"
        action="statuses.php?type=<?= e($type) ?>">

        <?php if ($editStatus): ?>
            <input type="hidden" name="statusID"
                value="<?= e($editStatus[$config['id']]) ?>">
        <?php endif; ?>

        <label>Status Name</label>
        <input type="text" name="statusName" maxlength="100"
            value="<?= e($editStatus[$config['name']] ?? '') ?>"
            required>

        <?php if ($editStatus): ?>
            <button type="submit" name="update">Update Status</button>
            <a href="statuses.php?type=<?= e($type) ?>">Cancel</a>
        <?php else: ?>
            <button type="submit" name="add">Add Status</button>
        <?php endif; ?>
    </form>

    <h2>Existing Statuses</h2>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Status Name</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($statuses as $status): ?>
            <tr>
                <td><?= e($status[$config['id']]) ?></td>
                <td><?= e($status[$config['name']]) ?></td>
                <td>
                    <a href="statuses.php?type=<?= e($type) ?>&edit=<?= e($status[$config['id']]) ?>">
                        Edit
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>
</body>
</html>
