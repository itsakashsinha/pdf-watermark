<?php
$servername = "localhost";
$username = "root"; // Default for XAMPP
$password = ""; // Default for XAMPP
$database = "courses_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the first PDF from the database
$sql = "SELECT courseId, courseName FROM courses";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Watermark System</title>
</head>
<body>
    <h2>PDF Watermark System</h2>

    <!-- List of PDFs -->
    <ul>
        <?php while ($row = $result->fetch_assoc()): ?>
            <li>
                <a href="watermarkImg.php?id=<?php echo $row['courseId']; ?>">
                    <?php echo htmlspecialchars($row['courseName']); ?>
                </a>
            </li>
        <?php endwhile; ?>
    </ul>

    <!-- PDF Preview -->
    <?php if (isset($_GET['id'])): ?>
        <h3>Watermarked PDF Preview:</h3>
        <iframe src="watermarkImg.php?id=<?php echo $_GET['id']; ?>" width="900" height="900"></iframe>
    <?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
