<?php

declare(strict_types=1);

$host = 'localhost';
$database = '[your db name]';
$username = '[your sql app username]';
$password = '[your sql app password]';
$port = '[your sql app port number]';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$database;port=$port;charset=$charset";

try {
    // Connect to database
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "Connected to database successfully.<br>";


    // Create tables
    $schema = [
        'Machine_Status' => "
            CREATE TABLE IF NOT EXISTS Machine_Status (
                macStatusID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                macStatusName VARCHAR(100) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        'Tray_Status' => "
            CREATE TABLE IF NOT EXISTS Tray_Status (
                trayStatusID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                trayStatusName VARCHAR(100) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        'Item_Status' => "
            CREATE TABLE IF NOT EXISTS Item_Status (
                itemStatusID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                itemStatusName VARCHAR(100) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        'Vend_Record_Status' => "
            CREATE TABLE IF NOT EXISTS Vend_Record_Status (
                vrStatusID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                vrStatusName VARCHAR(100) NOT NULL UNIQUE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        'Machine' => "
            CREATE TABLE IF NOT EXISTS Machine (
                macID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                macStatusID INT UNSIGNED NOT NULL,
                macLoc VARCHAR(255) NOT NULL,
                FOREIGN KEY (macStatusID)
                    REFERENCES Machine_Status(macStatusID)
                    ON UPDATE CASCADE ON DELETE RESTRICT, -- UPDATE CASCADE: If main status name changes, the connected machines update automatically. DELETE RESTRICT: Cannot delete a status if active machines are still linked to it.
                INDEX idx_machine_status (macStatusID)        -- speed up queries and foreign key joins on macStatusID
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        'Item' => "
            CREATE TABLE IF NOT EXISTS Item (
                itemID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                itemStatusID INT UNSIGNED NOT NULL,
                itemName VARCHAR(150) NOT NULL,
                itemPic VARCHAR(255) NULL,
                itemPrice DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (itemStatusID)
                    REFERENCES Item_Status(itemStatusID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                INDEX idx_item_status (itemStatusID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        'Tray' => "
            CREATE TABLE IF NOT EXISTS Tray (
                trayID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                macID INT UNSIGNED NOT NULL,
                itemID INT UNSIGNED NOT NULL,
                trayLabel VARCHAR(20) NOT NULL,
                trayCapacity INT UNSIGNED NOT NULL,
                trayItemQty INT UNSIGNED NOT NULL DEFAULT 0,
                trayStatusID INT UNSIGNED NOT NULL,
                UNIQUE (macID, trayLabel),
                CHECK (trayCapacity > 0),
                CHECK (trayItemQty <= trayCapacity),
                FOREIGN KEY (macID) REFERENCES Machine(macID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (itemID) REFERENCES Item(itemID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (trayStatusID) REFERENCES Tray_Status(trayStatusID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                INDEX idx_tray_item (itemID),
                INDEX idx_tray_machine (macID),
                INDEX idx_tray_status (trayStatusID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        'Tray_Item_Movement' => "
            CREATE TABLE IF NOT EXISTS Tray_Item_Movement (
                movID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                macID INT UNSIGNED NOT NULL,
                trayID INT UNSIGNED NOT NULL,
                movType ENUM('RESTOCK', 'DISPENSE') NOT NULL,
                Qty INT UNSIGNED NOT NULL,
                movTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                remarks TEXT NULL,
                FOREIGN KEY (macID) REFERENCES Machine(macID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (trayID) REFERENCES Tray(trayID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                INDEX idx_movement_machine (macID),
                INDEX idx_movement_tray (trayID),
                INDEX idx_movement_time (movTime)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",

        'Vend_Record' => "
            CREATE TABLE IF NOT EXISTS Vend_Record (
                vrID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                macID INT UNSIGNED NOT NULL,
                trayID INT UNSIGNED NOT NULL,
                itemID INT UNSIGNED NOT NULL,
                vrStatusID INT UNSIGNED NOT NULL,
                Qty INT UNSIGNED NOT NULL,
                vrTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (macID) REFERENCES Machine(macID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (trayID) REFERENCES Tray(trayID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (itemID) REFERENCES Item(itemID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (vrStatusID) REFERENCES Vend_Record_Status(vrStatusID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                INDEX idx_vend_record_machine (macID),
                INDEX idx_vend_record_tray (trayID),
                INDEX idx_vend_record_item (itemID),
                INDEX idx_vend_record_status (vrStatusID),
                INDEX idx_vend_record_time (vrTime)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
    ];

    foreach ($schema as $table => $sql) {
        $pdo->exec($sql);
        echo "Table checked: $table<br>";
    }


    // Insert seed data
    $pdo->beginTransaction();
    try {
        $machineStatuses = ['READY', 'OFFLINE', 'MAINTENANCE'];
        $check = $pdo->prepare("SELECT EXISTS(SELECT * FROM Machine_Status WHERE macStatusName = :name)");
        $insert = $pdo->prepare("INSERT INTO Machine_Status (macStatusName) VALUES (:name)");
        foreach ($machineStatuses as $name) {
            $check->execute(['name' => $name]);
            if($check->fetchColumn() == 0) {
                $insert->execute(['name' => $name]);
                echo "Inserted Machine Status: $name<br>";
            }
        }
        echo "Machine Status checked.<br>";

        $trayStatuses = ['EMPTY', 'FULL', 'LOW'];
        $check = $pdo->prepare("SELECT EXISTS(SELECT * FROM Tray_Status WHERE trayStatusName = :name)");
        $insert = $pdo->prepare("INSERT INTO Tray_Status (trayStatusName) VALUES (:name)");
        foreach ($trayStatuses as $name) {
            $check->execute(['name' => $name]);
            if($check->fetchColumn() == 0) {
                $insert->execute(['name' => $name]);
                echo "Inserted Tray Status: $name<br>";
            }
        }
        echo "Tray Status checked.<br>";
            
        $itemStatuses = ['AVAILABLE', 'LOW', 'OUT OF STOCK'];
        $check = $pdo->prepare("SELECT EXISTS(SELECT * FROM Item_Status WHERE itemStatusName = :name)");
        $insert = $pdo->prepare("INSERT INTO Item_Status (itemStatusName) VALUES (:name)");
        foreach ($itemStatuses as $name) {
            $check->execute(['name' => $name]);
            if($check->fetchColumn() == 0) {
                $insert->execute(['name' => $name]);
                echo "Inserted Item Status: $name<br>";
            }
        }
        echo "Item Status checked.<br>";
                
        $vendrecordStatuses = ['SUCCESS', 'FAILED'];
        $check = $pdo->prepare("SELECT EXISTS(SELECT * FROM Vend_Record_Status WHERE vrStatusName = :name)");
        $insert = $pdo->prepare("INSERT INTO Vend_Record_Status (vrStatusName) VALUES (:name)");
        foreach ($vendrecordStatuses as $name) {
            $check->execute(['name' => $name]);
            if($check->fetchColumn() == 0) {
                $insert->execute(['name' => $name]);
                echo "Inserted Vend Record Status: $name<br>";
            }
        }
        echo "Vend Record Status checked.<br>";
                


        // Get status IDs
        $stmt = $pdo->prepare(
            "SELECT macStatusID FROM Machine_Status
             WHERE macStatusName = 'READY'"
        );
        $stmt->execute();
        $macStatusID = $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT trayStatusID FROM Tray_Status
             WHERE trayStatusName = 'FULL'"
        );
        $stmt->execute();
        $trayStatusID = $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT itemStatusID FROM Item_Status
             WHERE itemStatusName = 'AVAILABLE'"
        );
        $stmt->execute();
        $itemStatusID = $stmt->fetchColumn();



        // Insert machine if it does not exist
        $machineLocation = 'Win Hotel Sibu';

        $check = $pdo->prepare("SELECT macID FROM Machine WHERE macLoc = :location");
        $check->execute(['location' => $machineLocation]);

        $macID = $check->fetchColumn();

        if ($macID === false) {
            $insert = $pdo->prepare(
                "INSERT INTO Machine (macStatusID, macLoc)
                VALUES (:statusID, :location)"
            );

            $insert->execute([
                'statusID' => $macStatusID,
                'location' => $machineLocation
            ]);

            $macID = $pdo->lastInsertId();
        }
        $macID = $macID;
        echo "Machine data checked.<br>";


        
        // Insert items if they do not exist
        $items = [
            ['coke zero sugar', 'cola-zero-sugar.jpg', 2.08],
            ['100 plus', '100+.jpg', 2.79],
            ['100 plus lemon', '100+lemon.jpg', 2.79],
            ['fanta grape', 'fanta-grape.jpg', 1.91],
            ['kitkat milk choco', 'kitkat-milk-choco.jpg', 2.50],
            ['maggie hot cup ayam', 'imaggie-hot-cup-ayam.jpg', 2.17],
            ['maggie hot cup kari', 'maggie-hot-cup-kari.jpg', 2.17],
            ['maggie hot cup tomyum', 'maggie-hot-cup-tomyum.jpg', 2.17],
            ['mamee monster', 'mamee-monster.jpg', 2.99],
            ['moma water', 'moma-water.jpg', 1.50],
            ['nutking', 'nutking.jpg', 1.40],
            ['oreo', 'oreo.jpg', 2.00],
            ['snek ku shoyue', 'snek-ku-shoyue.jpg', 3.80],
            ['coffee', 'three-tea-kopi.jpg', 6.00],
            ['teh c special', 'three-tea-teh-c-special.jpg', 6.00],
            ['vida c bankcurrant', 'vida-c-blackcurrant.jpg', 2.90],
            ['vida c orange', 'vida-c-orange.jpg', 2.90],
            ['chrysanthemum tea', 'yeos-chrysanthemum-tea.jpg', 1.42],
            ['green tea', 'yeos-green-tea.jpg', 1.42],
            ['milo', 'milo.jpg', 1.83],
        ];

        $itemIDs = [];

        $check = $pdo->prepare(
            "SELECT itemID FROM Item WHERE itemName = :name"
        );

        $insert = $pdo->prepare(
            "INSERT INTO Item(itemStatusID, itemName, itemPic, itemPrice)
             VALUES (:statusID, :name, :picture, :price)"
        );

        foreach ($items as $item) {
            $itemName = $item[0];
            $itemPic = $item[1];
            $itemPrice = $item[2];

            $check->execute(['name' => $itemName]);
            $itemID = $check->fetchColumn();

            if ($itemID === false) {
                $insert->execute([
                    'statusID' => $itemStatusID,
                    'name' => $itemName,
                    'picture' => $itemPic,
                    'price' => $itemPrice
                ]);

                $itemID = $pdo->lastInsertId();
            }
            $itemIDs[$itemName] = $itemID;
        }
        echo "Item data checked.<br>";


        // Insert tray if it does not exist
        $checkTray = $pdo->prepare(
            "SELECT trayID FROM Tray WHERE macID = :macID AND trayLabel = :trayLabel"
        );

        $insertTray = $pdo->prepare(
           "INSERT INTO Tray(macID, itemID, trayLabel, trayCapacity, trayItemQty, trayStatusID)
            VALUES (:macID, :itemID, :trayLabel, :capacity, :qty, :statusID)"
        );

        $trays = [];
            $trayNumber = 1;
            foreach ($items as $item) {
                $itemName = $item[0];
                $itemID = $itemIDs[$itemName];
                $trayLabel = 'T' . $trayNumber;
                $checkTray->execute([
                    'macID' => $macID,
                    'trayLabel' => $trayLabel
                ]);

                $trayID = $checkTray->fetchColumn();

                if ($trayID === false) {
                    $insertTray->execute([
                        'macID' => $macID,
                        'itemID' => $itemID,
                        'trayLabel' => $trayLabel,
                        'capacity' => 10,
                        'qty' => 10,
                        'statusID' => $trayStatusID
                    ]);
                    $trayID = $pdo->lastInsertId();
                }
                $trayID = $trayID;

                $trays[] = [
                    'macID' => $macID,
                    'trayID' => $trayID,
                    'itemID' => $itemID,
                    'label' => $trayLabel
                ];
                $trayNumber++;
            }
        echo "Tray data checked.<br>";


        /* Insert tray item movement
            $insert = $pdo->prepare(
                "INSERT INTO Tray_Item_Movement(macID, trayID, movType, Qty, remarks)
                 VALUES(:macID, :trayID, :type, :qty, :remarks)"
            );
            $insert->execute([
                'macID' => $macID,
                'trayID' => $trayID,
                'type' => 'RESTOCK',
                'qty' => 'restockQty',
                'remarks' => 'Restocked by staff'
            ]);


        // Insert vending record
            $insert = $pdo->prepare(
                "INSERT INTO Vend_Record(macID, trayID, itemID, vrStatusID, Qty)
                 VALUES(:macID, :trayID, :itemID, :statusID, :qty)"
            );
            $insert->execute([
                'macID' => $macID,
                'trayID' => $trayID,
                'itemID' => $itemID,
                'statusID' => $vendStatusID,
                'qty' => $qty
            ]);*/


        // Save changes
        $pdo->commit();
        echo "<br>Database setup completed successfully.<br>";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
} catch (Throwable $e) {
    echo "<br>DATABASE SETUP FAILED<br>";
    echo "Error: "
        . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        . "<br>";

    exit(1); // 1 - fail, 0 - success
}
?>
