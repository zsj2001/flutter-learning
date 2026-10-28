
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
    <style>
body {
    font-family: Arial, sans-serif;
    background-color: #f4f6f9;
    margin: 0;
    padding: 20px;
    color: #333;
}

.container {
    max-width: 1100px;
    margin: 20px auto;
    background-color: white;
    padding: 25px;
    border-radius: 8px;
}

h1 {
    color: #26384a;
}

h2 {
    margin-top: 30px;
    color: #34495e;
}

label {
    display: block;
    margin-top: 12px;
    margin-bottom: 5px;
}

input, select {
    width: 100%;
    padding: 10px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
}

button {
    background-color: #2878b5;
    color: white;
    border: none;
    padding: 9px 14px;
    margin-top: 12px;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: #1c5c8b;
}

a {
    color: #2878b5;
    text-decoration: none;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

th {
    background-color: #e9eef3;
}

.message {
    background-color: #e8f4e8;
    padding: 12px;
    border-radius: 4px;
}

td form {
    display: inline-block;
}

td button {
    margin-top: 0;
    background-color: #c0392b;
}

td button:hover {
    background-color: #922b21;
}
</style>
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
    </ul>

</div>

</body>
</html>
