
<?php
require 'db.php';

$machineCount = $pdo->query(
    "SELECT COUNT(*) FROM Machine"
)->fetchColumn();

$itemCount = $pdo->query(
    "SELECT COUNT(*) FROM Item"
)->fetchColumn();

$trayCount = $pdo->query(
    "SELECT COUNT(*) FROM Tray"
)->fetchColumn();

$vendCount = $pdo->query(
    "SELECT COUNT(*) FROM Vend_Record"
)->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vending Machine Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h1>Vending Machine Administration</h1>
    <p>Manage the vending machine system from this page.</p>

    <h2>System Summary</h2>

    <table>
        <tr>
            <th>Total Machines</th>
            <td><?= (int) $machineCount ?></td>
        </tr>
        <tr>
            <th>Total Items</th>
            <td><?= (int) $itemCount ?></td>
        </tr>
        <tr>
            <th>Total Trays</th>
            <td><?= (int) $trayCount ?></td>
        </tr>
        <tr>
            <th>Total Vending Records</th>
            <td><?= (int) $vendCount ?></td>
        </tr>
    </table>

    <h2>Management</h2>

    <ul>
        <li><a href="items.php">Manage Items</a></li>
        <li><a href="machines.php">Manage Machines</a></li>
        <li><a href="trays.php">Manage Trays</a></li>
        <li><a href="statuses.php?type=machine">
        Manage Machine Status
    </a></li>

    <li><a href="statuses.php?type=item">
        Manage Item Status
    </a></li>

    <li><a href="statuses.php?type=tray">
        Manage Tray Status
    </a></li>

    <li><a href="statuses.php?type=vend">
        Manage Vending Record Status
    </a></li>

    <li><a href="movements.php">
        View Stock Movement Records
    </a></li>

    <li><a href="vending_records.php">
        View Vending Records
    </a></li>
    </ul>

</div>

</body>
</html>
