<?php
// admin_create.php
require 'config.php';
$active = 'appointments';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $name   = trim($_POST['name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $dob     = $_POST['dob'] ?? null;
    $remarks = trim($_POST['remarks'] ?? '');
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $booking_slot = (int)($_POST['booking_slot'] ?? 0);

    if ($name === '')   $errors[] = 'Patient name is required.';
    if ($email === '')  $errors[] = 'Email is required.';
    if ($contact === '')$errors[] = 'Contact is required.';
    if ($doctor_id <= 0)$errors[] = 'Doctor is required.';
    if ($booking_slot <= 0)$errors[] = 'Appointment time is required.';
    if ($dob === null || $dob === '') $errors[] = 'Date of birth is required.';

    if (!$errors) {
        $sql = "INSERT INTO appointments
                (name, email, contact, address, date_of_birth, remarks, doctor_id, booking_slot)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $ok = $stmt->bind_param('ssssssii',
            $name, $email, $contact, $address, $dob, $remarks, $doctor_id, $booking_slot
        ) && $stmt->execute();

        if ($ok) {
            $success = "Appointment created (ID: {$stmt->insert_id}).";
            // reset form
            $name=$email=$contact=$address=$dob=$remarks='';
            $doctor_id=$booking_slot=0;
        } else {
            $errors[] = 'Failed to create appointment.';
        }
        $stmt->close();
    }
}

// Fetch doctors & schedules for selects
$doctors = $conn->query("SELECT id, name FROM doctor ORDER BY name ASC");
$schedules = $conn->query("SELECT schedule_id, date_time FROM schedule ORDER BY date_time ASC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add Appointment</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<!-- Navbar -->
<?php include 'adminNav.php'; ?>
<div class="container py-4">
  <h1 class="h3 mb-4">Add Appointment</h1>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" class="row g-3 bg-white p-3 rounded shadow-sm">
    <div class="col-md-6">
      <label class="form-label">Patient Name</label>
      <input name="name" class="form-control" value="<?= htmlspecialchars($name ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Contact</label>
      <input name="contact" class="form-control" value="<?= htmlspecialchars($contact ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Date of Birth</label>
      <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($dob ?? '') ?>" required>
    </div>
    <div class="col-12">
      <label class="form-label">Address</label>
      <input name="address" class="form-control" value="<?= htmlspecialchars($address ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Doctor</label>
      <select name="doctor_id" class="form-select" required>
        <option value="">— Select Doctor —</option>
        <?php while($d = $doctors->fetch_assoc()): ?>
          <option value="<?= $d['id'] ?>" <?= !empty($doctor_id)&&$doctor_id==$d['id']?'selected':'' ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Appointment Time</label>
      <select name="booking_slot" class="form-select" required>
        <option value="">— Select Time —</option>
        <?php while($s = $schedules->fetch_assoc()):
          $label = date('Y-m-d H:i', strtotime($s['date_time'])); ?>
          <option value="<?= $s['schedule_id'] ?>" <?= !empty($booking_slot)&&$booking_slot==$s['schedule_id']?'selected':'' ?>>
            <?= htmlspecialchars($label) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Remarks</label>
      <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($remarks ?? '') ?></textarea>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Create Appointment</button>
      <a href="simple_admin_table.php" class="btn btn-outline-secondary">Back</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
