<?php
// appointment.php (secure refactor)

// PHP errors (dev only; disable on production)
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// --- DB ---
require_once 'config.php';
$mysqli = $conn;

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  $_SESSION['redirect_to'] = 'appointment.php';
  header("Location: login.php");
  exit;
}

// Fetch logged-in user details to prefill form
$userId = $_SESSION['user_id'];
$userStmt = $mysqli->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userRes = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

$prefilledName = $userRes['name'] ?? '';
$prefilledEmail = $userRes['email'] ?? '';

// --- Helpers ---
function clean($s) { return trim($s ?? ''); }

function send_mail($toEmail, $toName, $subject, $htmlBody, $altBody='') {
  $mail = new PHPMailer(true);
  try {
    // SMTP from constants or environment
    $smtpHost = defined('SMTP_HOST') ? SMTP_HOST : (getenv('SMTP_HOST') ?: 'smtp.gmail.com');
    $smtpUser = defined('SMTP_USER') ? SMTP_USER : (getenv('SMTP_USER') ?: '');
    $smtpPass = defined('SMTP_PASS') ? SMTP_PASS : (getenv('SMTP_PASS') ?: '');
    $smtpPort = (int)(defined('SMTP_PORT') ? SMTP_PORT : (getenv('SMTP_PORT') ?: 465));
    $from     = defined('SMTP_FROM') ? SMTP_FROM : (getenv('SMTP_FROM') ?: $smtpUser);
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : (getenv('SMTP_FROM_NAME') ?: 'Skinith Clinic');

    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    // Use SMTPS (implicit TLS) on 465, STARTTLS on other ports (like 2525 or 587)
    $mail->SMTPSecure = ($smtpPort === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtpPort;

    $mail->setFrom($from, $fromName);
    $mail->addAddress($toEmail, $toName);

    $mail->CharSet  = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    if ($altBody) { $mail->AltBody = $altBody; }

    $mail->send();
    return true;
  } catch (Exception $e) {
    // Optionally log: error_log("Mail error: ".$e->getMessage());
    return false;
  }
}

// --- AJAX: fetch slots by doctor ---
if (isset($_GET['doctor_id'])) {
  header('Content-Type: application/json; charset=utf-8');
  $doctor_id = intval($_GET['doctor_id']);
  $slots = [];

  $stmt = $mysqli->prepare("SELECT schedule_id, date_time FROM schedule WHERE doctor_id = ? ORDER BY date_time ASC");
  $stmt->bind_param('i', $doctor_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) { $slots[] = $row; }
  $stmt->close();

  echo json_encode($slots);
  exit;
}

// --- Handle booking ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name    = clean($_POST['name'] ?? '');
  $email   = clean($_POST['email'] ?? '');
  $doctor  = clean($_POST['doctor'] ?? '');     // format: "<id>--<name>"
  $schedule= clean($_POST['schedule'] ?? '');   // format: "<schedule_id>--><date_time>"
  $contact = clean($_POST['contact'] ?? '');
  $address = clean($_POST['address'] ?? '');
  $dob     = clean($_POST['dob'] ?? '');
  $gender  = clean($_POST['gender'] ?? '');
  $remarks = clean($_POST['remarks'] ?? '');

  // Parse doctor & schedule combo fields safely
  $doctor_parts = explode('--', $doctor, 2);
  $doctor_id    = isset($doctor_parts[0]) ? (int)$doctor_parts[0] : 0;
  $doctor_name  = isset($doctor_parts[1]) ? clean($doctor_parts[1]) : '';

  $schedule_parts = explode('-->', $schedule, 2);
  $schedule_id    = isset($schedule_parts[0]) ? (int)$schedule_parts[0] : 0;
  $schedule_time  = isset($schedule_parts[1]) ? clean($schedule_parts[1]) : '';

  // Basic checks
  $ok = $name && filter_var($email, FILTER_VALIDATE_EMAIL) && $doctor_id > 0 && $schedule_id > 0 && $contact && $dob && $gender;
  if (!$ok) {
    echo "<script>alert('Please complete all required fields correctly.');</script>";
  } else {
    // Optional: ensure schedule exists & is future
    $chk = $mysqli->prepare("SELECT date_time FROM schedule WHERE schedule_id=? AND doctor_id=? LIMIT 1");
    $chk->bind_param('ii', $schedule_id, $doctor_id);
    $chk->execute();
    $chkRes = $chk->get_result();
    if ($row = $chkRes->fetch_assoc()) {
      $schedule_time = $row['date_time']; // trust DB
    } else {
      echo "<script>alert('Invalid schedule selected.');</script>";
      $chk->close();
      goto AFTER_INSERT;
    }
    $chk->close();

    // Insert appointment via prepared statement
    $ins = $mysqli->prepare("INSERT INTO appointments
      (name, email, doctor_id, booking_slot, contact, address, date_of_birth, remarks)
      VALUES (?,?,?,?,?,?,?,?)");
    $ins->bind_param('ssiissss', $name, $email, $doctor_id, $schedule_id, $contact, $address, $dob, $remarks);

    if ($ins->execute()) {
      echo "<script>alert('Appointment booked successfully!');</script>";
      // --- Email: Admin notification ---
      $adminEmail = getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: 'admin@example.com');
      $adminName  = getenv('SMTP_FROM_NAME') ?: 'Admin';

      $adminHtml = "
        <h2 style='color:#167898;margin:0 0 8px'>New Appointment Details</h2>
        <table border='1' cellpadding='8' style='border-collapse:collapse;font-family:Arial,Helvetica,sans-serif'>
          <tr><td><b>Doctor</b></td><td>".htmlspecialchars($doctor_name)."</td></tr>
          <tr><td><b>Date & Time</b></td><td>".htmlspecialchars($schedule_time)."</td></tr>
          <tr><td><b>Name</b></td><td>".htmlspecialchars($name)."</td></tr>
          <tr><td><b>Email</b></td><td>".htmlspecialchars($email)."</td></tr>
          <tr><td><b>Contact</b></td><td>".htmlspecialchars($contact)."</td></tr>
          <tr><td><b>Address</b></td><td>".htmlspecialchars($address)."</td></tr>
          <tr><td><b>DOB</b></td><td>".htmlspecialchars($dob)."</td></tr>
          <tr><td><b>Gender</b></td><td>".htmlspecialchars($gender)."</td></tr>
          <tr><td><b>Remarks</b></td><td>".nl2br(htmlspecialchars($remarks))."</td></tr>
        </table>
      ";
      send_mail($adminEmail, $adminName, 'New Appointment Notification', $adminHtml, strip_tags($adminHtml));

      // --- Email: Patient confirmation ---
      $alt = "Appointment Confirmed\n\n".
             "Dear $name,\n\n".
             "Doctor: $doctor_name\n".
             "Date & Time: $schedule_time\n".
             "Contact: $contact\n".
             "Address: $address\n".
             "Date of Birth: $dob\n".
             "Gender: $gender\n\n".
             "Please arrive 10 minutes early. If you need to reschedule, just reply to this email.\n\n".
             "Skinith Clinic – Kuala Lumpur, Malaysia\n".
             "Phone: +60 11-3317 9934 | Email: info@skinith.com";

      $patHtml = <<<HTML
<div style="font-family: Arial, Helvetica, sans-serif; background-color:#f5f7fb; padding:20px; margin:0;">
  <div style="max-width:640px; margin:0 auto; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 6px 18px rgba(16,24,40,.08);">
    <div style="background:#167898; color:#ffffff; text-align:center; padding:22px 24px;">
      <div style="font-size:22px; font-weight:700; letter-spacing:.3px;">Skinith Clinic</div>
      <div style="font-size:14px; opacity:.95; margin-top:6px;">Appointment Confirmation</div>
    </div>
    <div style="padding:24px 28px; color:#111827;">
      <p style="margin:0 0 12px; font-size:16px;">Dear <strong>{$name}</strong>,</p>
      <p style="margin:0 0 16px; font-size:15px; color:#374151;">
        Thank you for booking your appointment with <strong>Skinith</strong>. Here are your confirmed details:
      </p>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse; font-size:14px;">
        <tr style="background:#f3f4f6;">
          <td style="padding:10px 12px; font-weight:600; width:160px;">Doctor</td>
          <td style="padding:10px 12px;">{$doctor_name}</td>
        </tr>
        <tr>
          <td style="padding:10px 12px; font-weight:600;">Date &amp; Time</td>
          <td style="padding:10px 12px;">{$schedule_time}</td>
        </tr>
        <tr style="background:#f3f4f6;">
          <td style="padding:10px 12px; font-weight:600;">Contact</td>
          <td style="padding:10px 12px;">{$contact}</td>
        </tr>
        <tr>
          <td style="padding:10px 12px; font-weight:600;">Address</td>
          <td style="padding:10px 12px;">{$address}</td>
        </tr>
        <tr style="background:#f3f4f6;">
          <td style="padding:10px 12px; font-weight:600;">Date of Birth</td>
          <td style="padding:10px 12px;">{$dob}</td>
        </tr>
        <tr>
          <td style="padding:10px 12px; font-weight:600;">Gender</td>
          <td style="padding:10px 12px;">{$gender}</td>
        </tr>
      </table>
      <p style="margin:18px 0 10px; font-size:14px; color:#4b5563;">
        Please arrive <strong>10 minutes early</strong>. If you need to reschedule, simply reply to this email.
      </p>
      <div style="margin:18px 0 6px;">
        <a href="https://skinith.com"
           style="display:inline-block; background:#167898; color:#ffffff; text-decoration:none;
                  padding:12px 20px; border-radius:8px; font-size:14px; font-weight:700;">
          Visit Our Website
        </a>
      </div>
    </div>
    <div style="background:#f9fafb; color:#6b7280; text-align:center; font-size:12px; padding:14px 16px;">
      Skinith Clinic • Kuala Lumpur, Malaysia<br>
      Phone: +60 11-3317 9934 &nbsp;|&nbsp; Email: info@skinith.com
    </div>
  </div>
</div>
HTML;

      send_mail($email, $name, 'Your Appointment Confirmation - Skinith', $patHtml, $alt);
    } else {
      echo "<script>alert('Something went wrong. Try again!');</script>";
    }
    $ins->close();
  }

  AFTER_INSERT: ;
}

