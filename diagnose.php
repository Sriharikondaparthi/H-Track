<?php
// Save this as: diagnose_rooms.php
session_start();
require 'includes/config.inc.php';

$username = $_SESSION['username'] ?? null;
$hostel_id = $_SESSION['hostel_id'] ?? null;

if (!$username) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room & Hostel Diagnostic Tool</title>
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <style>
        body { padding: 20px; background: #f5f5f5; }
        .diagnostic-box { 
            background: white; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { border-left: 4px solid #28a745; }
        .danger { border-left: 4px solid #dc3545; }
        .warning { border-left: 4px solid #ffc107; }
        .info { border-left: 4px solid #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { font-size: 14px; }
        .badge { padding: 5px 10px; border-radius: 3px; color: white; }
        .badge-success { background: #28a745; }
        .badge-danger { background: #dc3545; }
        .highlight { background: yellow; font-weight: bold; }
        h3 { color: #333; margin-top: 0; }
        .btn { margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Room & Hostel Diagnostic Tool</h1>
        <p><a href="allocate_room.php" class="btn btn-primary">← Back to Allocate Room</a></p>

        <!-- SESSION INFO -->
        <div class="diagnostic-box info">
            <h3>📋 Step 1: Your Session Information</h3>
            <table class="table table-bordered">
                <tr>
                    <th>Username</th>
                    <td><?php echo htmlspecialchars($username); ?></td>
                </tr>
                <tr>
                    <th>Hostel ID in Session</th>
                    <td class="highlight"><?php echo htmlspecialchars($hostel_id ?? 'NOT SET'); ?></td>
                </tr>
                <tr>
                    <th>Data Type</th>
                    <td><?php echo gettype($hostel_id); ?></td>
                </tr>
            </table>
            <?php if (!$hostel_id): ?>
                <div class="alert alert-danger">
                    <strong>❌ ERROR:</strong> Hostel ID is not set in your session! Please logout and login again.
                </div>
            <?php endif; ?>
        </div>

        <?php if ($hostel_id): ?>
        
        <!-- HOSTEL INFO -->
        <div class="diagnostic-box info">
            <h3>🏠 Step 2: Hostel Information</h3>
            <?php
            $hostelStmt = $conn->prepare("SELECT * FROM Hostel WHERE Hostel_id = ?");
            $hostelStmt->bind_param("s", $hostel_id);
            $hostelStmt->execute();
            $hostelRes = $hostelStmt->get_result();
            
            if ($hostelRes->num_rows > 0) {
                $hostelData = $hostelRes->fetch_assoc();
                echo '<div class="alert alert-success">✓ Hostel found in database!</div>';
                echo '<table class="table table-bordered">';
                foreach ($hostelData as $key => $value) {
                    echo '<tr><th>' . htmlspecialchars($key) . '</th><td>' . htmlspecialchars($value) . '</td></tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="alert alert-danger">❌ No hostel found with ID: ' . htmlspecialchars($hostel_id) . '</div>';
            }
            $hostelStmt->close();
            ?>
        </div>

        <!-- ALL HOSTELS -->
        <div class="diagnostic-box warning">
            <h3>🏢 Step 3: All Hostels in Database</h3>
            <?php
            $allHostels = mysqli_query($conn, "SELECT Hostel_id, Hostel_name FROM Hostel");
            if ($allHostels && mysqli_num_rows($allHostels) > 0) {
                echo '<table class="table table-striped"><thead><tr><th>Hostel ID</th><th>Hostel Name</th><th>Match?</th></tr></thead><tbody>';
                while ($h = mysqli_fetch_assoc($allHostels)) {
                    $match = ($h['Hostel_id'] == $hostel_id) ? '<span class="badge badge-success">✓ YOUR HOSTEL</span>' : '';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($h['Hostel_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($h['Hostel_name']) . '</td>';
                    echo '<td>' . $match . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            ?>
        </div>

        <!-- ROOM TABLE STRUCTURE -->
        <div class="diagnostic-box info">
            <h3>🔧 Step 4: Room Table Structure</h3>
            <?php
            $structure = mysqli_query($conn, "DESCRIBE Room");
            if ($structure) {
                echo '<table class="table table-bordered table-sm">';
                echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead><tbody>';
                while ($col = mysqli_fetch_assoc($structure)) {
                    $highlight = ($col['Field'] == 'Allocated' || $col['Field'] == 'Hostel_id') ? 'class="highlight"' : '';
                    echo '<tr ' . $highlight . '>';
                    echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            ?>
        </div>

        <!-- TOTAL ROOMS -->
        <div class="diagnostic-box warning">
            <h3>📊 Step 5: Total Rooms for Your Hostel</h3>
            <?php
            // Check with exact match
            $totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM Room WHERE Hostel_id = ?");
            $totalStmt->bind_param("s", $hostel_id);
            $totalStmt->execute();
            $totalRes = $totalStmt->get_result();
            $totalRow = $totalRes->fetch_assoc();
            $totalRooms = $totalRow['total'];
            $totalStmt->close();
            
            if ($totalRooms > 0) {
                echo '<div class="alert alert-success">';
                echo '<h4>✓ Found ' . $totalRooms . ' total rooms for Hostel ID: ' . htmlspecialchars($hostel_id) . '</h4>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-danger">';
                echo '<h4>❌ NO ROOMS found for Hostel ID: ' . htmlspecialchars($hostel_id) . '</h4>';
                echo '<p><strong>Possible reasons:</strong></p>';
                echo '<ul>';
                echo '<li>No rooms have been added to the Room table for this hostel</li>';
                echo '<li>Hostel_id in Room table doesn\'t match your session Hostel_id</li>';
                echo '<li>Data type mismatch (string vs integer)</li>';
                echo '</ul>';
                echo '</div>';
                
                // Check if there are ANY rooms in the database
                $anyRooms = mysqli_query($conn, "SELECT COUNT(*) as total FROM Room");
                $anyRow = mysqli_fetch_assoc($anyRooms);
                echo '<p><strong>Total rooms in entire database:</strong> ' . $anyRow['total'] . '</p>';
            }
            ?>
        </div>

        <!-- ALL ROOMS DETAILED -->
        <div class="diagnostic-box info">
            <h3>🗂️ Step 6: All Rooms for Your Hostel (Detailed)</h3>
            <?php
            $allRoomsStmt = $conn->prepare("SELECT * FROM Room WHERE Hostel_id = ? ORDER BY Room_No ASC");
            $allRoomsStmt->bind_param("s", $hostel_id);
            $allRoomsStmt->execute();
            $allRoomsRes = $allRoomsStmt->get_result();
            
            if ($allRoomsRes->num_rows > 0) {
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped table-bordered">';
                echo '<thead><tr><th>Room_id</th><th>Room_No</th><th>Hostel_id</th><th>Allocated (Value)</th><th>Allocated (Type)</th><th>Status</th></tr></thead><tbody>';
                
                $available = 0;
                $allocated = 0;
                
                while ($room = $allRoomsRes->fetch_assoc()) {
                    $allocValue = $room['Allocated'];
                    $isAvailable = ($allocValue === '0' || $allocValue === 0);
                    
                    if ($isAvailable) $available++;
                    else $allocated++;
                    
                    $statusClass = $isAvailable ? 'table-success' : 'table-danger';
                    $statusBadge = $isAvailable ? '<span class="badge badge-success">AVAILABLE</span>' : '<span class="badge badge-danger">ALLOCATED</span>';
                    
                    echo '<tr class="' . $statusClass . '">';
                    echo '<td>' . htmlspecialchars($room['Room_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($room['Room_No']) . '</td>';
                    echo '<td>' . htmlspecialchars($room['Hostel_id']) . '</td>';
                    echo '<td><code>' . htmlspecialchars(var_export($allocValue, true)) . '</code></td>';
                    echo '<td>' . htmlspecialchars(gettype($allocValue)) . '</td>';
                    echo '<td>' . $statusBadge . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
                
                echo '<div class="alert alert-info">';
                echo '<strong>Summary:</strong><br>';
                echo '✓ Available Rooms: <strong>' . $available . '</strong><br>';
                echo '✗ Allocated Rooms: <strong>' . $allocated . '</strong>';
                echo '</div>';
                
            } else {
                echo '<div class="alert alert-warning">No rooms found for your hostel.</div>';
            }
            $allRoomsStmt->close();
            ?>
        </div>

        <!-- ROOMS WITH HOSTEL_ID MISMATCH -->
        <div class="diagnostic-box warning">
            <h3>⚠️ Step 7: Check for Hostel_id Mismatches</h3>
            <?php
            $allRoomsInDb = mysqli_query($conn, "SELECT DISTINCT Hostel_id, COUNT(*) as count FROM Room GROUP BY Hostel_id");
            if ($allRoomsInDb && mysqli_num_rows($allRoomsInDb) > 0) {
                echo '<p>Rooms grouped by Hostel_id in database:</p>';
                echo '<table class="table table-bordered">';
                echo '<thead><tr><th>Hostel_id in Room Table</th><th>Room Count</th><th>Match?</th></tr></thead><tbody>';
                while ($group = mysqli_fetch_assoc($allRoomsInDb)) {
                    $match = ($group['Hostel_id'] == $hostel_id);
                    $rowClass = $match ? 'table-success' : '';
                    $matchBadge = $match ? '<span class="badge badge-success">✓ YOURS</span>' : '';
                    echo '<tr class="' . $rowClass . '">';
                    echo '<td>' . htmlspecialchars($group['Hostel_id']) . ' <small>(' . gettype($group['Hostel_id']) . ')</small></td>';
                    echo '<td>' . $group['count'] . '</td>';
                    echo '<td>' . $matchBadge . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            ?>
        </div>

        <!-- PENDING APPLICATIONS -->
        <div class="diagnostic-box info">
            <h3>📝 Step 8: Pending Applications</h3>
            <?php
            $appStmt = $conn->prepare("SELECT COUNT(*) as pending FROM Application WHERE Hostel_id = ? AND Application_status = '1'");
            $appStmt->bind_param("s", $hostel_id);
            $appStmt->execute();
            $appRes = $appStmt->get_result();
            $appRow = $appRes->fetch_assoc();
            $pendingCount = $appRow['pending'];
            $appStmt->close();
            
            echo '<div class="alert alert-info">';
            echo '<strong>Pending Applications:</strong> ' . $pendingCount;
            echo '</div>';
            ?>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="diagnostic-box warning">
            <h3>⚡ Step 9: Quick Fix Actions</h3>
            
            <?php if ($totalRooms == 0): ?>
                <div class="alert alert-danger">
                    <strong>Problem Detected:</strong> No rooms exist for your hostel!
                </div>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="add_rooms">
                    <label>Number of rooms to add: <input type="number" name="room_count" value="10" min="1" max="100"></label>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Add rooms to hostel?');">Add Rooms</button>
                </form>
            <?php elseif (isset($available) && $available == 0): ?>
                <div class="alert alert-warning">
                    <strong>Problem Detected:</strong> All rooms are allocated!
                </div>
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="reset_all">
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Reset ALL rooms to available (Allocated=0)?');">Reset All Rooms to Available</button>
                </form>
            <?php else: ?>
                <div class="alert alert-success">
                    <strong>✓ Everything looks good!</strong> You have <?php echo $available ?? 0; ?> available room(s).
                </div>
                <a href="allocate_room.php" class="btn btn-success">Proceed to Allocate Rooms →</a>
            <?php endif; ?>
            
            <hr>
            <p><strong>Additional Actions:</strong></p>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="reset_all">
                <button type="submit" class="btn btn-secondary">Reset All Rooms</button>
            </form>
            
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="fix_data_types">
                <button type="submit" class="btn btn-info">Fix Data Types (Ensure Allocated is '0' or '1')</button>
            </form>
        </div>

        <?php
        // Handle Actions
        if (isset($_POST['action'])) {
            echo '<div class="diagnostic-box success"><h3>✅ Action Result</h3>';
            
            if ($_POST['action'] === 'reset_all') {
                $resetStmt = $conn->prepare("UPDATE Room SET Allocated = '0' WHERE Hostel_id = ?");
                $resetStmt->bind_param("s", $hostel_id);
                if ($resetStmt->execute()) {
                    echo '<div class="alert alert-success">Successfully reset ' . $resetStmt->affected_rows . ' rooms to available!</div>';
                    echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
                }
                $resetStmt->close();
            }
            
            if ($_POST['action'] === 'add_rooms') {
                $roomCount = intval($_POST['room_count']);
                $maxStmt = $conn->prepare("SELECT MAX(CAST(Room_No AS UNSIGNED)) as max_room FROM Room WHERE Hostel_id = ?");
                $maxStmt->bind_param("s", $hostel_id);
                $maxStmt->execute();
                $maxRes = $maxStmt->get_result();
                $maxRow = $maxRes->fetch_assoc();
                $startNum = ($maxRow['max_room'] ?? 0) + 1;
                $maxStmt->close();
                
                $insertStmt = $conn->prepare("INSERT INTO Room (Hostel_id, Room_No, Allocated) VALUES (?, ?, '0')");
                $success = 0;
                for ($i = 0; $i < $roomCount; $i++) {
                    $roomNo = $startNum + $i;
                    $insertStmt->bind_param("si", $hostel_id, $roomNo);
                    if ($insertStmt->execute()) $success++;
                }
                $insertStmt->close();
                echo '<div class="alert alert-success">Successfully added ' . $success . ' rooms!</div>';
                echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
            }
            
            if ($_POST['action'] === 'fix_data_types') {
                // Normalize all Allocated values
                $fix1 = $conn->prepare("UPDATE Room SET Allocated = '0' WHERE Hostel_id = ? AND (Allocated IS NULL OR Allocated = '' OR Allocated = 0)");
                $fix1->bind_param("s", $hostel_id);
                $fix1->execute();
                $count1 = $fix1->affected_rows;
                $fix1->close();
                
                $fix2 = $conn->prepare("UPDATE Room SET Allocated = '1' WHERE Hostel_id = ? AND Allocated != '0' AND Allocated IS NOT NULL");
                $fix2->bind_param("s", $hostel_id);
                $fix2->execute();
                $count2 = $fix2->affected_rows;
                $fix2->close();
                
                echo '<div class="alert alert-success">Fixed data types! Set ' . $count1 . ' rooms to \'0\' and ' . $count2 . ' rooms to \'1\'</div>';
                echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
            }
            
            echo '</div>';
        }
        ?>

        <?php endif; // end if hostel_id ?>

        <!-- SQL QUERIES TO RUN MANUALLY -->
        <div class="diagnostic-box info">
            <h3>💻 Step 10: Manual SQL Queries (if needed)</h3>
            <p>Run these in phpMyAdmin if you need manual control:</p>
            
            <p><strong>Check your rooms:</strong></p>
            <pre>SELECT * FROM Room WHERE Hostel_id = '<?php echo htmlspecialchars($hostel_id); ?>';</pre>
            
            <p><strong>Reset all rooms to available:</strong></p>
            <pre>UPDATE Room SET Allocated = '0' WHERE Hostel_id = '<?php echo htmlspecialchars($hostel_id); ?>';</pre>
            
            <p><strong>Add sample rooms:</strong></p>
            <pre>INSERT INTO Room (Hostel_id, Room_No, Allocated) VALUES
('<?php echo htmlspecialchars($hostel_id); ?>', '101', '0'),
('<?php echo htmlspecialchars($hostel_id); ?>', '102', '0'),
('<?php echo htmlspecialchars($hostel_id); ?>', '103', '0');</pre>
        </div>

    </div>
</body>
</html>