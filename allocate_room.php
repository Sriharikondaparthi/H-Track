<?php
// start session immediately
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// require DB config AFTER session started
require 'includes/config.inc.php';

// ensure user is logged in and is a manager
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
<title>Allocate Room - H-Track</title>
	
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
	
	<!-- css files -->
	<link rel="stylesheet" href="web_home/css_home/bootstrap.css">
	<link rel="stylesheet" href="web_home/css_home/style.css" type="text/css" media="all" />
	<link rel="stylesheet" href="web_home/css_home/fontawesome-all.css">
	
	<!-- web-fonts -->
	<link href="//fonts.googleapis.com/css?family=Poiret+One&amp;subset=cyrillic,latin-ext" rel="stylesheet">
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i&amp;subset=cyrillic,cyrillic-ext,greek,greek-ext,latin-ext,vietnamese" rel="stylesheet">
	
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
							<li>
								<a href="empty_rooms.php">Empty Rooms</a>
							</li>
							<li>
								<a href="vacate_rooms.php">Vacate Rooms</a>
							</li>
						</ul>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="message_hostel_manager.php">Messages Received</a>
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
			<div class="mail_grid_w3l">
				<form action="allocate_room.php" method="post">
					<div class="row">
					        <div class="col-md-9"> 
							<input type="text" placeholder="Search by Roll Number" name="search_box" value="<?php echo htmlspecialchars($_POST['search_box'] ?? ''); ?>">
							</div>
							<div class="col-md-3">
							<input type="submit" value="Search" name="search"></input>
							</div>
					</div>
				</form>
			</div>
	</div>
</section>

<?php
// Get hostel id from session (must be present for manager)
if ($hostel_id === null) {
    echo "<script type='text/javascript'>alert('Hostel context missing in session. Please login as a manager.');</script>";
    echo '</body></html>';
    exit;
}
?>

<?php
   if (isset($_POST['search']) && $hostel_id !== null) {
   	   $search_box = trim($_POST['search_box'] ?? '');
   	   $likeParam = $search_box . '%';
   	   $query_search = "SELECT * FROM Application WHERE Student_id LIKE ? AND Hostel_id = ? AND Application_status = '1'";
   	   if ($stmt = $conn->prepare($query_search)) {
   	   	    $stmt->bind_param("ss", $likeParam, $hostel_id);
   	   	    $stmt->execute();
   	   	    $result_search = $stmt->get_result();
   	   } else {
   	   	    $escaped = mysqli_real_escape_string($conn, $search_box);
   	   	    $result_search = mysqli_query($conn,"SELECT * FROM Application WHERE Student_id LIKE '{$escaped}%' AND Hostel_id = '{$hostel_id}' AND Application_status = '1'");
   	   }

       $hostel_name = '';
       if ($hstmt = $conn->prepare("SELECT Hostel_name FROM Hostel WHERE Hostel_id = ? LIMIT 1")) {
           $hstmt->bind_param("s", $hostel_id);
           $hstmt->execute();
           $hres = $hstmt->get_result();
           if ($hres && $hres->num_rows > 0) {
               $hrow = $hres->fetch_assoc();
               $hostel_name = $hrow['Hostel_name'] ?? '';
           }
           $hstmt->close();
       }
   	   ?>
   	   <div class="container">
   	   <table class="table table-hover">
    <thead>
      <tr>
        <th>Student Name</th>
        <th>Student ID</th>
        <th>Hostel</th>
        <th>Message</th>
      </tr>
    </thead>
    <tbody>
    <?php
   	   if (!isset($result_search) || mysqli_num_rows($result_search)==0){
   	   	  echo '<tr><td colspan="4">No Rows Returned</td></tr>';
   	   }
   	   else{
   	   	  while($row_search = mysqli_fetch_assoc($result_search)){
            $student_id = $row_search['Student_id'];
            $student_name = $student_id;
            if ($sstmt = $conn->prepare("SELECT Fname, Lname FROM Student WHERE Student_id = ? LIMIT 1")) {
                $sstmt->bind_param("s", $student_id);
                $sstmt->execute();
                $sres = $sstmt->get_result();
                if ($sres && $sres->num_rows > 0) {
                    $srow = $sres->fetch_assoc();
                    $student_name = trim(($srow['Fname'] ?? '') . ' ' . ($srow['Lname'] ?? ''));
                }
                $sstmt->close();
            }
            
      		echo "<tr><td>".htmlspecialchars($student_name)."</td><td>".htmlspecialchars($row_search['Student_id'])."</td><td>".htmlspecialchars($hostel_name)."</td><td>".htmlspecialchars($row_search['Message'])."</td></tr>\n";
   	   }
   }
   ?>
   </tbody>
  </table>
</div>
<?php
}
  ?>

