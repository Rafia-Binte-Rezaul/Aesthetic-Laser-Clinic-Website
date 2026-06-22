<?php
// appointmentDelete.php — delete an appointment (compact UI)
require 'config.php';


$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $errors[] = 'Pick an appointment to delete.';
    } else {
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id=?");
        $ok   = $stmt->bind_param('i', $id) && $stmt->execute();
        $stmt->close();
        if ($ok) $success = 'Appointment deleted.'; else $errors[] = 'Delete failed.';
    }
}

/* small list to keep page light */
$list = $conn->query("
    SELECT a.id, a.name, a.email,
           d.name AS doctor_name,
           s.date_time
      FROM appointments a
      JOIN doctor d   ON a.doctor_id=d.id
      JOIN schedule s ON a.booking_slot=s.schedule_id
     ORDER BY s.date_time ASC
     LIMIT 20
");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Delete Appointment</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  .wrap {max-width:1100px; margin:24px auto;}
  .title-bar {
    background:linear-gradient(90deg,#d3f7f5 0%,#b5ddf8 55%,#c2bdf9 80%,#e790d3 100%);
    border-radius:12px; padding:14px 18px; font-weight:700; font-size:1.25rem;
    color:#223; box-shadow:0 6px 22px rgba(100,120,180,.10);
    margin-bottom:14px;
  }
  .card-lite {border:none; border-radius:14px; box-shadow:0 8px 26px rgba(40,60,120,.07);}
  .table-sm td, .table-sm th {padding:.5rem .6rem;}
  .pill {
    display:inline-block; padding:.18rem .55rem; border-radius:999px;
    font-size:.78rem; background:#fff1f2; color:#8a2432; border:1px solid #ffe4e6;
  }
</style>
</head>
<body class="bg-light">
<?php include 'adminNav.php'; ?>

<div class="wrap">
  <!-- <div class="title-bar">Delete Appointment</div> -->

  <?php if ($success): ?>
    <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger py-2">
      <ul class="mb-0"><?php foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <div class="card card-lite">
    <div class="card-body">
      <h6 class="mb-3">Select one to remove</h6>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th><th>Name</th><th>Email</th><th>Date</th><th>Doctor</th><th></th>
            </tr>
          </thead>
          <tbody>
          <?php if ($list && $list->num_rows): ?>
            <?php while($r=$list->fetch_assoc()): ?>
              <tr>
                <td><span class="pill"><?= (int)$r['id'] ?></span></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['date_time']))) ?></td>
                <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Delete appointment #<?= (int)$r['id'] ?>?');">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </td>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
