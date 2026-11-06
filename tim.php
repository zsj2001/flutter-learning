<?php
require 'db.php';
include_once 'admindashboard.php';

$sql = "
    SELECT
        m.macLoc,
        t.trayLabel,
        i.itemName,
        tim.movType,
        tim.Qty,
        tim.movTime,
        tim.remarks
    FROM Tray_Item_Movement tim
    INNER JOIN Machine m
        ON tim.macID = m.macID
    INNER JOIN Tray t
        ON tim.trayID = t.trayID
    INNER JOIN Item i
        ON t.itemID = i.itemID
    ORDER BY tim.movTime DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$movements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tray Item Movement</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card">
    <h2>Tray item movement listing</h2>
    <table>
        <thead>
            <tr>
                <th>Machine location</th>
                <th>Tray label</th>
                <th>Item name</th>
                <th>Movement type</th>
                <th>Quantity</th>
                <th>Movement time</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($movements) > 0): ?>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?= $movement['macLoc'] ?></td>
                            <td><?= $movement['trayLabel'] ?></td>
                            <td><?= $movement['itemName'] ?></td>
                            <td><?= $movement['movType'] ?></td>
                            <td><?= $movement['Qty'] ?></td>
                            <td><?= $movement['movTime'] ?></td>
                            <td><?= $movement['remarks'] ?? '' ?></td>
                        </tr>
                    <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No tray movement records found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</body>
</html>