<div class="container">
<h2 class="heading text-capitalize mb-sm-5 mb-4"> Applications Received </h2>
<?php
   // Detect application table structure
   $app_columns = [];
   $created_at_exists = false;
   $app_pk = null;
   $common_pk_candidates = ['id','ID','application_id','Application_id','ApplicationID','app_id','App_id','ApplicationId','AppID'];

   $colRes = mysqli_query($conn, "SHOW COLUMNS FROM `Application`");
   if ($colRes) {
       while ($crow = mysqli_fetch_assoc($colRes)) {
           $app_columns[] = $crow['Field'];
       }
       $created_at_exists = in_array('created_at', $app_columns, true) || in_array('createdAt', $app_columns, true);
       foreach ($common_pk_candidates as $c) {
           if (in_array($c, $app_columns, true)) {
               $app_pk = $c;
               break;
           }
       }
       if ($app_pk === null && count($app_columns) > 0) {
           $app_pk = $app_columns[0];
       }
   } else {
       $created_at_exists = false;
       $app_pk = 'id';
   }

   $order_col = $created_at_exists ? 'created_at' : $app_pk;

   $query1 = "SELECT * FROM Application WHERE Hostel_id = ? AND Application_status = '1' ORDER BY `$order_col` ASC";
   if ($stmt1 = $conn->prepare($query1)) {
       $stmt1->bind_param("s", $hostel_id);
       $stmt1->execute();
       $res1 = $stmt1->get_result();
   } else {
       $res1 = mysqli_query($conn, "SELECT * FROM Application WHERE Hostel_id = '". mysqli_real_escape_string($conn,$hostel_id) ."' AND Application_status = '1' ORDER BY `".mysqli_real_escape_string($conn,$order_col)."` ASC");
   }

   $hostel_name = '';
   if ($hstmt = $conn->prepare("SELECT Hostel_name FROM Hostel WHERE Hostel_id = ? LIMIT 1")) {
       $hstmt->bind_param("s", $hostel_id);
       $hstmt->execute();
       $hres = $hstmt->get_result();
       if ($hres && $hres->num_rows > 0) {
           $hrow = $hres->fetch_assoc();
           $hostel_name = $hrow['Hostel_name'] ?? '';
       }
       $hstmt->close();
   }
?>
        
  <table class="table table-hover">
    <thead>
      <tr>
        <th>Student Name</th>
        <th>Student ID</th>
        <th>Hostel</th>
        <th>Message</th>
      </tr>
    </thead>
    <tbody>
    <?php
      if (!isset($res1) || mysqli_num_rows($res1)==0){
         echo '<tr><td colspan="4">No Rows Returned</td></tr>';
      }
      else{
      	while($row1 = mysqli_fetch_assoc($res1)){
            $student_id = $row1['Student_id']; 
            $student_name = $student_id;
            if ($sstmt = $conn->prepare("SELECT Fname, Lname FROM Student WHERE Student_id = ? LIMIT 1")) {
                $sstmt->bind_param("s", $student_id);
                $sstmt->execute();
                $sres = $sstmt->get_result();
                if ($sres && $sres->num_rows > 0) {
                    $srow = $sres->fetch_assoc();
                    $student_name = trim(($srow['Fname'] ?? '') . ' ' . ($srow['Lname'] ?? ''));
                }
                $sstmt->close();
            }
            
      		echo "<tr><td>".htmlspecialchars($student_name)."</td><td>".htmlspecialchars($row1['Student_id'])."</td><td>".htmlspecialchars($hostel_name)."</td><td>".htmlspecialchars($row1['Message'])."</td></tr>\n";
      	}
      }
    ?>
    </tbody>
  </table>
</div>

<?php
// Display available rooms info before allocation button
$availCheckQuery = "SELECT COUNT(*) as available_count FROM Room WHERE Hostel_id = ? AND Allocated = '0'";
$availCheckStmt = $conn->prepare($availCheckQuery);
if ($availCheckStmt) {
    $availCheckStmt->bind_param("s", $hostel_id);
    $availCheckStmt->execute();
    $availCheckRes = $availCheckStmt->get_result();
    $availRow = $availCheckRes->fetch_assoc();
    $available_rooms_display = $availRow['available_count'];
    $availCheckStmt->close();
    
    echo '<div class="container"><div class="alert alert-info">';
    echo '<strong>Available Rooms:</strong> ' . $available_rooms_display;
    if ($available_rooms_display == 0) {
        echo ' <span style="color: red;">(No rooms available!)</span>';
        echo ' <a href="diagnose.php" class="btn btn-sm btn-warning" style="margin-left: 10px;">🔧 Fix This Issue</a>';
    }
    echo '</div></div>';
}
?>

