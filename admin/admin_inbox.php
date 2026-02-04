<?php
require '../includes/config.inc.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Admin session value (change if your admin session key is different)
$admin_id = $_SESSION['admin_id'] ?? '1'; // receiver_id in your DB is stored as varchar

// prepare SQL (messages sent to admin)
$stmt = $conn->prepare("
    SELECT msg_id, sender_type, sender_id, receiver_type, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, created_at
    FROM Message
    WHERE receiver_type = 'admin' AND receiver_id = ?
    ORDER BY created_at DESC, msg_date DESC, msg_time DESC
");
if ($stmt) {
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Inbox - H-Track</title>

    <!-- Use same CSS as other pages -->
    <link rel="stylesheet" href="../web_home/css_home/bootstrap.css">
    <link rel="stylesheet" href="../web_home/css_home/style.css" type="text/css" media="all" />
    <link rel="stylesheet" href="../web_home/css_home/fontawesome-all.css">
    <link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">

    <style>
        /* small adjustments for inbox layout */
        body { background: #fff; }
        .inbox-container { padding: 40px 15px; }
        .msg-card { border: 1px solid #e3e3e3; border-radius: 6px; padding: 18px; margin-bottom: 18px; background: #fff; box-shadow: 0 1px 0 rgba(0,0,0,0.02); }
        .msg-subject { font-weight: 700; font-size: 20px; color: #222; margin-bottom: 8px; }
        .msg-meta { color: #666; font-size: 13px; margin-bottom: 12px; }
        .msg-body { color: #333; white-space: pre-wrap; line-height: 1.6; }
        .page-title { margin: 30px 0; font-size: 34px; font-weight: 400; }
        .no-msg { color: #777; padding: 30px 0; }
        /* maintain footer spacing like other pages */
        footer.py-5 { margin-top: 40px; }
    </style>
</head>
<body>

<!-- banner / header (matching your other pages) -->
<div class="inner-page-banner" id="home">
    <header>
        <div class="container agile-banner_nav">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <h1><a class="navbar-brand" href="../admin/admin_home.php">H-Track <span class="display"></span></a></h1>
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item"><a class="nav-link" href="../admin/admin_home.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="create_hm.php">Appoint Hostel Manager</a></li>
                        <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                        <li class="nav-item active"><a class="nav-link" href="admin_inbox.php">Inbox</a></li>
                        <li class="dropdown nav-item">
                            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?> <b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu agile_short_dropdown">
                                <li><a href="admin_profile.php">My Profile</a></li>
                                <li><a href="../includes/logout.inc.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
</div>
<!-- //banner -->

<div class="container inbox-container">
    <h2 class="page-title">Admin Inbox</h2>

    <?php
    if ($res === false) {
        echo '<div class="alert alert-danger">Database error: failed to fetch messages.</div>';
    } else {
        if ($res->num_rows === 0) {
            echo '<div class="no-msg">No messages received yet.</div>';
        } else {
            while ($row = $res->fetch_assoc()) {
                // Basic fields
                $subject = htmlspecialchars($row['subject_h'] ?? '(no subject)');
                $message = nl2br(htmlspecialchars($row['message'] ?? ''));
                $sender_type = htmlspecialchars($row['sender_type'] ?? '');
                $sender_id = htmlspecialchars($row['sender_id'] ?? '');
                $date = htmlspecialchars($row['msg_date'] ?? '');
                $time = htmlspecialchars($row['msg_time'] ?? '');
                // optionally show hostel name if present (best-effort)
                $hostel_label = '';
                if (!empty($row['hostel_id'])) {
                    $hid = $row['hostel_id'];
                    // try quick lookup (no heavy error handling)
                    $hstmt = $conn->prepare("SELECT Hostel_name FROM Hostel WHERE Hostel_id = ? LIMIT 1");
                    if ($hstmt) {
                        $hstmt->bind_param("s", $hid);
                        $hstmt->execute();
                        $hres = $hstmt->get_result();
                        if ($hres && $hres->num_rows) {
                            $hrow = $hres->fetch_assoc();
                            $hostel_label = ' | Hostel: ' . htmlspecialchars($hrow['Hostel_name'] ?? '');
                        }
                        $hstmt->close();
                    }
                }

                echo '<div class="msg-card">';
                echo "<div class=\"msg-subject\">{$subject}</div>";
                echo "<div class=\"msg-meta\">From: <strong>{$sender_type}</strong> (ID: {$sender_id}){$hostel_label} &nbsp; | &nbsp; Date: {$date} &nbsp; Time: {$time}</div>";
                echo "<div class=\"msg-body\">{$message}</div>";
                echo '</div>';
            }
        }
        $res->free();
    }
    if ($stmt) $stmt->close();
    ?>

</div>

<!-- footer (same style as your site) -->
<footer class="py-5">
    <div class="container py-md-5">
        <div class="footer-logo mb-5 text-center">
            <a class="navbar-brand" href="https://dresut.vercel.app/" target="_blank">ESUT<span class="display"></span></a>
        </div>
        <div class="footer-grid">
            <div class="list-footer">
                <ul class="footer-nav text-center">
                    <li><a href="../admin/admin_home.php">Home</a></li>
                    <li><a href="create_hm.php">Appoint</a></li>
                    <li><a href="students.php">Students</a></li>
                    <li><a href="admin_contact.php">Contact</a></li>
                    <li><a href="admin_profile.php">Profile</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- scripts -->
<script type="text/javascript" src="../web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="../web_home/js/bootstrap.js"></script>
<script src="../web_home/js/SmoothScroll.min.js"></script>
<script type="text/javascript" src="../web_home/js/move-top.js"></script>
<script type="text/javascript" src="../web_home/js/easing.js"></script>
</body>
</html>
