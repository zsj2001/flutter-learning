<?php
$currentPage = basename($_SERVER['PHP_SELF']);

if ($currentPage == 'items.php') {
    $pageTitle = 'Item';
} elseif ($currentPage == 'machines.php') {
    $pageTitle = 'Machine';
} elseif ($currentPage == 'tim.php') {
    $pageTitle = 'Tray Item Movement';
} elseif ($currentPage == 'vendRecord.php') {
    $pageTitle = 'Vending Record';
} elseif ($currentPage == 'trays.php') {
    $pageTitle = 'Tray';
} elseif ($currentPage == 'statuses.php') {
    
    $statusTableType = $_GET['type'] ?? '';
    switch ($statusTableType) {
        case 'machine':
            $pageTitle = 'Machine Status';
            break;
        case 'item':
            $pageTitle = 'Item Status';
            break;
        case 'tray':
            $pageTitle = 'Tray Status';
            break;
        case 'vend':
            $pageTitle = 'Vending Record Status';
            break;
        default:
            $pageTitle = 'Statuses';
    }
} else {
    $pageTitle = 'Admin page';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="nav-links">
    <a href="items.php">Manage Items</a>
    <a href="machines.php">Manage Machines</a>
    <a href="trays.php">Manage Trays</a>
    <a href="statuses.php?type=machine">Manage Machine Statuses</a>
    <a href="statuses.php?type=item">Manage Item Status</a>
    <a href="statuses.php?type=tray">Manage Tray Status</a>
    <a href="statuses.php?type=vend">Manage Vending Record Status</a>
    <a href="vendRecord.php">Vending Record</a>
    <a href="tim.php">Tray Item Movement</a>
</div>
</body>
</html>