<section class="contact py-5">
	<div class="container">
			<div class="mail_grid_w3l">
				<form action="allocate_room.php" method="post">
					<div class="row"> 
							<input type="submit" value="Allocate Rooms" name="submit">
					</div>
				</form>
			</div>
	</div>
</section>

<?php
// FIXED ROOM ALLOCATION LOGIC WITH AUTO-REDIRECT TO DIAGNOSTIC
if (isset($_POST['submit']) && $hostel_id !== null) {
    $in_transaction = false;

    function dbg_alloc($msg) {
        error_log("[ALLOCATE_ROOM] " . $msg);
        echo "<script>console.log('ALLOCATE_ROOM: " . addslashes($msg) . "');</script>";
    }

    try {
        dbg_alloc("=== ALLOCATION STARTED ===");
        dbg_alloc("Hostel ID: {$hostel_id}");

        // Check total rooms for this hostel
        $totalRoomsQuery = "SELECT COUNT(*) as total FROM Room WHERE Hostel_id = ?";
        $totalStmt = $conn->prepare($totalRoomsQuery);
        $totalStmt->bind_param("s", $hostel_id);
        $totalStmt->execute();
        $totalRes = $totalStmt->get_result();
        $totalRow = $totalRes->fetch_assoc();
        $total_rooms = $totalRow['total'];
        $totalStmt->close();
        dbg_alloc("Total rooms in hostel: {$total_rooms}");

        if ($total_rooms == 0) {
            echo "<script>
                alert('ERROR: No rooms exist for your hostel!\\n\\nRedirecting to diagnostic tool to add rooms...');
                window.location.href='diagnose.php';
            </script>";
            exit;
        }

        // Check available rooms
        $availQuery = "SELECT COUNT(*) as available_count FROM Room WHERE Hostel_id = ? AND Allocated = '0'";
        $availStmt = $conn->prepare($availQuery);
        if (!$availStmt) {
            throw new Exception('Prepare availStmt failed: ' . $conn->error);
        }
        $availStmt->bind_param("s", $hostel_id);
        $availStmt->execute();
        $availRes = $availStmt->get_result();
        $availRow = $availRes->fetch_assoc();
        $available_rooms = $availRow['available_count'];
        $availStmt->close();
        
        dbg_alloc("Available rooms (Allocated='0'): {$available_rooms}");

        if ($available_rooms == 0) {
            echo "<script>
                if(confirm('No available rooms found!\\n\\nTotal rooms: {$total_rooms}\\nAvailable: 0\\n\\nAll rooms are marked as allocated.\\n\\nClick OK to open diagnostic tool to fix this, or Cancel to stay here.')) {
                    window.location.href='diagnose.php';
                }
            </script>";
            throw new Exception('No rooms available');
        }

        // Begin transaction
        if (method_exists($conn, 'begin_transaction')) {
            $conn->begin_transaction();
        } else {
            mysqli_autocommit($conn, false);
        }
        $in_transaction = true;

        // Get pending applications
        $appsSql = "SELECT `$app_pk` AS app_pk, Student_id FROM `Application` WHERE Hostel_id = ? AND Application_status = '1' ORDER BY `$order_col` ASC";
        $appsStmt = $conn->prepare($appsSql);
        if (!$appsStmt) throw new Exception('Prepare appsStmt failed: ' . $conn->error);
        $appsStmt->bind_param("s", $hostel_id);
        $appsStmt->execute();
        $appsRes = $appsStmt->get_result();
        $pending_apps = $appsRes->num_rows;
        dbg_alloc("Pending applications: {$pending_apps}");

        if ($pending_apps === 0) {
            if ($in_transaction) {
                if (method_exists($conn, 'commit')) $conn->commit(); else mysqli_commit($conn);
                $in_transaction = false;
            }
            echo "<script>alert('No pending applications to allocate.');</script>";
            $appsStmt->close();
        } else {
            $allocated_count = 0;
            $failed_count = 0;

            while ($appRow = $appsRes->fetch_assoc()) {
                $application_id = $appRow['app_pk'];
                $student_id = $appRow['Student_id'];
                dbg_alloc("Processing application id={$application_id}, student_id={$student_id}");

                // Find available room
                $roomStmt = $conn->prepare("SELECT Room_id, Room_No FROM Room WHERE Allocated = '0' AND Hostel_id = ? ORDER BY Room_No ASC LIMIT 1");
                if (!$roomStmt) throw new Exception('Prepare roomStmt failed: ' . $conn->error);
                $roomStmt->bind_param("s", $hostel_id);
                $roomStmt->execute();
                $roomRes = $roomStmt->get_result();

                if (!$roomRes || $roomRes->num_rows === 0) {
                    dbg_alloc("No more rooms available. Allocated {$allocated_count} applications so far.");
                    $roomStmt->close();
                    break;
                }

                $roomRow = $roomRes->fetch_assoc();
                $room_no = $roomRow['Room_No'];
                $room_id = $roomRow['Room_id'];
                dbg_alloc("Found room_id={$room_id}, room_no={$room_no}");
                $roomStmt->close();

                // Update application
                $updAppSql = "UPDATE `Application` SET Application_status = '0', Room_No = ? WHERE `$app_pk` = ? AND Application_status = '1'";
                $updApp = $conn->prepare($updAppSql);
                if (!$updApp) throw new Exception('Prepare updApp failed: ' . $conn->error);
                
                if (is_numeric($application_id)) {
                    $updApp->bind_param("si", $room_no, $application_id);
                } else {
                    $updApp->bind_param("ss", $room_no, $application_id);
                }
                $updApp->execute();
                
                if ($updApp->affected_rows === 0) {
                    $updApp->close();
                    dbg_alloc("Application update failed for id={$application_id}");
                    $failed_count++;
                    continue;
                }
                $updApp->close();

                // Update student record
                $updStudent = $conn->prepare("UPDATE Student SET Hostel_id = ?, Room_id = ? WHERE Student_id = ?");
                if (!$updStudent) throw new Exception('Prepare updStudent failed: ' . $conn->error);
                $updStudent->bind_param("sis", $hostel_id, $room_id, $student_id);
                $updStudent->execute();
                $updStudent->close();

                // Mark room as allocated
                $updRoom = $conn->prepare("UPDATE Room SET Allocated = '1' WHERE Room_id = ?");
                if (!$updRoom) throw new Exception('Prepare updRoom failed: ' . $conn->error);
                $updRoom->bind_param("i", $room_id);
                $updRoom->execute();
                $updRoom->close();
                
                $allocated_count++;
                dbg_alloc("Successfully allocated room {$room_no} to student {$student_id}");
            }

            $appsStmt->close();

            // Commit transaction
            if ($in_transaction) {
                if (method_exists($conn, 'commit')) $conn->commit(); else mysqli_commit($conn);
                $in_transaction = false;
            }
            
            dbg_alloc("=== ALLOCATION COMPLETED ===");
            dbg_alloc("Allocated: {$allocated_count}, Failed: {$failed_count}");
            
            if ($allocated_count > 0) {
                echo "<script>alert('✓ Successfully allocated {$allocated_count} room(s)!'); window.location.href='allocate_room.php';</script>";
            } else {
                echo "<script>alert('No rooms were allocated. Please check the console for details.');</script>";
            }
        }

    } catch (Exception $e) {
        if ($in_transaction) {
            if (method_exists($conn, 'rollback')) $conn->rollback(); else mysqli_rollback($conn);
            $in_transaction = false;
        }
        
        $err = $e->getMessage();
        error_log("[ALLOCATE_ROOM_EXCEPTION] " . $err);
        dbg_alloc("ERROR: " . $err);
    }
}

