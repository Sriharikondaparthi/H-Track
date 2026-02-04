<?php
require '../includes/config.inc.php';
date_default_timezone_set('Asia/Kolkata');
if (session_status() === PHP_SESSION_NONE) session_start();

// Admin -> Student/Manager handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject']) && isset($_POST['message'])) {
    $sender_type = 'admin';
    $sender_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? '0'; // change if your admin session key differs

    $recipient_type = $_POST['recipient_type'] ?? ''; // 'student' or 'manager'
    // New form fields:
    $recipient_manager = trim($_POST['recipient_manager_id'] ?? ''); // manager key from dropdown
    $recipient_roll = trim($_POST['recipient_roll'] ?? ''); // will contain Student_id now
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($recipient_type) || empty($subject) || empty($message)) {
        echo "<script>alert('Please fill all required fields.');</script>";
    } else {
        $today_date = date("Y-m-d");
        $time = date("H:i:s");

        // prepared insert (includes created_at)
        $ins = $conn->prepare(
            "INSERT INTO Message 
             (sender_type, sender_id, receiver_type, receiver_id, subject_h, message, msg_date, msg_time, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        if (!$ins) {
            echo "<script>alert('DB error: " . htmlspecialchars($conn->error) . "');</script>";
            exit;
        }

        if ($recipient_type === 'student') {
            // validate Student_id -> get internal id (we use Student_id as key)
            if ($recipient_roll === '') {
                echo "<script>alert('Please enter the Student ID.');</script>";
                $ins->close();
                exit;
            }

            // Direct lookup using Student_id as the key
            $chk = $conn->prepare("SELECT Student_id FROM student WHERE Student_id = ? LIMIT 1");
            if (!$chk) {
                echo "<script>alert('DB error: " . htmlspecialchars($conn->error) . "');</script>";
                $ins->close();
                exit;
            }

            $chk->bind_param("s", $recipient_roll);
            $chk->execute();
            $res = $chk->get_result();

            if (!$res || $res->num_rows === 0) {
                echo "<script>alert('Student not found with that Student ID.');</script>";
                $chk->close();
                $ins->close();
            } else {
                $row = $res->fetch_assoc();
                $recipient_id = (string)$row['Student_id']; // cast to string to match varchar column
                $chk->close();

                $ins->bind_param(
                    "ssssssss",
                    $sender_type,
                    $sender_id,
                    $recipient_type,
                    $recipient_id,
                    $subject,
                    $message,
                    $today_date,
                    $time
                );

                if ($ins->execute()) {
                    echo "<script>alert('Message sent to student successfully.');</script>";
                } else {
                    echo "<script>alert('Failed to send message.');</script>";
                }
                $ins->close();
            }

        } elseif ($recipient_type === 'manager') {
            // manager selected from dropdown (preferred)
            $selected_mgr = $recipient_manager;

            if ($selected_mgr === '') {
                echo "<script>alert('Please choose a hostel manager from the dropdown.');</script>";
                $ins->close();
                exit;
            }

            // validate the selected manager: first by Hostel_man_id, then by numeric id
            $found = false;

            // 1) match Hostel_man_id (string)
            $chk = $conn->prepare("SELECT Hostel_man_id AS hid FROM Hostel_Manager WHERE Hostel_man_id = ? LIMIT 1");
            if ($chk) {
                $chk->bind_param("s", $selected_mgr);
                $chk->execute();
                $res = $chk->get_result();
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $recipient_id = (string)$row['hid'];
                    $found = true;
                }
                $chk->close();
            }

            // 2) fallback: try numeric primary key id in Hostel_Manager
            if (!$found) {
                $chk2 = $conn->prepare("SELECT id FROM Hostel_Manager WHERE id = ? LIMIT 1");
                if ($chk2) {
                    $chk2->bind_param("s", $selected_mgr);
                    $chk2->execute();
                    $res2 = $chk2->get_result();
                    if ($res2 && $res2->num_rows > 0) {
                        $row2 = $res2->fetch_assoc();
                        $recipient_id = (string)$row2['id'];
                        $found = true;
                    }
                    $chk2->close();
                }
            }

            if (!$found) {
                echo "<script>alert('Selected hostel manager not found.');</script>";
                $ins->close();
            } else {
                // bind and insert
                $ins->bind_param(
                    "ssssssss",
                    $sender_type,
                    $sender_id,
                    $recipient_type,
                    $recipient_id,
                    $subject,
                    $message,
                    $today_date,
                    $time
                );
                if ($ins->execute()) {
                    echo "<script>alert('Message sent to hostel manager successfully.');</script>";
                } else {
                    echo "<script>alert('Failed to send message.');</script>";
                }
                $ins->close();
            }

        } else {
            echo "<script>alert('Invalid recipient type.');</script>";
            $ins->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Admin Contact - H-Track</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<link href="../web_home/css_home/slider.css" type="text/css" rel="stylesheet" media="all">
<link rel="stylesheet" href="../web_home/css_home/bootstrap.css">
<link rel="stylesheet" href="../web_home/css_home/style.css" type="text/css" media="all" />
<link rel="stylesheet" href="../web_home/css_home/fontawesome-all.css">
<link rel="stylesheet" href="../web_home/css_home/flexslider.css" type="text/css" media="screen" property="" />
<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
</head>

<body>
<!-- banner / header (unchanged) -->
<div class="inner-page-banner" id="home">
    <header>
        <div class="container agile-banner_nav">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <h1><a class="navbar-brand" href="admin_home.php">H-Track <span class="display"></span></a></h1>
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                        data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item"><a class="nav-link" href="admin_home.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="create_hm.php">Appoint Hostel Manager</a></li>
                        <li class="nav-item"><a class="nav-link" href="students.php">Students</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin_contact.php">Contact</a></li>
                        <li class="dropdown nav-item">
                            <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
                                <b class="caret"></b>
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

<!-- contact -->
<section class="contact py-5">
    <div class="container">
        <h2 class="heading text-capitalize mb-sm-5 mb-4"> Contact / Send Message </h2>
        <div class="mail_grid_w3l">
            <form action="" method="post">
                <div class="row">
                    <!-- LEFT: recipient & meta -->
                    <div class="col-md-6 contact_left_grid" data-aos="fade-right">

                        <!-- FROM (readonly) -->
                        <div class="contact-fields-w3ls">
                            <input type="text" name="from_admin" value="Admin" readonly style="background:#f7f7f7; font-weight:600;">
                            
                        </div>

                        <!-- Recipient Type -->
                        <div class="contact-fields-w3ls">
                            <label>Recipient Type</label>
                            <select name="recipient_type" id="recipient_type" required>
                                <option value="">-- Select --</option>
                                <option value="student">Student</option>
                                <option value="manager">Hostel Manager</option>
                            </select>
                        </div>

                        <!-- Student ID (only visible when Student selected) -->
                        <div class="contact-fields-w3ls" id="student_roll_wrap" style="display:none;">
                            <input type="text" name="recipient_roll" id="recipient_roll" placeholder="Student ID">
                            <small>Enter Student ID</small>
                        </div>

                        <!-- Manager dropdown grouped by first-letter groups (A..F) -->
                        <div class="contact-fields-w3ls" id="manager_select_wrap" style="display:none;">
                            <label>Choose Hostel Manager</label>
                            <select name="recipient_manager_id" id="recipient_manager_id">
                                <option value="">-- Select manager --</option>

                                <?php
                                // Fetch hostel managers and hostel names
                                $mgrQ = "SELECT hm.Hostel_man_id AS manager_key, h.Hostel_name AS hostel_name
                                         FROM Hostel_Manager hm
                                         LEFT JOIN Hostel h ON hm.Hostel_id = h.Hostel_id
                                         ORDER BY h.Hostel_name, hm.Hostel_man_id";
                                if ($mgrStmt = $conn->prepare($mgrQ)) {
                                    $mgrStmt->execute();
                                    $mgrRes = $mgrStmt->get_result();

                                    // build groups A-F and Others
                                    $groups = [
                                        'A' => [], 'B' => [], 'C' => [], 'D' => [], 'E' => [], 'F' => [], 'Others' => []
                                    ];

                                    while ($mgrRow = $mgrRes->fetch_assoc()) {
                                        $mkey = $mgrRow['manager_key'];
                                        $hname = $mgrRow['hostel_name'] ?? '';
                                        $first = strtoupper(substr(trim($hname), 0, 1));
                                        if (in_array($first, ['A','B','C','D','E','F'], true)) {
                                            $groups[$first][] = ['key'=>$mkey, 'hostel'=>$hname];
                                        } else {
                                            $groups['Others'][] = ['key'=>$mkey, 'hostel'=>$hname];
                                        }
                                    }
                                    $mgrStmt->close();

                                    // output optgroups for A..F then Others
                                    foreach (['A','B','C','D','E','F','Others'] as $g) {
                                        if (count($groups[$g]) === 0) continue;
                                        echo "<optgroup label=\"Hostels ($g)\">";
                                        foreach ($groups[$g] as $item) {
                                            $mkey = htmlspecialchars($item['key']);
                                            $label = htmlspecialchars($item['hostel']) . " (ID: $mkey)";
                                            echo "<option value=\"$mkey\">$label</option>";
                                        }
                                        echo "</optgroup>";
                                    }
                                } else {
                                    // query failed; show helpful fallback
                                    echo "<option value=\"\">No managers found</option>";
                                }
                                ?>
                            </select>
                            
                        </div>

                        <!-- Subject -->
                        <div class="contact-fields-w3ls" style="margin-top:12px;">
                            <input type="text" name="subject" placeholder="Subject" required="">
                        </div>

                    </div>

                    <div class="col-md-6 contact_left_grid" data-aos="fade-left">
                        <div class="contact-fields-w3ls">
                            <textarea name="message" placeholder="Message..." required=""></textarea>
                        </div>
                        <input type="submit" value="Send">
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
<!-- //contact -->

<!-- footer (unchanged) -->
<footer class="py-5">
    <div class="container py-md-5">
        <div class="footer-logo mb-5 text-center">
            <a class="navbar-brand" href="https://dresut.vercel.app/" target="_blank">ESUT<span class="display"></span></a>
        </div>
        <div class="footer-grid">
            <div class="list-footer">
                <ul class="footer-nav text-center">
                    <li><a href="admin_home.php">Home</a></li>
                    <li><a href="create_hm.php">Appoint</a></li>
                    <li><a href="students.php">Students</a></li>
                    <li><a href="admin_contact.php">Contact</a></li>
                    <li><a href="admin_profile.php">Profile</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- js -->
<script type="text/javascript" src="../web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="../web_home/js/bootstrap.js"></script>
<script src="web_home/js/SmoothScroll.min.js"></script>
<script type="text/javascript" src="web_home/js/move-top.js"></script>
<script type="text/javascript" src="web_home/js/easing.js"></script>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        $(".scroll").click(function(event){
            event.preventDefault();
            $('html,body').animate({scrollTop:$(this.hash).offset().top},1000);
        });
    });
</script>
<script type="text/javascript">
    $(document).ready(function () {
        $().UItoTop({easingType: 'easeOutQuart'});
    });
</script>

<!-- small JS to toggle student/manager inputs -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('recipient_type');
    const studentWrap = document.getElementById('student_roll_wrap');
    const managerWrap = document.getElementById('manager_select_wrap');
    const rollInput = document.getElementById('recipient_roll');
    const mgrSelect = document.getElementById('recipient_manager_id');

    function toggleRecipientFields() {
        const val = typeSelect ? typeSelect.value : '';
        if (val === 'student') {
            studentWrap.style.display = 'block';
            managerWrap.style.display = 'none';
            if (rollInput) rollInput.required = true;
            if (mgrSelect) mgrSelect.required = false;
        } else if (val === 'manager') {
            studentWrap.style.display = 'none';
            managerWrap.style.display = 'block';
            if (rollInput) rollInput.required = false;
            if (mgrSelect) mgrSelect.required = true;
        } else {
            studentWrap.style.display = 'none';
            managerWrap.style.display = 'none';
            if (rollInput) rollInput.required = false;
            if (mgrSelect) mgrSelect.required = false;
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', toggleRecipientFields);
        toggleRecipientFields(); // initial
    }
});
</script>

</body>
</html>
