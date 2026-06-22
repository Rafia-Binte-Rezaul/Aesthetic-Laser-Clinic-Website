<?php
// appointmentEdit.php — single-record editor with full fields
require 'config.php';


$errors  = [];
$success = '';
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* Handle update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = (int)($_POST['id'] ?? 0);
    $name         = trim($_POST['name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $contact      = trim($_POST['contact'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $dob          = $_POST['dob'] ?? null;
    $remarks      = trim($_POST['remarks'] ?? '');
    $doctor_id    = (int)($_POST['doctor_id'] ?? 0);
    $booking_slot = (int)($_POST['booking_slot'] ?? 0);

    if ($id <= 0)         $errors[] = 'Missing appointment id.';
    if ($name === '')     $errors[] = 'Patient name is required.';
    if ($email === '')    $errors[] = 'Email is required.';
    if ($contact === '')  $errors[] = 'Contact is required.';
    if (!$dob)            $errors[] = 'Date of birth is required.';
    if ($doctor_id <= 0)  $errors[] = 'Doctor is required.';
    if ($booking_slot<=0) $errors[] = 'Date/Time is required.';

    if (!$errors) {
        $sql = "UPDATE appointments
                   SET name=?, email=?, contact=?, address=?, date_of_birth=?, remarks=?, doctor_id=?, booking_slot=?
                 WHERE id=?";
        $stmt = $conn->prepare($sql);
        $ok = $stmt->bind_param(
            'ssssssiii',
            $name, $email, $contact, $address, $dob, $remarks, $doctor_id, $booking_slot, $id
        ) && $stmt->execute();
        $stmt->close();

        if ($ok) {
            header("Location: appointmentEdit.php?id={$id}&ok=1");
            exit;
        } else {
            $errors[] = 'Failed to update appointment.';
        }
    }
}

/* Load dropdown data (for both list and editor) */
$doctors   = $conn->query("SELECT id, name FROM doctor ORDER BY name");
$schedules = $conn->query("SELECT schedule_id, date_time FROM schedule ORDER BY date_time");

/* If an id is provided, load that single record */
$current = null;
if ($id > 0) {
    $stmt = $conn->prepare("
        SELECT a.*,
               d.name AS doctor_name,
               s.date_time
          FROM appointments a
          JOIN doctor d ON a.doctor_id=d.id
          JOIN schedule s ON a.booking_slot=s.schedule_id
         WHERE a.id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* If no id, show a compact picker list */
$picker = null;
if ($id === 0) {
    $picker = $conn->query("
        SELECT a.id, a.name, a.email, d.name AS doctor_name, s.date_time
          FROM appointments a
          JOIN doctor d   ON a.doctor_id=d.id
          JOIN schedule s ON a.booking_slot=s.schedule_id
         ORDER BY s.date_time ASC
         LIMIT 20
    ");
}

$success = isset($_GET['ok']) ? 'Appointment updated.' : $success;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<!-- <title>Edit Appointment</title> -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  .wrap {max-width:1100px; margin:24px auto;}
  .title-bar {
    background:linear-gradient(90deg, #d3f7f5 0%, #b5ddf8 55%, #c2bdf9 80%, #e790d3 100%);
    border-radius:12px; padding:14px 18px; font-weight:700; font-size:1.25rem;
    color:#223; box-shadow:0 6px 22px rgba(100,120,180,.10);
    margin-bottom:14px;
  }
  .card-lite {border:none; border-radius:14px; box-shadow:0 8px 26px rgba(40,60,120,.07);}
  .table-sm td, .table-sm th {padding:.5rem .6rem;}
  .pill {
    display:inline-block; padding:.18rem .55rem; border-radius:999px;
    font-size:.78rem; background:#eef6ff; color:#246; border:1px solid #dbeafe;
  }
</style>
</head>
<body class="bg-light">
<?php include 'adminNav.php'; ?>

<div class="wrap">
  <div class="title-bar">Edit Appointment</div>

  <?php if ($success): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger py-2">
      <ul class="mb-0"><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <?php if ($id === 0): ?>
    <!-- Picker list (no inline form) -->
    <div class="card card-lite">
      <div class="card-body">
        <h6 class="mb-3">Pick an appointment to edit</h6>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr><th>ID</th><th>Name</th><th>Email</th><th>Date</th><th>Doctor</th><th></th></tr>
            </thead>
            <tbody>
            <?php if ($picker && $picker->num_rows): ?>
              <?php while($r=$picker->fetch_assoc()): ?>
                <tr>
                  <td><span class="pill"><?= (int)$r['id'] ?></span></td>
                  <td><?= htmlspecialchars($r['name']) ?></td>
                  <td><?= htmlspecialchars($r['email']) ?></td>
                  <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['date_time']))) ?></td>
                  <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                  <td><a class="btn btn-sm btn-outline-primary" href="?id=<?= (int)$r['id'] ?>">Edit</a></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6">No data.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <a href="appointmentPanel.php" class="btn btn-outline-secondary">Back</a>
      </div>
    </div>
  <?php else: ?>
    <!-- Full-screen single-record editor -->
    <div class="card card-lite">
      <div class="card-body">
        <h6 class="mb-3">Editing #<?= (int)$current['id'] ?></h6>
        <form method="post" class="row g-3">
          <input type="hidden" name="id" value="<?= (int)$current['id'] ?>">

          <div class="col-md-6">
            <label class="form-label">Patient Name</label>
            <input name="name" class="form-control" value="<?= htmlspecialchars($current['name']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($current['email']) ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Contact</label>
            <input name="contact" class="form-control" value="<?= htmlspecialchars($current['contact']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($current['date_of_birth']) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label">Address</label>
            <input name="address" class="form-control" value="<?= htmlspecialchars($current['address']) ?>">
          </div>

          <div class="col-md-6">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-select" required>
              <option value="">— Select Doctor —</option>
              <?php
              // we must requery doctors because previous result-set may be exhausted by foreach use elsewhere
              $doctors2 = $conn->query("SELECT id, name FROM doctor ORDER BY name");
              while($d=$doctors2->fetch_assoc()): ?>
                <option value="<?= (int)$d['id'] ?>" <?= $current['doctor_id']==$d['id']?'selected':'' ?>>
                  <?= htmlspecialchars($d['name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Date / Time</label>
            <select name="booking_slot" class="form-select" required>
              <option value="">— Select Time —</option>
              <?php
              $schedules2 = $conn->query("SELECT schedule_id, date_time FROM schedule ORDER BY date_time");
              while($s=$schedules2->fetch_assoc()):
                  $label = date('Y-m-d H:i', strtotime($s['date_time'])); ?>
                <option value="<?= (int)$s['schedule_id'] ?>" <?= $current['booking_slot']==$s['schedule_id']?'selected':'' ?>>
                  <?= htmlspecialchars($label) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Remarks</label>
            <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($current['remarks']) ?></textarea>
          </div>

          <div class="col-12">
            <button class="btn btn-primary">Save Changes</button>
            <a href="appointmentEdit.php" class="btn btn-outline-secondary">Back</a>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