// Handle room reset
if (isset($_GET['reset_rooms']) && $_GET['reset_rooms'] == '1' && $hostel_id !== null) {
    $resetStmt = $conn->prepare("UPDATE Room SET Allocated = '0' WHERE Hostel_id = ?");
    if ($resetStmt) {
        $resetStmt->bind_param("s", $hostel_id);
        if ($resetStmt->execute()) {
            $affected = $resetStmt->affected_rows;
            echo "<script>alert('Successfully reset {$affected} rooms to available!'); window.location.href='allocate_room.php';</script>";
        }
        $resetStmt->close();
    }
}
?>
<br><br><br>

<!-- footer -->
<footer class="py-5">
	<div class="container py-md-5">
		<div class="footer-logo mb-5 text-center">
			<a class="navbar-brand" href="https://dresut.vercel.app/" target="_blank">ESUT<span class="display"></span></a>
		</div>
		<div class="footer-grid">
			<div class="list-footer">
				<ul class="footer-nav text-center">
					<li><a href="home_manager.php">Home</a></li>
					<li><a href="allocate_room.php">Allocate</a></li>
					<li><a href="contact_manager.php">Contact</a></li>
					<li><a href="admin/manager_profile.php">Profile</a></li>
				</ul>
			</div>
		</div>
	</div>
</footer>
<!-- footer -->

<!-- js-scripts -->
	<script type="text/javascript" src="web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="web_home/js/bootstrap.js"></script>
	<script src="web_home/js/snap.svg-min.js"></script>
	<script src="web_home/js/main.js"></script>
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
		$(document).ready(function() {
			$().UItoTop({ easingType: 'easeOutQuart' });
		});
	</script>
<!-- //js-scripts -->

</body>
</html>