<?php
require 'includes/config.inc.php';
date_default_timezone_set('Asia/Kolkata');
if (session_status() === PHP_SESSION_NONE) session_start();

// manager session / hostel context
$manager_id = $_SESSION['hostel_man_id'] ?? $_SESSION['manager_id'] ?? '';
// cast hostel_id to int (safer for DB integer column)
$hostel_id = isset($_SESSION['hostel_id']) ? (int) $_SESSION['hostel_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject']) && isset($_POST['message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $recipient_type = trim($_POST['recipient_type'] ?? '');
    $recipient_roll = trim($_POST['recipient_roll'] ?? '');

    if ($subject === '' || $message === '' || $recipient_type === '') {
        echo "<script>alert('Please fill required fields.');</script>";
    } else {
        $today_date = date("Y-m-d");
        $time = date("H:i:s");
        $sender_type = 'manager';
        $sender_id = $manager_id;

        $ins = $conn->prepare(
            "INSERT INTO Message 
             (sender_type, sender_id, receiver_type, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        if (!$ins) {
            // show prepare error for debugging
            $err = $conn->error;
            echo "<script>alert('DB Error: failed to prepare statement. Error: " . htmlspecialchars($err) . "');</script>";
        } else {
            // make sure all bound params are variables (no literals)
            $admin_id = 1; // admin id as variable
            // ensure receiver_id and hostel_id variables exist before bind
            if ($recipient_type === 'admin') {
                $receiver_id = $admin_id; // integer

                // bind variables (hostel_id is an int)
                $ins->bind_param(
                    "ssssissss",
                    $sender_type,
                    $sender_id,
                    $recipient_type,
                    $receiver_id,
                    $hostel_id,
                    $subject,
                    $message,
                    $today_date,
                    $time
                );

                if ($ins->execute()) {
                    echo "<script>alert('Message sent to Admin!');</script>";
                } else {
                    $err = $ins->error;
                    echo "<script>alert('Failed to send to Admin. Error: " . htmlspecialchars($err) . "');</script>";
                }
                $ins->close();

            } elseif ($recipient_type === 'student') {
                if ($recipient_roll === '') {
                    echo "<script>alert('Please enter student roll/ID.');</script>";
                    $ins->close();
                    exit;
                }

                // verify student exists
                $chk = $conn->prepare("SELECT Student_id FROM Student WHERE Student_id = ? LIMIT 1");
                if (!$chk) {
                    echo "<script>alert('DB Error (chk prepare): " . htmlspecialchars($conn->error) . "');</script>";
                    $ins->close();
                    exit;
                }
                $chk->bind_param("s", $recipient_roll);
                $chk->execute();
                $cres = $chk->get_result();
                if (!$cres || $cres->num_rows === 0) {
                    echo "<script>alert('Student not found.');</script>";
                    $chk->close();
                    $ins->close();
                    exit;
                }
                $crow = $cres->fetch_assoc();
                $receiver_id = $crow['Student_id'];
                $chk->close();

                // bind using variables (no literals)
                $ins->bind_param(
                    "ssssissss",
                    $sender_type,
                    $sender_id,
                    $recipient_type,
                    $receiver_id,
                    $hostel_id,
                    $subject,
                    $message,
                    $today_date,
                    $time
                );

                if ($ins->execute()) {
                    echo "<script>alert('Message sent to Student!');</script>";
                } else {
                    $err = $ins->error;
                    echo "<script>alert('Failed to send message. Error: " . htmlspecialchars($err) . "');</script>";
                }
                $ins->close();

            } else {
                echo "<script>alert('Invalid recipient type.');</script>";
                $ins->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Reply Students / Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="utf-8">
<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
</head>

<body>

<!-- banner -->
<div class="inner-page-banner" id="home"> 	   
	<!--Header-->
	<header>
		<div class="container agile-banner_nav">
			<nav class="navbar navbar-expand-lg navbar-light bg-light">
				
				<h1><a class="navbar-brand" href="home_manager.php">H-Track<span class="display"></span></a></h1>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item">
							<a class="nav-link" href="home_manager.php">Home <span class="sr-only">(current)</span></a>
						</li>
						<li class="nav-item">
						<a class="nav-link" href="allocate_room.php">Allocate Room</a>
					<li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown">Rooms
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li>
								<a href="allocated_rooms.php">Allocated Rooms</a>
							</li>
							<li class="nav-item">
						<a class="nav-link" href="message_hostel_manager.php">Messages Received</a>
					</li>
							<li>
								<a href="empty_rooms.php">Empty Rooms</a>
							</li>
							<li>
								<a href="vacate_rooms.php">Vacate Rooms</a>
							</li>
						</ul>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="contact_manager.php">Contact</a>
					</li>
					<li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li>
								<a href="admin/manager_profile.php">My Profile</a>
							</li>
							<li>
								<a href="includes/logout.inc.php">Logout</a>
							</li>
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

<section class="contact py-5">
	<div class="container">

        <h2 class="heading text-capitalize mb-sm-5 mb-4"> Reply Students / Admin </h2>

        <div class="mail_grid_w3l">
            <form action="contact_manager.php" method="post">
                <div class="row">
                    <div class="col-md-6 contact_left_grid" data-aos="fade-right">
                        <div class="contact-fields-w3ls">
                            <input type="text" name="from" placeholder="Name" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" readonly>
                        </div>

                        <div class="contact-fields-w3ls">
                            <label>Recipient Type</label>
                            <select name="recipient_type" id="recipient_type" required>
                                <option value="">-- Select --</option>
                                <option value="student">Student</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="contact-fields-w3ls" id="student_wrap" style="display:none;">
                            <input type="text" name="recipient_roll" id="recipient_roll" placeholder="Student Roll Number (leave blank for Admin)">
                            <small>Enter student roll (if sending to student). Leave blank when sending to Admin.</small>
                        </div>

                        <div class="contact-fields-w3ls">
                            <input type="text" name="subject" placeholder="Subject" required="">
                        </div>
                    </div>

                    <div class="col-md-6 contact_left_grid" data-aos="fade-left">
                        <div class="contact-fields-w3ls">
                            <textarea name="message" placeholder="Message..." required=""></textarea>
                        </div>
                        <input type="submit" name="submit" value="Send">
                    </div>
                </div>
            </form>
        </div>

	</div>
</section>

<!-- footer -->
<footer class="py-5">
	<div class="container py-md-5">
		<div class="footer-logo mb-5 text-center">
			<a class="navbar-brand"  href="https://dresut.vercel.app/" target="_blank" >ESUT<span class="display"></span></a>
		</div>
		<div class="footer-grid">
			
			<div class="list-footer">
				<ul class="footer-nav text-center">
					<li>
						<a href="home_manager.php">Home</a>
					</li>
					<li>
						<a href="allocate_room.php">Allocate</a>
					</li>
					
					<li>
						<a href="contact_manager.php">Contact</a>
					</li>
					<li>
						<a href="admin/manager_profile.php">Profile</a>
					</li>
				</ul>
			</div>
			
		</div>
	</div>
</footer>
<!-- footer -->

<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const sel = document.getElementById('recipient_type');
    const stud = document.getElementById('student_wrap');
    const roll = document.getElementById('recipient_roll');
    if (sel) {
        sel.addEventListener('change', function(){
            if (this.value === 'student') {
                stud.style.display = 'block';
                if (roll) roll.required = true;
            } else {
                stud.style.display = 'none';
                if (roll) roll.required = false;
            }
        });
    }
});
</script>
</body>
</html>
