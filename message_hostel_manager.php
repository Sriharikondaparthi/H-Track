<?php
// message_hostel_manager.php
require 'includes/config.inc.php';
date_default_timezone_set('Asia/Kolkata');
if (session_status() === PHP_SESSION_NONE) session_start();

// small helper - safe prepare
function safe_prepare($conn, $sql) {
    try {
        $stmt = $conn->prepare($sql);
        return $stmt;
    } catch (Exception $e) {
        return false;
    }
}

// Try to fetch admin name using possible column names
function fetch_admin_name($conn, $admin_id) {
    $variants = [
        "SELECT username AS name FROM admin WHERE id = ? LIMIT 1",
        "SELECT username AS name FROM admin WHERE admin_id = ? LIMIT 1",
        "SELECT name AS name FROM admin WHERE id = ? LIMIT 1",
        "SELECT name AS name FROM admin WHERE admin_id = ? LIMIT 1"
    ];
    foreach ($variants as $sql) {
        $stmt = safe_prepare($conn, $sql);
        if (!$stmt) continue;
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $stmt->close();
            return $r['name'] ?? null;
        }
        $stmt->close();
    }
    return null;
}

// Helper to fetch student display name
function fetch_student_display($conn, $student_id) {
    // try both Student table column names (Student_id) and Students table variants
    $variants = [
        ["student", "Student_id", "Fname", "Lname"],
        ["Students", "roll", "fname", "lname"],
        ["student", "Student_id", "fname", "lname"],
        ["Students", "id", "fname", "lname"]
    ];
    foreach ($variants as $v) {
        list($table, $keycol, $fnamecol, $lnamecol) = $v;
        $sql = "SELECT {$fnamecol} AS fn, {$lnamecol} AS ln, {$keycol} AS keyval FROM {$table} WHERE {$keycol} = ? LIMIT 1";
        $stmt = safe_prepare($conn, $sql);
        if (!$stmt) continue;
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $stmt->close();
            $full = trim(($r['fn'] ?? '') . ' ' . ($r['ln'] ?? ''));
            if ($full !== '') return "{$full} ({$r['keyval']})";
            return (string)($r['keyval'] ?? $student_id);
        }
        $stmt->close();
    }
    // fallback
    return "Student (ID: " . htmlspecialchars($student_id) . ")";
}

