<?php
// doctorEdit.php
require 'config.php';


$success = '';
$errors  = [];

// --- MODE A: Handle update when id + POST ---
if (isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = (int)$_GET['id'];
    $name       = trim($_POST['name'] ?? '');
    $specialist = trim($_POST['specialist'] ?? '');
    $newImage   = $_FILES['doctor_image']['name'] ?? '';

    if ($name === '') $errors[] = 'Doctor name is required.';

    if (!$errors) {
        // optional image upload
        $imgColSql = '';
        $imgParam  = '';
        if (!empty($newImage) && is_uploaded_file($_FILES['doctor_image']['tmp_name'])) {
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/','_', basename($newImage));
            $targetDir = __DIR__ . '/img/doctors/';
            if (!is_dir($targetDir)) { @mkdir($targetDir, 0777, true); }
            $target = $targetDir . $safeName;

            if (move_uploaded_file($_FILES['doctor_image']['tmp_name'], $target)) {
                $imgColSql = ", doctor_image = ?";
                $imgParam  = $safeName;
            } else {
                $errors[] = 'Failed to save uploaded image.';
            }
        }

        if (!$errors) {
            if ($imgColSql) {
                $sql  = "UPDATE doctor SET name = ?, specialist = ? $imgColSql WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssi', $name, $specialist, $imgParam, $id);
            } else {
                $sql  = "UPDATE doctor SET name = ?, specialist = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssi', $name, $specialist, $id);
            }
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) $success = 'Doctor updated.';
            else     $errors[] = 'Update failed.';
        }
    }
}

// --- MODE B: Load doctor by id (for form) ---
$editing = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $res = $conn->prepare("SELECT * FROM doctor WHERE id = ?");
    $res->bind_param('i', $id);
    $res->execute();
    $editing = $res->get_result()->fetch_assoc();
    $res->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Doctor</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<?php include 'adminNav.php'; ?>
<div class="container py-4">

<?php if ($editing): ?>
  <h1 class="h3 mb-4">Edit Doctor</h1>

  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="row g-3 bg-white p-3 rounded shadow-sm">
    <div class="col-md-6">
      <label class="form-label">Doctor Name</label>
      <input name="name" class="form-control" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">Specialist</label>
      <input name="specialist" class="form-control" value="<?= htmlspecialchars($editing['specialist'] ?? '') ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Photo (optional)</label>
      <input type="file" name="doctor_image" class="form-control">
      <?php
        $img = $editing['doctor_image'] ?? '';
        if ($img && is_file(__DIR__ . '/img/doctors/' . $img)) {
            echo '<div class="mt-2"><img src="img/doctors/' . htmlspecialchars($img) . '" style="height:64px;border-radius:8px;border:1px solid #eee"></div>';
        }
      ?>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Save Changes</button>
      <a href="doctorPanel.php" class="btn btn-outline-secondary">Back</a>
    </div>
  </form>

<?php else: ?>
  <!-- No id => selector list -->
  <h1 class="h3 mb-4">Choose a Doctor to Edit</h1>
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
          <td><a class="btn btn-sm btn-primary" href="doctorEdit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
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
