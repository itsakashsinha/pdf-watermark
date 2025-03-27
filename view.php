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

$sql = "SELECT courseId, courseName FROM courses";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Courses</title>
</head>
<body>

    <h2>Available Courses</h2>
    <table border="1">
        <tr>
            <th>Course Name</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['courseName']; ?></td>
                <td><a href="download.php?id=<?php echo $row['courseId']; ?>">Download PDF</a></td>
            </tr>
        <?php } ?>
    </table>

</body>
</html>

<?php $conn->close(); ?>
