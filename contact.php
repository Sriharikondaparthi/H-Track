<?php
require 'includes/config.inc.php';
date_default_timezone_set('Asia/Kolkata');
if (session_status() === PHP_SESSION_NONE) session_start();

// ensure student session (adjust session key if needed)
$student_roll = $_SESSION['roll'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject']) && isset($_POST['message'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $recipient_type = trim($_POST['recipient_type'] ?? '');
    $hostel_name = trim($_POST['hostel_name'] ?? '');

    if ($subject === '' || $message === '' || $recipient_type === '') {
        echo "<script>alert('Please fill required fields.');</script>";
    } else {
        $today_date = date("Y-m-d");
        $time = date("H:i:s");
        $sender_type = 'student';
        $sender_id = $student_roll; // we store Student roll/id as sender

        $ins = $conn->prepare(
            "INSERT INTO Message 
             (sender_type, sender_id, receiver_type, receiver_id, hostel_id, subject_h, message, msg_date, msg_time, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );

        if (!$ins) {
            echo "<script>alert('DB Error: failed to prepare statement.');</script>";
            // optional debug: error_log($conn->error);
        } else {
            if ($recipient_type === 'admin') {
                $receiver_type = 'admin';
                $receiver_id = '1'; // admin id (string) — change if your admin id differs
                $hostel_id_val = null;

                $ins->bind_param(
                    "ssssissss",
                    $sender_type,
                    $sender_id,
                    $receiver_type,
                    $receiver_id,
                    $hostel_id_val,
                    $subject,
                    $message,
                    $today_date,
                    $time
                );

                if ($ins->execute()) {
                    echo "<script>alert('Message sent to Admin!');</script>";
                } else {
                    echo "<script>alert('Failed to send message to Admin.');</script>";
                }
                $ins->close();

            } elseif ($recipient_type === 'manager') {
                // hostel name required
                if ($hostel_name === '') {
                    echo "<script>alert('Please enter the hostel name to reach its manager.');</script>";
                    $ins->close();
                    exit;
                }

                // find hostel id
                $stmtH = $conn->prepare("SELECT Hostel_id FROM Hostel WHERE Hostel_name = ? LIMIT 1");
                $stmtH->bind_param("s", $hostel_name);
                $stmtH->execute();
                $resH = $stmtH->get_result();
                if (!$resH || $resH->num_rows === 0) {
                    echo "<script>alert('Hostel not found. Please enter exact hostel name.');</script>";
                    $stmtH->close();
                    $ins->close();
                    exit;
                }
                $hrow = $resH->fetch_assoc();
                $hostel_id_val = $hrow['Hostel_id'];
                $stmtH->close();

                // find hostel manager (prefer Hostel_man_id)
                $stmtM = $conn->prepare("SELECT Hostel_man_id FROM Hostel_Manager WHERE Hostel_id = ? LIMIT 1");
                $stmtM->bind_param("s", $hostel_id_val);
                $stmtM->execute();
                $resM = $stmtM->get_result();
                if (!$resM || $resM->num_rows === 0) {
                    echo "<script>alert('No hostel manager found for that hostel.');</script>";
                    $stmtM->close();
                    $ins->close();
                    exit;
                }
                $mrow = $resM->fetch_assoc();
                $receiver_id = $mrow['Hostel_man_id'];
                $stmtM->close();

                $receiver_type = 'manager';

                $ins->bind_param(
                    "ssssissss",
                    $sender_type,
                    $sender_id,
                    $receiver_type,
                    $receiver_id,
                    $hostel_id_val,
                    $subject,
                    $message,
                    $today_date,
                    $time
                );

                if ($ins->execute()) {
                    echo "<script>alert('Message sent to Hostel Manager!');</script>";
                } else {
                    echo "<script>alert('Failed to send message to Hostel Manager.');</script>";
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
<title>Contact Us - Student</title>
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
				
				<h1><a class="navbar-brand" href="home.php">H-Track <span class="display"></span></a></h1>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item">
							<a class="nav-link" href="home.php">Home <span class="sr-only">(current)</span></a>
						</li>
						
						<li class="nav-item">
							<a class="nav-link" href="services.php">Hostels</a>
						</li>
						<li class="nav-item active">
							<a class="nav-link" href="contact.php">Contact</a>
						</li>
						<li class="nav-item">
						<a class="nav-link" href="message_user.php">Message Received</a>
					</li>
						<li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo htmlspecialchars($_SESSION['roll'] ?? ''); ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li>
								<a href="profile.php">My Profile</a>
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

<!-- contact -->
<section class="contact py-5">
	<div class="container">
		<h2 class="heading text-capitalize mb-sm-5 mb-4"> Contact / Send Message </h2>
			<div class="mail_grid_w3l">
				<form action="contact.php" method="post">
					<div class="row">
						<div class="col-md-6 contact_left_grid" data-aos="fade-right">
							<div class="contact-fields-w3ls">
								<input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')); ?>" readonly>
							</div>

							<div class="contact-fields-w3ls">
								<label>Recipient Type</label>
								<select name="recipient_type" id="recipient_type" required>
									<option value="">-- Select --</option>
									<option value="manager">Hostel Manager</option>
									<option value="admin">Admin</option>
								</select>
							</div>

							<div class="contact-fields-w3ls" id="hostel_wrap" style="display:none;">
								<input type="text" name="hostel_name" placeholder="Hostel Name (exact)">
								<small>Enter exact hostel name (matching Hostel table) to reach manager.</small>
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
<!-- //contact -->

<!-- footer -->
<footer class="py-5">
	<div class="container py-md-5">
		<div class="footer-logo mb-5 text-center">
			<a class="navbar-brand" href="https://dresut.vercel.app/" target="_blank">ESUT<span class="display"></span></a>
		</div>
		<div class="footer-grid">
			
			<div class="list-footer">
				<ul class="footer-nav text-center">
					<li>
						<a href="home.php">Home</a>
					</li>
					<li>
						<a href="services.php">Hostels</a>
					</li>
					
					<li>
						<a href="contact.php">Contact</a>
					</li>
					<li>
						<a href="profile.php">Profile</a>
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
    const hostWrap = document.getElementById('hostel_wrap');
    if (sel) {
        sel.addEventListener('change', function(){
            if (this.value === 'manager') hostWrap.style.display = 'block';
            else hostWrap.style.display = 'none';
        });
    }
});
</script>
</body>
</html>
