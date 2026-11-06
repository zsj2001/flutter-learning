<?php
require 'db.php';
include_once 'admindashboard.php';

$sql = "
    SELECT
        m.macLoc,
        t.trayLabel,
        i.itemName,
        vrs.vrStatusName,
        vr.Qty,
        vr.vrTime
    FROM Vend_Record vr
    INNER JOIN Machine m
        ON vr.macID = m.macID
    INNER JOIN Tray t
        ON vr.trayID = t.trayID
    INNER JOIN Item i
        ON vr.itemID = i.itemID
    INNER JOIN Vend_Record_Status vrs
        ON vr.vrStatusID = vrs.vrStatusID
    ORDER BY vr.vrTime DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$vendRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vending Record</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card">
    <h2>Vending record listing</h2>
    <table>
        <thead>
            <tr>
                <th>Machine location</th>
                <th>Tray label</th>
                <th>Item name</th>
                <th>Status</th>
                <th>Quantity</th>
                <th>Vending record time</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php if (count($vendRecords) > 0): ?>
                    <?php foreach ($vendRecords as $record): ?>
                        <tr>
                            <td><?= $record['macLoc'] ?></td>
                            <td><?= $record['trayLabel'] ?></td>
                            <td><?= $record['itemName'] ?></td>
                            <td><?= $record['vrStatusName'] ?></td>
                            <td><?= $record['Qty'] ?></td>
                            <td><?= $record['vrTime'] ?? '' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="6">No vending records found</td>
                </tr>
                <?php endif; ?>
            </tr>
        </tbody>
    </table>
    </div>
</body>
</html>