// Helper to fetch manager display (hostel name if possible)
function fetch_manager_display($conn, $mgr_key) {
    // try to find by Hostel_man_id first
    $stmt = safe_prepare($conn, "SELECT Hostel_man_id, Hostel_id FROM Hostel_Manager WHERE Hostel_man_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $mgr_key);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $hid = $r['Hostel_id'] ?? null;
            $stmt->close();
            if ($hid) {
                $qh = safe_prepare($conn, "SELECT Hostel_name FROM Hostel WHERE Hostel_id = ? LIMIT 1");
                if ($qh) {
                    $qh->bind_param("s", $hid);
                    $qh->execute();
                    $rh = $qh->get_result();
                    if ($rh && $rh->num_rows > 0) {
                        $hrow = $rh->fetch_assoc();
                        $qh->close();
                        return htmlspecialchars($hrow['Hostel_name']) . " (Mgr ID: " . htmlspecialchars($mgr_key) . ")";
                    }
                    $qh->close();
                }
            }
            return "Hostel Manager (ID: " . htmlspecialchars($mgr_key) . ")";
        }
        $stmt->close();
    }
    // fallback: try numeric primary id
    $stmt2 = safe_prepare($conn, "SELECT id, Hostel_id FROM Hostel_Manager WHERE id = ? LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param("s", $mgr_key);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($res2 && $res2->num_rows > 0) {
            $r2 = $res2->fetch_assoc();
            $hid = $r2['Hostel_id'] ?? null;
            $stmt2->close();
            if ($hid) {
                $qh2 = safe_prepare($conn, "SELECT Hostel_name FROM Hostel WHERE Hostel_id = ? LIMIT 1");
                if ($qh2) {
                    $qh2->bind_param("s", $hid);
                    $qh2->execute();
                    $rh2 = $qh2->get_result();
                    if ($rh2 && $rh2->num_rows > 0) {
                        $hrow2 = $rh2->fetch_assoc();
                        $qh2->close();
                        return htmlspecialchars($hrow2['Hostel_name']) . " (Mgr ID: " . htmlspecialchars($mgr_key) . ")";
                    }
                    $qh2->close();
                }
            }
            return "Hostel Manager (ID: " . htmlspecialchars($mgr_key) . ")";
        }
        $stmt2->close();
    }
    return "Hostel Manager (ID: " . htmlspecialchars($mgr_key) . ")";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Hostel Manager - Messages Received</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8">
    <link rel="stylesheet" href="web_home/css_home/bootstrap.css">
    <link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
    <link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
    <link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
    <style type="text/css">
        .card-header { padding: 15px; font-size: 30px; }
        .card-body { padding: 15px; }
        .card-footer { text-align: left; padding: 15px; }
        .sender-badge { display:inline-block; padding:4px 8px; border-radius:12px; font-size:13px; font-weight:600; margin-right:10px;}
        .sender-admin { background:#d9534f; color:#fff; }
        .sender-manager { background:#0275d8; color:#fff; }
        .sender-student { background:#6c757d; color:#fff; }
    </style>
</head>
<body>

<!-- banner -->
<div class="inner-page-banner" id="home">
    <!--Header-->
    <header>
        <div class="container agile-banner_nav">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <h1><a class="navbar-brand" href="home_manager.php">H-Track<span class="display"></span></a></h1>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item"><a class="nav-link" href="home_manager.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="allocate_room.php">Allocate Room</a></li>
                        <li class="nav-item"><a class="nav-link" href="message_hostel_manager.php">Messages Received</a></li>
                        <li class="dropdown nav-item">
                            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Rooms <b class="caret"></b></a>
                            <ul class="dropdown-menu agile_short_dropdown">
                                <li><a href="allocated_rooms.php">Allocated Rooms</a></li>
                                <li><a href="empty_rooms.php">Empty Rooms</a></li>
                                <li><a href="vacate_rooms.php">Vacate Rooms</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="contact_manager.php">Contact</a></li>
                        <li class="dropdown nav-item">
                            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?> <b class="caret"></b></a>
                            <ul class="dropdown-menu agile_short_dropdown">
                                <li><a href="admin/manager_profile.php">My Profile</a></li>
                                <li><a href="includes/logout.inc.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
    <!--Header-->
</div>
<!-- //banner -->

<br><br><br>

<?php
// manager identification: prefer session key 'hostel_man_id' otherwise try 'manager_id'
$hostel_man_id = $_SESSION['hostel_man_id'] ?? $_SESSION['manager_id'] ?? '';

// defensive
if (empty($hostel_man_id)) {
    echo '<div class="container"><div class="alert alert-warning">Manager session not found. Please login.</div></div>';
} else {
    // fetch messages addressed to this manager (receiver_type = 'manager')
    $sql = "SELECT * FROM Message WHERE receiver_type = 'manager' AND receiver_id = ? ORDER BY created_at DESC";
    $stmt = safe_prepare($conn, $sql);
    if ($stmt) {
        $stmt->bind_param("s", $hostel_man_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // fallback simple query
        $q = "SELECT * FROM Message WHERE receiver_type = 'manager' AND receiver_id = '" . mysqli_real_escape_string($conn, $hostel_man_id) . "' ORDER BY created_at DESC";
        $result = mysqli_query($conn, $q);
    }

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subject = $row['subject_h'] ?? '';
            $message = nl2br(htmlspecialchars($row['message'] ?? ''));
            $msg_date = $row['msg_date'] ?? '';
            $msg_time = $row['msg_time'] ?? '';
            $sender_type = $row['sender_type'] ?? '';
            $sender_id = $row['sender_id'] ?? '';

            // determine badge and display label
            $badge_class = 'sender-student';
            $badge_text = 'Student';
            $sender_label = htmlspecialchars($sender_id);

            if ($sender_type === 'admin') {
                $badge_class = 'sender-admin';
                $badge_text = 'Admin';
                $name = null;
                if (!empty($sender_id)) $name = fetch_admin_name($conn, $sender_id);
                if ($name) $sender_label = htmlspecialchars($name);
                else $sender_label = 'Admin' . (!empty($sender_id) ? ' (ID: ' . htmlspecialchars($sender_id) . ')' : '');
            } elseif ($sender_type === 'manager') {
                $badge_class = 'sender-manager';
                $badge_text = 'Manager';
                $sender_label = fetch_manager_display($conn, $sender_id);
            } else {
                // student fallback (try multiple student table names)
                $badge_class = 'sender-student';
                $badge_text = 'Student';
                $sender_label = fetch_student_display($conn, $sender_id);
            }
            // formatted timestamp
            $timestamp = trim($msg_date . ' ' . $msg_time);
            $display_time = $timestamp !== '' ? ( ($ts = strtotime($timestamp)) !== false ? date("d-m-Y h:i A", $ts) : htmlspecialchars($timestamp) ) : '';
            ?>

            <div class="container">
                <div class="card">
                    <div class="card-header"><span class="sender-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span> <strong><?php echo htmlspecialchars($subject); ?></strong></div>
                    <div class="card-body"><?php echo $message; ?></div>
                    <div class="card-footer">
                        <strong><?php echo $sender_label; ?></strong>
                        <span style="float:right"><?php echo $display_time; ?></span>
                    </div>
                </div>
            </div>
            <br><br>

            <?php
        } // end while
        if ($stmt && $stmt instanceof mysqli_stmt) $stmt->close();
    } else {
        echo '<div class="container"><p>No messages found.</p></div>';
    }
}
?>

<br><br>

<!-- footer (keep your original footer markup if you like; this matches your style) -->
<footer class="py-5">
    <div class="container py-md-5">
        <div class="footer-logo mb-5 text-center"><a class="navbar-brand" href="https://dresut.vercel.app/" target="_blank">ESUT</a></div>
        <div class="footer-grid"><div class="list-footer"><ul class="footer-nav text-center"><li><a href="home_manager.php">Home</a></li><li><a href="allocate_room.php">Allocate</a></li><li><a href="contact_manager.php">Contact</a></li><li><a href="admin/manager_profile.php">Profile</a></li></ul></div></div>
    </div>
</footer>

<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
<script src="web_home/js/SmoothScroll.min.js"></script>
<script type="text/javascript" src="web_home/js/move-top.js"></script>
<script type="text/javascript" src="web_home/js/easing.js"></script>

</body>
</html>
