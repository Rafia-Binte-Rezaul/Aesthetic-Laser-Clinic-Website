<?php
require 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$result = $conn->query("SELECT id, name, specialist, doctor_image FROM doctor ORDER BY name ASC");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Doctors (View Only)</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    .doc-thumb{width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid #eee;}
  </style>
</head>
<body class="bg-light">
<?php include 'adminNav.php'; ?>

<div class="container py-4">
  <h1 class="h3 mb-4">Doctors (View Only)</h1>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:60px;">#</th>
            <th>Doctor Name</th>
            <th>Specialist</th>
            <th>Photo</th>
          </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['specialist'] ?? '') ?></td>
            <td>
              <?php
                $img = trim((string)$row['doctor_image']);
                $path = $img ? "img/doctors/$img" : '';
                if ($img && is_file($path)): ?>
                  <img class="doc-thumb" src="<?= htmlspecialchars($path) ?>" alt="">
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
