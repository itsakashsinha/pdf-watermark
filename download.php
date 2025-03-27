<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "courses_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $courseId = $_GET['id'];
    $stmt = $conn->prepare("SELECT courseContent FROM courses WHERE courseId = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($pdfData);
    $stmt->fetch();

    if ($pdfData) {
        header("Content-type: application/pdf");
        header("Content-Disposition: attachment; filename=course.pdf");
        echo $pdfData;
    } else {
        echo "No file found.";
    }

    $stmt->close();
}

$conn->close();
?>
