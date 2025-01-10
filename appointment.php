<?php
session_start(); // Start the session

// Check if student_id is set in the session
if (!isset($_SESSION['student_id'])) {
    die("Error: student_id is not set in the session.");
}

$student_id = $_SESSION['student_id']; // Get student_id from the session
$student_id = trim($student_id); // Trim any leading or trailing spaces

// Database credentials
$host = "localhost";
$user = "root";
$pass = "";
$db = "fyp";

// Database connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for advisor and availability data
$advisor_id = '';
$advisor_name = '';
$availability_data = [];

if (isset($_SESSION['student_name']) && isset($_SESSION['student_id'])) {
    // Fetch the assigned advisor for the student
    $query = "SELECT a.advisor_id, a.advisor_name 
              FROM advisor a 
              JOIN advisor_student ast ON a.advisor_id = ast.advisor_id 
              WHERE ast.student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $advisor = $result->fetch_assoc();
    $stmt->close();

    if ($advisor) {
        $advisor_id = $advisor['advisor_id'];
        $advisor_name = $advisor['advisor_name'];

        // Fetch the availability of the assigned advisor
        $query = "SELECT advisor_id, advisor_name, available_date, available_time 
                  FROM availability 
                  WHERE advisor_id = ? AND is_booked = 0 
                  ORDER BY available_date, available_time";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $advisor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $availability_data[] = $row;
        }
        $stmt->close();
    }
}

$conn->close();

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    // Retrieve student details from session
    $student_name = $_SESSION['student_name'];
    $student_id = $_SESSION['student_id'];
    $advisor_id = $_POST['advisor_id'];
    $advisor_name = $_POST['advisor_name'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];

    // Database connection
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert into appointments table
    $query = "INSERT INTO appointments (student_name, student_id, advisor_id, advisor_name, appointment_date, appointment_time) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssss", $student_name, $student_id, $advisor_id, $advisor_name, $appointment_date, $appointment_time);
    if ($stmt->execute()) {
        echo "<script>alert('Appointment successfully booked!');</script>";
    } else {
        echo "<script>alert('Error booking appointment.');</script>";
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Advisor Availability</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        header {
            background: #A32753;
            color: white;
            text-align: center;
            padding: 15px;
            font-size: 1.5rem;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            height: 50px;
            margin-right: 10px;
        }

        nav {
            margin-top: 10px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 900px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        table th {
            background-color: #A32753;
            color: white;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        button {
            padding: 8px 15px;
            background-color: #A32753;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #801E40;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #A32753;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
        }

        .back-btn:hover {
            background-color: #801E40;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="logouniten.png" alt="Logo">
            <span>EduConsult - Advisor Availability</span>
        </div>
        <nav>
            <a href="mainpage.html">Dashboard</a>
            <a href="appointment.php">Appointments</a>
            <a href="#">History</a>
            <a href="studprofile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <div class="container">
        <a href="mainpage.html" class="back-btn">Back to Main Page</a> <!-- Back to Main Page Button -->
        <h2>Available Advisors</h2>
        <table>
            <thead>
                <tr>
                    <th>Advisor ID</th>
                    <th>Advisor Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($availability_data)): ?>
                    <tr>
                        <td colspan="5">You are not assigned to any advisor yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($availability_data as $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($data['advisor_id']); ?></td>
                        <td><?php echo htmlspecialchars($data['advisor_name']); ?></td>
                        <td><?php echo htmlspecialchars($data['available_date']); ?></td>
                        <td><?php echo htmlspecialchars($data['available_time']); ?></td>
                        <td>
                            <!-- Appointment Booking Form -->
                            <form method="POST">
                                <input type="hidden" name="advisor_id" value="<?php echo $data['advisor_id']; ?>">
                                <input type="hidden" name="advisor_name" value="<?php echo $data['advisor_name']; ?>">
                                <input type="hidden" name="appointment_date" value="<?php echo $data['available_date']; ?>">
                                <input type="hidden" name="appointment_time" value="<?php echo $data['available_time']; ?>">
                                <button type="submit" name="book_appointment">Book Appointment</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>