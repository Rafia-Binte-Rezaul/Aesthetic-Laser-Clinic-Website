<?php
// doctorDelete.php
require 'config.php';

$deleted = '';
$errors  = [];

// If POST delete with id => delete and show success
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id  = (int)$_POST['id'];

    // Optionally remove image file
    $imgQ = $conn->prepare("SELECT doctor_image FROM doctor WHERE id=?");
    $imgQ->bind_param('i', $id);
    $imgQ->execute();
    $img = $imgQ->get_result()->fetch_assoc()['doctor_image'] ?? '';
    $imgQ->close();

    $stmt = $conn->prepare("DELETE FROM doctor WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        if ($img && is_file(__DIR__ . '/img/doctors/' . $img)) @unlink(__DIR__ . '/img/doctors/' . $img);
        $deleted = 'Doctor deleted.';
    } else {
        $errors[] = 'Delete failed (the doctor may be referenced by appointments).';
    }
}

// If GET id => show a confirm card
$confirmRow = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $r  = $conn->prepare("SELECT id, name, specialist FROM doctor WHERE id=?");
    $r->bind_param('i', $id);
    $r->execute();
    $confirmRow = $r->get_result()->fetch_assoc();
    $r->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Delete Doctor</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<?php include 'adminNav.php'; ?>
<div class="container py-4">

<?php if ($deleted): ?>
  <div class="alert alert-success"><?= htmlspecialchars($deleted) ?></div>
  <a href="doctorPanel.php" class="btn btn-outline-secondary">Back to Doctors</a>

<?php elseif ($confirmRow): ?>
  <h1 class="h4 mb-3">Confirm Delete</h1>
  <div class="card p-3">
    <p>Are you sure you want to delete this doctor?</p>
    <ul>
      <li><strong><?= htmlspecialchars($confirmRow['name']) ?></strong></li>
      <li><?= htmlspecialchars($confirmRow['specialist']) ?></li>
    </ul>
    <form method="post" class="d-inline">
      <input type="hidden" name="id" value="<?= (int)$confirmRow['id'] ?>">
      <button class="btn btn-danger">Yes, delete</button>
    </form>
    <a href="doctorPanel.php" class="btn btn-secondary ms-2">Cancel</a>
  </div>

<?php else: ?>
  <!-- No id => selector list -->
  <h1 class="h3 mb-4">Choose a Doctor to Delete</h1>
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div><?php endif; ?>
  <div class="card p-3">
    <table class="table table-hover">
      <thead><tr><th>#</th><th>Name</th><th>Specialist</th><th></th></tr></thead>
      <tbody>
      <?php
        $q = $conn->query("SELECT id, name, specialist FROM doctor ORDER BY name ASC");
        while ($r = $q->fetch_assoc()):
      ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['specialist']) ?></td>
          <td><a class="btn btn-sm btn-danger" href="doctorDelete.php?id=<?= (int)$r['id'] ?>">Delete</a></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