// Fetch doctor list for the form
$doctors = $mysqli->query("SELECT id, name FROM doctor ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Rezaul Rafia Binte-Final Year Project</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="" name="keywords">
  <meta content="" name="description">

  <link href="img/favicon.ico" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <link href="css/doctors.css" rel="stylesheet">
  <link href="css/navResponsive.css" rel="stylesheet">
</head>

<body>
  <!-- Spinner -->
  <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
      <span class="sr-only">Loading...</span>
    </div>
  </div>

  <!-- Navbar -->
   <nav class="navbar">
  <div class="nav-left">
    <div class="nav-brand">
       <img src="img/logo.png" alt="Skinith Logo" class="logo-img" />
    </div>
  </div>

  <div class="nav-center">
    <div class="nav-links">
      <a href="index.html" >Home</a>
      <a href="about.html" >About Us</a>
      <a href="treatment.html" >Treatments</a>
      <a href="doctors.php" >Doctors</a>
      <a href="contact.html" >Contact</a>
    </div>
  </div>

  <div class="nav-right">
    <a href="appointment.php" class="book-btn">BOOK APPOINTMENT</a>
    <a href="https://wa.me/601133179934" target="_blank" class="whatsapp-nav">
      <i class="fab fa-whatsapp" style="font-size:32px; color:#12c661;"></i>
    </a>
  </div>
</nav>

  <!-- Form -->
  <div class="form-container">
    <h2>Schedule a Consultation</h2>
    <form method="POST" action="">
      <input type="text" name="name" placeholder="Your full name" value="<?= htmlspecialchars($prefilledName) ?>" required>
      <input type="email" name="email" placeholder="Your Email" value="<?= htmlspecialchars($prefilledEmail) ?>" required>
      <select name="doctor" id="doctor-select" required>
        <option value="" disabled selected>Select a doctor</option>
        <?php while ($doc = $doctors->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($doc['id'].'--'.$doc['name']) ?>">
            <?= htmlspecialchars($doc['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <select name="schedule" id="schedule-select" required>
        <option value="" disabled selected>Appointment slot</option>
      </select>
      <input type="text" name="address" placeholder="Your full Address" required>
      <input type="tel" name="contact" placeholder="+(60)11xxxxxxx" required>
      <input type="date" name="dob" placeholder="Date of Birth" required>
      <select name="gender" required>
        <option value="" disabled selected>Gender</option>
        <option>Male</option>
        <option>Female</option>
        <option>Other</option>
      </select>
      <textarea class="full-width" name="remarks" placeholder="Any notes or questions…"></textarea>
      <button class="s full-width" type="submit">Submit</button>
    </form>
  </div>

  <!-- JS -->
  <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="lib/wow/wow.min.js"></script>
  <script src="lib/easing/easing.min.js"></script>
  <script src="lib/waypoints/waypoints.min.js"></script>
  <script src="lib/owlcarousel/owl.carousel.min.js"></script>
  <script src="js/main.js"></script>

  <script>
    window.addEventListener('load', function () {
      document.getElementById('spinner').style.display = 'none';
    });

    document.getElementById("doctor-select").addEventListener("change", function() {
      const doctorId = this.value.split("--")[0];
      const scheduleSelect = document.getElementById("schedule-select");
      scheduleSelect.innerHTML = '<option value="" disabled selected>Loading…</option>';
      fetch("appointment.php?doctor_id=" + encodeURIComponent(doctorId))
        .then(res => res.json())
        .then(data => {
          scheduleSelect.innerHTML = '';
          if(Array.isArray(data) && data.length > 0){
            data.forEach(slot => {
              const opt = document.createElement("option");
              opt.value = String(slot.schedule_id) + "-->" + String(slot.date_time);
              opt.textContent = slot.date_time;
              scheduleSelect.appendChild(opt);
            });
          } else {
            scheduleSelect.innerHTML = '<option disabled>No slots</option>';
          }
        })
        .catch(() => {
          scheduleSelect.innerHTML = '<option disabled>Error loading slots</option>';
        });
    });
  </script>

  <!-- Footer (unchanged from your file) -->
  <div class="footer-wave" style="margin:0; padding:0;">
    <svg viewBox="0 0 1440 100" width="100%" height="100" preserveAspectRatio="none" style="display:block;">
      <defs>
        <linearGradient id="footer-gradient" x1="0" y1="0" x2="1" y2="0" gradientUnits="objectBoundingBox">
          <stop offset="0%" stop-color="#8268bc"/>
          <stop offset="100%" stop-color="#e6bada"/>
        </linearGradient>
      </defs>
      <path d="M0,50 Q360,0 720,50 T1440,50 L1440,100 L0,100 Z" fill="url(#footer-gradient)" />
    </svg>
  </div>

  <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
    <div class="container py-5">
      <div class="row g-5">
        <div class="col-lg-3 col-md-6">
          <h4 class="text-white mb-3">Quick Links</h4>
          <a class="btn btn-link" href="about.html">About Us</a>
          <a class="btn btn-link" href="contact.html">Contact</a>
          <a class="btn btn-link" href="treatments.html">Treatments</a>
          <a class="btn btn-link" href="faqs.html">FAQs</a>
          <a class="btn btn-link" href="appointment.html">Book Appointment</a>
          <div class="d-flex pt-2">
            <a class="btn btn-social btn-facebook" href="#"><i class="fab fa-facebook-f"></i></a>
            <a class="btn btn-social btn-instagram" href="#"><i class="fab fa-instagram"></i></a>
            <a class="btn btn-social btn-tiktok" href="#"><i class="fab fa-tiktok"></i></a>
            <a class="btn btn-social btn-whatsapp" href="#"><i class="fab fa-whatsapp"></i></a>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <h4 class="text-white mb-3">Contact</h4>
          <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Skinith, Kuala Lumpur, Malaysia</p>
          <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+60 11-3317 9934</p>
          <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@skinith.com</p>
        </div>
        <div class="col-lg-3 col-md-6">
          <h4 class="text-white mb-3">Gallery</h4>
          <div class="row g-2 pt-2">
            <div class="col-4"><img class="img-fluid gallery-img" src="img/course-1.jpg" alt=""></div>
            <div class="col-4"><img class="img-fluid gallery-img" src="img/course-2.jpg" alt=""></div>
            <div class="col-4"><img class="img-fluid gallery-img" src="img/course-3.jpg" alt=""></div>
            <div class="col-4"><img class="img-fluid gallery-img" src="img/course-2.jpg" alt=""></div>
            <div class="col-4"><img class="img-fluid gallery-img" src="img/course-3.jpg" alt=""></div>
            <div class="col-4"><img class="img-fluid gallery-img" src="img/course-1.jpg" alt=""></div>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <h4 class="text-white mb-3">Newsletter</h4>
          <p>Subscribe to receive exclusive updates on beauty tips and skin treatment offers.</p>
          <div class="position-relative mx-auto" style="max-width: 400px;">
            <input class="form-control border-0 w-100 py-3 ps-4 pe-5" type="text" placeholder="Your email">
            <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">Subscribe</button>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="copyright">
        <div class="row">
          <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
            &copy; <a class="border-bottom" href="#">Skinith</a>, All Rights Reserved.<br><br>
            Designed by <a class="border-bottom" href="#">Rafia Binte Rezaul</a>
          </div>
          <div class="col-md-6 text-center text-md-end">
            <div class="footer-menu">
              <a href="index.html">Home</a>
              <a href="treatments.html">Treatments</a>
              <a href="contact.html">Contact</a>
              <a href="faqs.html">FAQs</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
<?php $mysqli->close(); ?>
