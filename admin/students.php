<?php
require '../includes/config.inc.php';

// ensure session is started (if config.inc.php doesn't)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// helper to safely echo values
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$conn_error = false;
if (!isset($conn) || !$conn) {
    $conn_error = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<title> Allocated Rooms</title>

	<!-- Meta tag Keywords -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta charset="utf-8">
	<meta name="keywords" content="Intrend Responsive web template, Bootstrap Web Templates, Flat Web Templates, Android Compatible web template,
	Smartphone Compatible web template, free webdesigns for Nokia, Samsung, LG, SonyEricsson, Motorola web design" />
	<script type="application/x-javascript">
		addEventListener("load", function () {
			setTimeout(hideURLbar, 0);
		}, false);
		function hideURLbar() {
			window.scrollTo(0, 1);
		}
	</script>
	<!--bootsrap -->

	<!--// Meta tag Keywords -->

	<!-- css files -->
	<link rel="stylesheet" href="../web_home/css_home/bootstrap.css"> <!-- Bootstrap-Core-CSS -->
	<link rel="stylesheet" href="../web_home/css_home/style.css" type="text/css" media="all" /> <!-- Style-CSS -->
	<link rel="stylesheet" href="../web_home/css_home/fontawesome-all.css"> <!-- Font-Awesome-Icons-CSS -->
	<!-- //css files -->

	<!-- web-fonts -->
	<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese" rel="stylesheet">
	<!-- //web-fonts -->

</head>

<body>

<!-- banner -->
<div class="inner-page-banner" id="home">
	<!--Header-->
	<header>
		<div class="container agile-banner_nav">
			<nav class="navbar navbar-expand-lg navbar-light bg-light">

				<h1><a class="navbar-brand" href="admin_home.php">H-Track <span class="display"> </span></a></h1>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				</button>

				<div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item active">
							<a class="nav-link" href="admin_home.php">Home <span class="sr-only">(current)</span></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="create_hm.php">Appoint Hostel Manager</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="students.php">Students</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="admin_contact.php">Contact</a>
						</li>
			            <li class="dropdown nav-item">
						<a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown"><?php echo isset($_SESSION['username']) ? h($_SESSION['username']) : 'Admin'; ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu agile_short_dropdown">
							<li>
								<a href="admin_profile.php">My Profile</a>
							</li>
							<li>
								<a href="../includes/logout.inc.php">Logout</a>
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
			<div class="mail_grid_w3l">
				<form action="students.php" method="post" class="form-inline">
					<div class="row w-100">
					        <div class="col-md-9">
							<input type="text" class="form-control w-100" placeholder="Search by Roll Number" name="search_box" value="<?php echo isset($_POST['search_box']) ? h($_POST['search_box']) : ''; ?>">
							</div>
							<div class="col-md-3">
							<input type="submit" class="btn btn-primary w-100" value="Search" name="search"></input>
							</div>
					</div>
				</form>
			</div>
	</div>
</section>

<?php
if ($conn_error) {
    echo '<div class="container"><div class="alert alert-danger">Database connection not found. Check <code>config.inc.php</code>.</div></div>';
} else {

    // HANDLE SEARCH using prepared statement and LEFT JOINs
    if (isset($_POST['search'])) {
        $search_box = trim($_POST['search_box'] ?? '');

        // prepared statement with LIKE; add wildcard only if input non-empty
        $sql_search = "SELECT S.*, R.Room_No, H.Hostel_name
                       FROM Student S
                       LEFT JOIN Room R ON S.Room_id = R.Room_id
                       LEFT JOIN Hostel H ON S.Hostel_id = H.Hostel_id
                       WHERE S.Student_id LIKE ?
                       ORDER BY S.Fname, S.Lname
                       LIMIT 500";
        if ($stmt = mysqli_prepare($conn, $sql_search)) {
            $param = $search_box === '' ? '%' : $search_box . '%';
            mysqli_stmt_bind_param($stmt, "s", $param);
            mysqli_stmt_execute($stmt);
            $result_search = mysqli_stmt_get_result($stmt);
        } else {
            // prepare failed
            $result_search = false;
        }

        ?>
        <div class="container">
        <table class="table table-hover">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Student ID</th>
            <th>Contact Number</th>
            <th>Hostel</th>
            <th>Room Number</th>
          </tr>
        </thead>
        <tbody>
        <?php
        if (!$result_search) {
            echo '<tr><td colspan="5">Query failed. Please check database.</td></tr>';
        } elseif (mysqli_num_rows($result_search) === 0) {
            echo '<tr><td colspan="5">No Rows Returned</td></tr>';
        } else {
            while ($row_search = mysqli_fetch_assoc($result_search)) {
                $student_name = trim($row_search['Fname'] . ' ' . $row_search['Lname']);
                $student_id = $row_search['Student_id'] ?? '';
                $mob_no = $row_search['Mob_no'] ?? '';
                $hostel_name = $row_search['Hostel_name'] ?? 'None';
                $room_no = $row_search['Room_No'] ?? 'None';

                echo '<tr>'
                    . '<td>' . h($student_name) . '</td>'
                    . '<td>' . h($student_id) . '</td>'
                    . '<td>' . h($mob_no) . '</td>'
                    . '<td>' . h($hostel_name) . '</td>'
                    . '<td>' . h($room_no) . '</td>'
                    . '</tr>';
            }
        }
        ?>
        </tbody>
        </table>
        </div>
        <?php

        // free stmt if used
        if (isset($stmt) && $stmt) {
            mysqli_stmt_close($stmt);
        }
    } // end search

    // MAIN listing using LEFT JOINs
    $sql_all = "SELECT S.*, R.Room_No, H.Hostel_name
                FROM Student S
                LEFT JOIN Room R ON S.Room_id = R.Room_id
                LEFT JOIN Hostel H ON S.Hostel_id = H.Hostel_id
                ORDER BY S.Fname, S.Lname
                LIMIT 1000";
    $result1 = mysqli_query($conn, $sql_all);
    ?>
    <div class="container">
    <h2 class="heading text-capitalize mb-sm-5 mb-4"> Rooms Allotted </h2>

      <table class="table table-hover">
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Student ID</th>
            <th>Contact Number</th>
            <th>Hostel</th>
            <th>Room Number</th>
          </tr>
        </thead>
        <tbody>
        <?php
        if (!$result1) {
            echo '<tr><td colspan="5">Query failed. Please check database.</td></tr>';
        } elseif (mysqli_num_rows($result1) === 0) {
            echo '<tr><td colspan="5">No Rows Returned</td></tr>';
        } else {
            while ($row1 = mysqli_fetch_assoc($result1)) {
                $student_name = trim($row1['Fname'] . ' ' . $row1['Lname']);
                $student_id = $row1['Student_id'] ?? '';
                $mob_no = $row1['Mob_no'] ?? '';
                $HNM = $row1['Hostel_name'] ?? 'None';
                $room_no = $row1['Room_No'] ?? 'None';

                echo '<tr>'
                    . '<td>' . h($student_name) . '</td>'
                    . '<td>' . h($student_id) . '</td>'
                    . '<td>' . h($mob_no) . '</td>'
                    . '<td>' . h($HNM) . '</td>'
                    . '<td>' . h($room_no) . '</td>'
                    . '</tr>';
            }
        }
        ?>
        </tbody>
      </table>
    </div>

<?php
} // end conn check
?>

<br>
<br>
<br>

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
						<a href="admin_home.php">Home</a>
					</li>

					<li>
						<a href="create_hm.php">Appoint</a>
					</li>
					<li>
						<a href="students.php">Students</a>
					</li>
					<li>
						<a href="admin_contact.php">Contact</a>
					</li>
					<li>
						<a href="admin_profile.php">Profile</a>
					</li>
				</ul>
			</div>

		</div>
	</div>
</footer>
<!-- footer -->

<!-- js-scripts -->

	<!-- js -->
	<script type="text/javascript" src="../web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="../web_home/js/bootstrap.js"></script> <!-- Necessary-JavaScript-File-For-Bootstrap -->
	<!-- //js -->

	<!-- banner js -->
	<script src="web_home/js/snap.svg-min.js"></script>
	<script src="web_home/js/main.js"></script> <!-- Resource jQuery -->
	<!-- //banner js -->

	<!-- flexSlider --><!-- for testimonials -->
	<script defer src="web_home/js/jquery.flexslider.js"></script>
	<script type="text/javascript">
		$(window).load(function(){
		  $('.flexslider').flexslider({
			animation: "slide",
			start: function(slider){
			  $('body').removeClass('loading');
			}
		  });
		});
	</script>
	<!-- //flexSlider --><!-- for testimonials -->

	<!-- start-smoth-scrolling -->
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
	<!-- here stars scrolling icon -->
	<script type="text/javascript">
		$(document).ready(function() {
			$().UItoTop({ easingType: 'easeOutQuart' });
			});
	</script>
	<!-- //here ends scrolling icon -->
	<!-- start-smoth-scrolling -->

<!-- //js-scripts -->

</body>
</html>
