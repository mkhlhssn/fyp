<?php
session_start();

// Assumes advisor_id and advisor_name are stored in the session
$advisor_id = $_SESSION['advisor_id'] ?? '';
$advisor_name = $_SESSION['advisor_name'] ?? '';

// Optional: Validate the session variables or fetch from the database if not set
if (empty($advisor_name)) {
    $conn = new mysqli("localhost", "root", "", "fyp");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT advisor_name FROM advisor WHERE advisor_id = ?");
    $stmt->bind_param("s", $advisor_id);
    $stmt->execute();
    $stmt->bind_result($retrieved_name);
    if ($stmt->fetch()) {
        $advisor_name = $retrieved_name;
        $_SESSION['advisor_name'] = $advisor_name; // Store in session for future use
    }
    $stmt->close();
    $conn->close();
}

// Fetch upcoming appointments for the advisor
$conn = new mysqli("localhost", "root", "", "fyp");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$upcoming_appointments = [];
$stmt = $conn->prepare("SELECT id, student_name, appointment_date, appointment_time, status FROM appointments WHERE advisor_id = ? ORDER BY appointment_date, appointment_time");
$stmt->bind_param("s", $advisor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $upcoming_appointments[] = $row;
}
$stmt->close();
$conn->close();

// Debugging: Log the upcoming appointments
error_log("Upcoming Appointments: " . print_r($upcoming_appointments, true));

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_done'])) {
    $appointment_id = $_POST['appointment_id'];

    $conn = new mysqli("localhost", "root", "", "fyp");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("UPDATE appointments SET status = 'done' WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    if ($stmt->execute()) {
        echo "<script>alert('Appointment marked as done successfully!');</script>";
    } else {
        echo "<script>alert('Error marking appointment as done.');</script>";
    }
    $stmt->close();
    $conn->close();

    // Refresh the page to reflect changes
    header("Location: advismainpage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduConsult - Advisor Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f4f9;
            color: #000;
            min-height: 100vh;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #fff;
            border-bottom: 2px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .logo img {
            margin-right: 10px;
            width: 50px;
            height: 50px;
        }

        nav {
            display: flex;
            gap: 30px;
        }

        nav a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            transition: color 0.3s;
        }

        nav a:hover {
            color: #A32753;
        }

        .container {
            padding: 40px;
        }

        h1 {
            font-size: 2.5rem;
            color: #A32753;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        table th {
            background-color: #A32753;
            color: #fff;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            margin-top: 10px;
            background: #A32753;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #801E40;
        }

        .status-done {
            color: green;
            font-weight: bold;
        }

        .status-pending {
            color: orange;
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 15px;
            background-color: #333;
            color: #fff;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <img src="logouniten.png" alt="Logo">
            <span>EduConsult - Advisor Dashboard</span>
        </div>
        <nav>
            <a href="advisavailability.php">Appointments</a>
            <a href="history.php">History</a>
            <a href="advisprofile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Overview Section -->
        <section class="section">
            <h1>Welcome, <?php echo htmlspecialchars($advisor_name); ?>!</h1>
            <p>Manage your appointments, track student advisories, and organize schedules.</p>
        </section>

        <!-- Upcoming Appointments -->
        <section class="section">
            <h2>📅 Upcoming Appointments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($upcoming_appointments)): ?>
                        <tr>
                            <td colspan="5">No upcoming appointments.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($upcoming_appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                <td>
                                    <span class="<?php echo ($appointment['status'] == 'done') ? 'status-done' : 'status-pending'; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($appointment['status'] == 'pending'): ?>
                                        <form method="POST" action="advismainpage.php">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" name="mark_done" class="btn">Mark as Done</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="status-done">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Manage Schedule -->
        <section class="section">
            <h2>🗓 Manage Your Schedule</h2>
            <p>Click below to organize your availability.</p>
            <a href="advisavailability.php" class="btn">Manage Schedule</a>
        </section>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 EduConsult. All Rights Reserved.</p>
    </footer>
</body>
</html>