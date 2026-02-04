<?php require 'includes/config.inc.php'; 
 date_default_timezone_set('Asia/Kolkata'); 

// small helper to prepare safely (catches mysqli_sql_exception)
function safe_prepare($conn, $sql) {
    try {
        $stmt = $conn->prepare($sql);
        return $stmt;
    } catch (mysqli_sql_exception $e) {
        return false;
    } catch (Exception $e) {
        return false;
    }
}

// helper to try multiple admin name query variants
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>H-Track - Messages</title>
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
        .debug-box { background:#fff4e5; padding:10px; border:1px solid #e0b87a; margin:12px 0; font-family:monospace; }
    </style>
</head>

<body>
<!-- header & nav kept same -->
<div class="inner-page-banner" id="home">
    <header>
        <div class="container agile-banner_nav">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <h1><a class="navbar-brand" href="home.php">H-Track</a></h1>
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>

                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="services.php">Hostels</a></li>
                        <li class="nav-item active"><a class="nav-link" href="contact.php">Contact</a></li>
                        <li class="nav-item"><a class="nav-link" href="message_user.php">Message Received</a></li>
                        <li class="dropdown nav-item">
                            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['roll'] ?? ''); ?>
                                <b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu agile_short_dropdown">
                                <li><a href="profile.php">My Profile</a></li>
                                <li><a href="includes/logout.inc.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
</div>

<?php
// Toggle debug to true to show raw message rows (turn off after debugging)
$debug = false;

$roll_no = $_SESSION['roll'] ?? '';

