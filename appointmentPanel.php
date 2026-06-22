<?php
// simple_admin_table.php
// This file provides a clean appointment management table with gradient column dividers.

require 'config.php';

// Fetch appointments with doctor and schedule details
$sql = "SELECT
            a.id,
            a.name,
            a.email,
            a.contact,
            a.address,
            a.date_of_birth AS dob,
            a.remarks,
            d.name      AS doctor_name,
            s.date_time AS slot_time
        FROM appointments a
        JOIN doctor d   ON a.doctor_id    = d.id
        JOIN schedule s ON a.booking_slot = s.schedule_id
        ORDER BY s.date_time ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Management</title>
    <!-- Load Bootstrap for base styling -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        /* Basic page styling */
        body {
            background-color: #f8f9fa;
            padding: 40px;
            font-family: Arial, Helvetica, sans-serif;
        }
        /* Card container around the table */
        .card {
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
        }
        /* General cell alignment */
        .table th, .table td {
            vertical-align: middle;
        }
        /* Alternate column shading */
        .table tbody tr td:nth-child(odd) {
            background-color: #f7f9fc;
        }
        .table tbody tr td:nth-child(even) {
            background-color: #ffffff;
        }

        /* Layout with sidebar and content area */
        /* Navigation bar gradient */
        .nav-gradient {
            background-image: linear-gradient(90deg, #d3f7f5 0%, #b5ddf8 55%, #c2bdf9 80%, #e790d3 100%);
            border-radius: 8px;
        }
        .nav-link {
            color: #333;
            font-weight: 500;
        }
        .nav-link.active {
            font-weight: bold;
            text-decoration: underline;
        }
        /* Panel header for page title */
        /* Panel header - no longer used */
        .panel-header {
            display: none;
        }
        /* Logo image sizing */
        .logo-img {
            height: 60px;
        }

        /* No caption styling needed since caption removed */

        /* Style the table header row */
        .table thead th {
            background-color: #eef7fc;
            color: #333;
            font-weight: 600;
        }

        /* Alternate row shading for readability */
        .table tbody tr:nth-child(even) td {
            background-color: #f8fbff;
        }
    </style>
</head>
<body>
    <!-- Top navigation bar -->
    <header class="nav-gradient d-flex justify-content-between align-items-center p-3 mb-3">
        <div class="d-flex align-items-center">
            <img src="img/logo1.png" alt="Skinith Logo" class="logo-img" />
        </div>
        <ul class="nav">
            <!-- Appointments dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link active dropdown-toggle" href="#" id="appointmentsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Appointments
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="appointmentsDropdown">
                    <li><a class="dropdown-item" href="appointmentAdd.php">Add Appointment</a></li>
                    <li><a class="dropdown-item" href="appointmentEdit.php">Edit Appointment</a></li>
                    <li><a class="dropdown-item" href="appointmentDelete.php">Delete Appointment</a></li>
                </ul>
            </li>
            <!-- Doctors dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="doctorsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Doctors
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="doctorsDropdown">
                    <li><a class="dropdown-item" href="doctorAdd.php">Add Doctor</a></li>
                    <li><a class="dropdown-item" href="doctorEdit.php">Edit Doctor</a></li>
                    <li><a class="dropdown-item" href="doctorDelete.php">Delete Doctor</a></li>
                </ul>
            </li>
            <!-- Schedules dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="schedulesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Schedules
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="schedulesDropdown">
                    <li><a class="dropdown-item" href="slotPanel.php">Add Schedule</a></li>
                    <li><a class="dropdown-item" href="slotEdit.php">Edit Schedule</a></li>
                    <li><a class="dropdown-item" href="slotDelete.php">Delete Schedule</a></li>
                </ul>
            </li>

            <!-- Schedules dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="schedulesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Dashboard
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="schedulesDropdown">
                    <li><a class="dropdown-item" href="adminDashboard.php">Home Page</a></li>
                   
                </ul>
            </li>
        </ul>
    </header>
    <div class="container-fluid">
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient Name</th>
                            <th>Email</th>
                            <th>Doctor</th>
                            <th>Appointment Time</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Date of Birth</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['doctor_name']) ?></td>
                                <td><?= htmlspecialchars($row['slot_time']) ?></td>
                                <td><?= htmlspecialchars($row['contact']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><?= htmlspecialchars($row['dob']) ?></td>
                                <td><?= htmlspecialchars($row['remarks']) ?></td>
                                
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9">No appointments found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div><!-- /.card -->
    </div><!-- /.container-fluid -->
        <!-- Include Bootstrap JS for dropdowns -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
