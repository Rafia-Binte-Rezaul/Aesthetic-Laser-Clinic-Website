<?php
// doctorAdd.php (CREATE)
require 'config.php';

// Show useful DB errors during dev
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$errors = [];
$success = '';
$name = $specialist = '';
$imgName = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $specialist = trim($_POST['specialist'] ?? '');

    if ($name === '') $errors[] = 'Doctor name is required.';

    // Handle optional image upload
    if (!empty($_FILES['doctor_image']['name'])) {
        $allowed = ['jpg','jpeg','png','gif','webp','avif'];
        $ext = strtolower(pathinfo($_FILES['doctor_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Image must be jpg, jpeg, png, gif, webp or avif.';
        } elseif ($_FILES['doctor_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
        } else {
            // Save into img/doctors/
            if (!is_dir('img/doctors')) mkdir('img/doctors', 0777, true);
            $imgName = 'doc_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $target = __DIR__ . '/img/doctors/' . $imgName;
            if (!move_uploaded_file($_FILES['doctor_image']['tmp_name'], $target)) {
                $errors[] = 'Unable to save uploaded image.';
                $imgName = null;
            }
        }
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "INSERT INTO doctor (name, specialist, doctor_image) VALUES (?, ?, ?)"
        );
        // NULLs are ok; they’ll insert as NULL
        $stmt->bind_param('sss', $name, $specialist, $imgName);
        $stmt->execute();

        $success = "Doctor added (ID: {$stmt->insert_id}).";
        $name = $specialist = '';
        $imgName = null;
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Add Doctor</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<?php include 'adminNav.php'; ?>

<div class="container py-4">
  <h1 class="h3 mb-4">Add Doctor</h1>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0"><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="row g-3 bg-white p-3 rounded shadow-sm">
    <div class="col-12">
      <label class="form-label">Doctor Name</label>
      <input name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
    </div>

    <div class="col-12">
      <label class="form-label">Specialist (optional)</label>
      <input name="specialist" class="form-control" value="<?= htmlspecialchars($specialist) ?>">
    </div>

    <div class="col-12">
      <label class="form-label">Doctor Image (optional)</label>
      <input type="file" name="doctor_image" class="form-control">
      <small class="text-muted">jpg, jpeg, png, gif, webp, avif</small>
    </div>

    <div class="col-12">
      <button class="btn btn-primary">Add Doctor</button>
      <a href="doctors.php" class="btn btn-outline-secondary">Back</a>
    </div>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