// Defensive: ensure we have a roll to look up messages for
if ($roll_no === '') {
    echo '<div class="container"><p>No roll number in session. Please login.</p></div>';
} else {
    // IMPORTANT CHANGE: only show messages where receiver_type = 'student'
    $sql = "SELECT * FROM Message WHERE receiver_id = ? AND receiver_type = 'student' ORDER BY created_at DESC";
    $stmt = safe_prepare($conn, $sql);
    if ($stmt) {
        $stmt->bind_param("s", $roll_no);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // fallback query (escaped)
        $query = "SELECT * FROM Message WHERE receiver_id ='". mysqli_real_escape_string($conn, $roll_no) ."' AND receiver_type = 'student' ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);
    }

    // If debug = true, also fetch *all* rows that match receiver_id regardless of receiver_type
    if ($debug) {
        $allRows = mysqli_query($conn, "SELECT id,sender_type,sender_id,receiver_type,receiver_id,subject_h,created_at FROM Message WHERE receiver_id = '". mysqli_real_escape_string($conn, $roll_no) ."' ORDER BY created_at DESC LIMIT 50");
        echo '<div class="container"><div class="debug-box"><strong>DEBUG: All Message rows matching receiver_id (any type):</strong><br>';
        while ($r = mysqli_fetch_assoc($allRows)) {
            echo htmlspecialchars(json_encode($r)) . "<br>";
        }
        echo '</div></div>';
    }

    if ($result && $result->num_rows > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            if (!is_array($row)) continue;
            $subject = $row['subject_h'] ?? '';
            $message = $row['message'] ?? '';
            $msg_date = $row['msg_date'] ?? '';
            $msg_time = $row['msg_time'] ?? '';

            // Build sender_display same as before (admin/manager/student)
            $sender_display = 'Unknown';
            $sender_type = $row['sender_type'] ?? null;
            $sender_id = $row['sender_id'] ?? null;

            if ($sender_type === 'admin') {
                $admin_name = null;
                if (!empty($sender_id)) $admin_name = fetch_admin_name($conn, $sender_id);
                if ($admin_name) {
                    $sender_display = htmlspecialchars($admin_name);
                } else {
                    $sender_display = htmlspecialchars($_SESSION['username'] ?? 'Admin');
                    if (!empty($sender_id)) $sender_display .= ' (ID: ' . htmlspecialchars($sender_id) . ')';
                }
            } elseif ($sender_type === 'manager' && !empty($sender_id)) {
                $mgr_display = '';
                $mgr_stmt = safe_prepare($conn, "
                    SELECT hm.Hostel_man_id AS hm_key, h.Hostel_name AS hostel_name
                    FROM Hostel_Manager hm
                    LEFT JOIN Hostel h ON hm.Hostel_id = h.Hostel_id
                    WHERE hm.Hostel_man_id = ?
                    LIMIT 1
                ");
                if ($mgr_stmt) {
                    $mgr_stmt->bind_param("s", $sender_id);
                    $mgr_stmt->execute();
                    $mgr_res = $mgr_stmt->get_result();
                    if ($mgr_res && $mgr_res->num_rows > 0) {
                        $mgr_row = $mgr_res->fetch_assoc();
                        $hostel_name = $mgr_row['hostel_name'] ?? '';
                        $hm_key = $mgr_row['hm_key'] ?? $sender_id;
                        if ($hostel_name !== '') $mgr_display = htmlspecialchars($hostel_name) . ' Hostel Manager';
                        else $mgr_display = 'Hostel Manager (ID: ' . htmlspecialchars($hm_key) . ')';
                    }
                    $mgr_stmt->close();
                }
                $sender_display = $mgr_display ?: ('Hostel Manager (ID: ' . htmlspecialchars($sender_id) . ')');
            } elseif ($sender_type === 'student' && !empty($sender_id)) {
                $st_stmt = safe_prepare($conn, "SELECT Fname, Lname FROM student WHERE Student_id = ? LIMIT 1");
                if ($st_stmt) {
                    $st_stmt->bind_param("s", $sender_id);
                    $st_stmt->execute();
                    $sres = $st_stmt->get_result();
                    if ($sres && $sres->num_rows > 0) {
                        $srow = $sres->fetch_assoc();
                        $full = trim(($srow['Fname'] ?? '') . ' ' . ($srow['Lname'] ?? ''));
                        $sender_display = $full !== '' ? htmlspecialchars($full) : 'Student (ID: ' . htmlspecialchars($sender_id) . ')';
                    } else {
                        $sender_display = 'Student (ID: ' . htmlspecialchars($sender_id) . ')';
                    }
                    $st_stmt->close();
                } else {
                    $sender_display = 'Student (ID: ' . htmlspecialchars($sender_id) . ')';
                }
            } else {
                $fallback_type = $sender_type ? htmlspecialchars($sender_type) : 'Sender';
                $fallback_id = $sender_id ? ' (ID: ' . htmlspecialchars($sender_id) . ')' : '';
                $sender_display = $fallback_type . $fallback_id;
            }
            ?>

            <div class="container">
                <div class="card">
                    <div class="card-header"><b><?php echo htmlspecialchars($subject); ?></b></div>
                    <div class="card-body"><?php echo nl2br(htmlspecialchars($message)); ?></div>
                    <div class="card-footer">
                        <?php echo $sender_display; ?>
                        <span style="float: right">
                            <?php
                            $raw = trim($msg_date . ' ' . $msg_time);
                            if ($raw !== '') {
                                $ts = strtotime($raw);
                                if ($ts !== false) echo htmlspecialchars(date("d-m-Y h:i A", $ts));
                                else echo htmlspecialchars($raw);
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <br><br>

        <?php
        } // end while
    } else {
        echo '<div class="container"><p>No messages found.</p></div>';
    }

    if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
}
?>

<!-- footer & scripts remain unchanged -->
<footer class="py-5">
    <div class="container py-md-5">
        <div class="footer-logo mb-5 text-center"><a class="navbar-brand" href="https://dresut.vercel.app/" target="_blank">ESUT</a></div>
        <div class="footer-grid"><div class="list-footer"><ul class="footer-nav text-center"><li><a href="home.php">Home</a></li><li><a href="services.php">Hostels</a></li><li><a href="contact.php">Contact</a></li><li><a href="profile.php">Profile</a></li></ul></div></div>
    </div>
</footer>

<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
<script src="web_home/js/SmoothScroll.min.js"></script>
<script type="text/javascript" src="web_home/js/move-top.js"></script>
<script type="text/javascript" src="web_home/js/easing.js"></script>
</body>
</html>
