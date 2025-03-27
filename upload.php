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

if (isset($_POST['upload'])) {
    $courseName = $_POST['courseName'];
    $pdf = $_FILES['courseContent']['tmp_name'];

    if ($_FILES['courseContent']['error'] !== UPLOAD_ERR_OK) {
        die("File upload error. Please try again.");
    }

    $pdfData = file_get_contents(filename: $pdf);

    $stmt = $conn->prepare("INSERT INTO courses (courseName, courseContent) VALUES (?, ?)");
    $null = NULL;  // Placeholder for binary data
    $stmt->bind_param("sb", $courseName, $null);
    $stmt->send_long_data(1, $pdfData); // Send actual binary data

    if ($stmt->execute()) {
        echo "PDF uploaded successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Course PDF</title>
</head>
<body>

    <h2>Upload Course PDF</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label>Course Name:</label>
        <input type="text" name="courseName" required>
        <br><br>
        <label>Select PDF:</label>
        <input type="file" name="courseContent" accept="application/pdf" required>
        <br><br>
        <button type="submit" name="upload">Upload</button>
    </form>

</body>
</html>
