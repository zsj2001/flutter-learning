
<?php

declare(strict_types=1);

$host = 'localhost';
$database = 'vend_mac_db';
$username = 'root';
$password = '7430';
$port = '3306';
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
                    ON UPDATE CASCADE ON DELETE RESTRICT,     -- UPDATE CASCADE: If main status name changes, the connected machines update automatically. DELETE RESTRICT: Cannot delete a status if active machines are still linked to it.
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

                FOREIGN KEY (macID)
                    REFERENCES Machine(macID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,

                FOREIGN KEY (itemID)
                    REFERENCES Item(itemID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,

                FOREIGN KEY (trayStatusID)
                    REFERENCES Tray_Status(trayStatusID)
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

                FOREIGN KEY (macID)
                    REFERENCES Machine(macID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,
                FOREIGN KEY (trayID)
                    REFERENCES Tray(trayID)
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

                FOREIGN KEY (macID)
                    REFERENCES Machine(macID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,

                FOREIGN KEY (trayID)
                    REFERENCES Tray(trayID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,

                FOREIGN KEY (itemID)
                    REFERENCES Item(itemID)
                    ON UPDATE CASCADE ON DELETE RESTRICT,

                FOREIGN KEY (vrStatusID)
                    REFERENCES Vend_Record_Status(vrStatusID)
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
        $statuses = [
            'Machine_Status' => [
                'macStatusID' => 'macStatusName',   // look up raw ID, display it to the readable name
                'values' => ['READY', 'OFFLINE', 'MAINTENANCE']
            ],
            'Tray_Status' => [
                'trayStatusID' => 'trayStatusName',
                'values' => ['EMPTY', 'FULL', 'LOW']
            ],
            'Item_Status' => [
                'itemStatusID' => 'itemStatusName',
                'values' => ['AVAILABLE', 'LOW', 'UNAVAILABLE']
            ],
            'Vend_Record_Status' => [
                'vrStatusID' => 'vrStatusName',
                'values' => ['SUCCESS', 'FAILED']
            ],
        ];

        foreach ($statuses as $table => $data) {
            $idColumn = $data['values'] ? $data[array_key_first($data)] : '';
            $nameColumn = $data[array_key_first($data)];

            // Check whether the status already exists
            $check = $pdo->prepare(
                "SELECT EXISTS(SELECT 1 FROM `$table` WHERE `$nameColumn` = :name)"
            );

            $insert = $pdo->prepare(
                "INSERT INTO `$table` (`$nameColumn`) VALUES (:name)"
            );

            foreach ($data['values'] as $name) {
                $check->execute(['name' => $name]);

                if ($check->fetchColumn() == 0) {
                    $insert->execute(['name' => $name]);
                }
            }
        }
        echo "Status data checked.<br>";


        // Get status IDs
        $stmt = $pdo->prepare(
            "SELECT macStatusID FROM Machine_Status
             WHERE macStatusName = 'READY'"
        );
        $stmt->execute();
        $machineStatusID = $stmt->fetchColumn();

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

        $stmt = $pdo->prepare(
            "SELECT vrStatusID FROM Vend_Record_Status
             WHERE vrStatusName = 'SUCCESS'"
        );
        $stmt->execute();
        $vendStatusID = $stmt->fetchColumn();


        // Insert machine if it does not exist
        $machineLocation = 'Win Hotel Sibu';

        $check = $pdo->prepare(
            "SELECT macID FROM Machine WHERE macLoc = :location"
        );
        $check->execute(['location' => $machineLocation]);
        $machineID = $check->fetchColumn();

        if ($machineID === false) {
            $insert = $pdo->prepare(
                "INSERT INTO Machine (macStatusID, macLoc)
                 VALUES (:statusID, :location)"
            );
            $insert->execute([
                'statusID' => $machineStatusID,
                'location' => $machineLocation
            ]);

            $machineID = $pdo->lastInsertId();
        }
        $machineID = $machineID;
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
            $itemIDs[$itemName] = (int) $itemID;
        }
        echo "Item data checked.<br>";


        // Insert tray if it does not exist
        $check = $pdo->prepare(
            "SELECT trayNo FROM Tray
             WHERE macID = :machineID AND itemID = :itemID"
        );
        $check->execute([
            'machineID' => $machineID,
            'itemID' => $itemID
        ]);
        $trayNo = $check->fetchColumn();

        if ($trayNo === false) {
            $insert = $pdo->prepare(
                "INSERT INTO Tray
                    (itemID, macID, trayCapacity, trayItemQty, trayStatusID)
                 VALUES (:itemID, :machineID, :capacity, :qty, :statusID)"
            );
            $insert->execute([
                'itemID' => $itemID,
                'machineID' => $machineID,
                'capacity' => 20,
                'qty' => 10,
                'statusID' => $trayStatusID
            ]);

            $trayNo = $pdo->lastInsertId();
        }

        $trayNo = (int) $trayNo;
        echo "Tray data checked.<br>";


        // Insert initial restock movement once
        $remarks = 'Initial sample restock';

        $check = $pdo->prepare(
            "SELECT movID FROM Tray_Item_Movement
             WHERE macID = :machineID
             AND trayNo = :trayNo
             AND movType = 'RESTOCK'
             AND remarks = :remarks"
        );
        $check->execute([
            'machineID' => $machineID,
            'trayNo' => $trayNo,
            'remarks' => $remarks
        ]);

        if ($check->fetchColumn() === false) {
            $insert = $pdo->prepare(
                "INSERT INTO Tray_Item_Movement
                    (macID, trayNo, movType, Qty, movTime, remarks)
                 VALUES
                    (:machineID, :trayNo, :type, :qty, :time, :remarks)"
            );
            $insert->execute([
                'machineID' => $machineID,
                'trayNo' => $trayNo,
                'type' => 'RESTOCK',
                'qty' => 10,
                'time' => '2026-01-01 09:00:00',
                'remarks' => $remarks
            ]);
        }

        echo "Movement data checked.<br>";


        // Insert sample vending record once
        $vendTime = '2026-01-01 12:00:00';

        $check = $pdo->prepare(
            "SELECT vrID FROM Vend_Record
             WHERE macID = :machineID
             AND trayNo = :trayNo
             AND itemID = :itemID
             AND vrTime = :time"
        );
        $check->execute([
            'machineID' => $machineID,
            'trayNo' => $trayNo,
            'itemID' => $itemID,
            'time' => $vendTime
        ]);

        if ($check->fetchColumn() === false) {
            $insert = $pdo->prepare(
                "INSERT INTO Vend_Record
                    (macID, trayNo, itemID, vrStatusID, Qty, vrTime)
                 VALUES
                    (:machineID, :trayNo, :itemID, :statusID, :qty, :time)"
            );
            $insert->execute([
                'machineID' => $machineID,
                'trayNo' => $trayNo,
                'itemID' => $itemID,
                'statusID' => $vendStatusID,
                'qty' => 1,
                'time' => $vendTime
            ]);
        }

        echo "Vending record checked.<br>";


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

    exit(1);
}

?>
