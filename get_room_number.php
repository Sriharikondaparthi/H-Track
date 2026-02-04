<?php
session_start();
require 'includes/config.inc.php';

header('Content-Type: application/json');

if(isset($_POST['roll_no']) && isset($_SESSION['hostel_id'])) {
    $roll_no = mysqli_real_escape_string($conn, $_POST['roll_no']);
    $hostel_id = $_SESSION['hostel_id'];
    
    // Query to get room number for the student
    $query = "SELECT r.Room_No 
              FROM Student s 
              INNER JOIN Room r ON s.Room_id = r.Room_id 
              WHERE s.Student_id = '$roll_no' 
              AND s.Hostel_id = '$hostel_id'
              AND r.Allocated = '1'";
    
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'room_no' => $row['Room_No']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No room allocated for this roll number in your hostel'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>