<?php
require 'config.php'; // must define $conn = new mysqli(...)

date_default_timezone_set('Asia/Kuala_Lumpur');

// DEV (comment these 4 lines on prod if you want)
// ini_set('display_errors',1); ini_set('display_startup_errors',1);
// error_reporting(E_ALL); mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --------- Date range (last 60 days default) ----------
$end   = $_GET['end']   ?? date('Y-m-d');
$start = $_GET['start'] ?? date('Y-m-d', strtotime('-60 days'));

$startTs = $start.' 00:00:00';
$endTs   = $end.' 23:59:59';

// --------- 1) Line chart: Appointments over time (per day) ----------
$labels = []; $values = [];
$res = $conn->query("
  SELECT DATE(s.date_time) d, COUNT(*) c
  FROM appointments a
  JOIN schedule s ON s.schedule_id = a.booking_slot
  WHERE s.date_time BETWEEN '$startTs' AND '$endTs'
  GROUP BY DATE(s.date_time)
  ORDER BY DATE(s.date_time)
");
while($r = $res->fetch_assoc()){
  $labels[] = $r['d'];
  $values[] = (int)$r['c'];
}

// --------- 2) Bar chart: Appointments by doctor ----------
$docLabels = []; $docValues = [];
$res = $conn->query("
  SELECT d.name doctor, COUNT(*) c
  FROM appointments a
  JOIN doctor d ON d.id = a.doctor_id
  JOIN schedule s ON s.schedule_id = a.booking_slot
  WHERE s.date_time BETWEEN '$startTs' AND '$endTs'
  GROUP BY d.id
  ORDER BY c DESC
");
while($r=$res->fetch_assoc()){
  $docLabels[] = $r['doctor'];
  $docValues[] = (int)$r['c'];
}

// --------- 3) Doughnut: Slot utilisation in range ----------
$res = $conn->query("SELECT COUNT(*) c FROM schedule s WHERE s.date_time BETWEEN '$startTs' AND '$endTs'");
$totalSlots = (int)($res->fetch_assoc()['c'] ?? 0);

$res = $conn->query("
  SELECT COUNT(*) c
  FROM schedule s
  JOIN appointments a ON a.booking_slot = s.schedule_id
  WHERE s.date_time BETWEEN '$startTs' AND '$endTs'
");
$booked = (int)($res->fetch_assoc()['c'] ?? 0);
$free   = max(0, $totalSlots - $booked);

// JSON for Chart.js
$js_labels     = json_encode($labels);
$js_values     = json_encode($values);
$js_docLabels  = json_encode($docLabels);
$js_docValues  = json_encode($docValues);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Analytics • Skinith</title>
  <link rel="stylesheet" href="css/adminDashboard.css">
  <style>
    /* Page layout kept compact like your cards */
    .page { margin-left: calc(var(--sidebar-w,260px) + 20px); padding: 24px 26px 48px; }
    .ana-head { display:flex; justify-content:space-between; align-items:end; gap:12px; margin-bottom:14px; }
    .range { background:#fff; border:1px solid #edf1f8; border-radius:12px; padding:8px 10px; display:flex; gap:8px; align-items:center; }
    .range input[type=date]{ border:1px solid #e6ecf4; border-radius:10px; padding:8px 10px; }
    .range button{ border:0; background:#3d6bff; color:#fff; border-radius:999px; padding:9px 16px; font-weight:700; cursor:pointer; }
    .grid { display:grid; grid-template-columns: 2fr 1fr; gap:14px; }
    .grid-row { display:grid; grid-template-columns: 1fr; gap:14px; }
    .card h5 { margin:0 0 10px; font-size:16px; }
    .chart { width:100%; height:300px; }
    @media (max-width:1200px){ .grid{ grid-template-columns:1fr; } .chart{ height:260px; } }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
</head>
<body>
  <!-- reuse your existing sidebar -->
  <aside class="sidebar">
    <div class="brand"><img src="img/logo1.png" alt="Skinith"></div>
    <nav class="nav">
      <a href="adminDashboard.php">Dashboard</a>
      <a href="appointmentPanel.php">Appointments</a>
      <a href="doctorPanel.php">Doctors</a>
      <a href="slotPanel.php">Slots</a>
      <a class="active" href="analytics.php">Analytics</a>
      <a href="index.html">Logout</a>
    </nav>
  </aside>

  <main class="page">
    <div class="ana-head">
      <div>
        <h2 style="margin:0 0 4px">Analytics</h2>
        <div style="opacity:.7;font-size:.95rem">Range: <?=htmlspecialchars($start)?> → <?=htmlspecialchars($end)?></div>
      </div>
      <form class="range" method="get" action="analytics.php">
        <label>From</label><input type="date" name="start" value="<?=$start?>">
        <label>To</label><input type="date" name="end" value="<?=$end?>">
        <button>Apply</button>
      </form>
    </div>

    <section class="grid">
      <div class="card">
        <h5>Appointments over time</h5>
        <canvas id="line" class="chart"></canvas>
      </div>
      <div class="card">
        <h5>Slot utilisation</h5>
        <canvas id="doughnut" class="chart"></canvas>
        <div style="margin-top:8px;opacity:.75;font-size:.92rem">
          Booked: <b><?=$booked?></b> &nbsp;•&nbsp; Free: <b><?=$free?></b> &nbsp;•&nbsp; Total: <b><?=$totalSlots?></b>
        </div>
      </div>
      <div class="card" style="grid-column:1 / -1">
        <h5>Appointments by doctor</h5>
        <canvas id="bar" class="chart"></canvas>
      </div>
    </section>
  </main>

  <script>
    const lineLabels = <?=$js_labels?>;
    const lineData   = <?=$js_values?>;
    const barLabels  = <?=$js_docLabels?>;
    const barData    = <?=$js_docValues?>;

    new Chart(document.getElementById('line'), {
      type: 'line',
      data: { labels: lineLabels,
        datasets: [{ label:'Appointments', data: lineData, borderColor:'#3d6bff', backgroundColor:'rgba(61,107,255,.12)', tension:.35, fill:true, pointRadius:2 }]
      },
      options:{ plugins:{legend:{display:false}}, scales:{x:{grid:{display:false}}, y:{beginAtZero:true}} }
    });

    new Chart(document.getElementById('bar'), {
      type: 'bar',
      data: { labels: barLabels, datasets:[{ label:'Appointments', data: barData, backgroundColor:'#10b981' }] },
      options:{ plugins:{legend:{display:false}}, scales:{x:{grid:{display:false}}, y:{beginAtZero:true}} }
    });

    new Chart(document.getElementById('doughnut'), {
      type: 'doughnut',
      data: { labels:['Booked','Free'], datasets:[{ data:[<?=$booked?>, <?=$free?>], backgroundColor:['#8b5cf6','#e5e7ff'], borderWidth:0 }] },
      options:{ cutout:'62%', plugins:{legend:{position:'bottom'}} }
    });
  </script>
</body>
</html>
