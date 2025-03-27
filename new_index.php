<?php
session_start();

// Enhanced login check using session flag
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: new_login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$database = "courses_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch course names and file URLs
$sql = "SELECT courseName, filename, fileurl FROM courses";
$result = $conn->query($sql);

$courses = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .course-list {
            list-style: none;
            padding: 0;
        }
        .course-item {
            background: #f4f4f4;
            margin: 10px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .course-item a {
            color: #0066cc;
            text-decoration: none;
            font-weight: bold;
        }
        .course-item a:hover {
            text-decoration: underline;
        }
        .course-name {
            font-size: 1.2em;
            margin-bottom: 5px;
        }
        .file-name {
            color: #555;
        }
        .welcome-message {
            font-size: 1.3em;
            margin-bottom: 20px;
            padding: 10px;
            background: #e7f3fe;
            border-radius: 5px;
        }
        .user-info {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="welcome-message">
        Welcome, <?php 
        echo htmlspecialchars($_SESSION['firstname']) . ' ' . 
             htmlspecialchars($_SESSION['lastname']); 
        ?>
    </div>
    
    <div class="user-info">
        <p>You have successfully logged in.</p>
        <p>Email: <?php echo htmlspecialchars($_SESSION['email']); ?></p>
    </div>

    <h1>Course Materials</h1>
    
    <!-- In your course list loop in finalIndex_new.php -->
    <!-- In your course list loop -->
    <ul class="course-list">
        <?php foreach ($courses as $course): ?>
            <li class="course-item">
                <div class="course-name"><?php echo htmlspecialchars($course['courseName']); ?></div>
                <div class="file-name">
                    <a href="new_watermark.php?pdfUrl=<?php echo urlencode($course['fileurl']); ?>&actualFilename=<?php echo urlencode($course['filename']); ?>&email=<?php echo urlencode($_SESSION['email']); ?>" target="_blank">
                        <?php echo htmlspecialchars($course['filename']); ?>
                    </a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>