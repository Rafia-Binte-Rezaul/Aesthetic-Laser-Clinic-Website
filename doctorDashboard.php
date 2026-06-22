<?php
// doctorDashboard.php — dynamic doctor dashboard for Skinith (admin-style hero)

error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Always use clinic timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Which doctor?  Fall back to 1 if none specified
$doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;

// Filters from query string
$search   = trim($_GET['q'] ?? '');
$dateFrom = $_GET['from'] ?? '';
$dateTo   = $_GET['to'] ?? '';

// Connect to database
require_once __DIR__ . '/config.php';

// Fetch doctor info
$docStmt = $conn->prepare("SELECT id,name,specialist,doctor_image FROM doctor WHERE id=?");
$docStmt->bind_param("i",$doctorId);
$docStmt->execute();
$doctor = $docStmt->get_result()->fetch_assoc();

// Image paths: use doctor photo when available, otherwise use your logo
$imgBase   = "img/";
$logoImage = "logo1.png";
$doctorImg = !empty($doctor['doctor_image']) ? $imgBase.$doctor['doctor_image'] : $imgBase.$logoImage;

/* =========================
   Insert a new slot (validated)
   ========================= */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='add_slot') {
    $d  = $_POST['slot_date'] ?? '';
    $t  = $_POST['slot_time'] ?? '';

    try {
        // Build DateTime from date + time
        $dt = DateTime::createFromFormat('Y-m-d H:i', "$d $t", new DateTimeZone('Asia/Kuala_Lumpur'));
        if (!$dt) {
            throw new Exception('Invalid date or time.');
        }

        // Must be in the future (allow ~60s grace)
        $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
        $minFuture = (clone $now)->modify('+60 seconds');
        if ($dt <= $minFuture) {
            throw new Exception('Slot must be in the future.');
        }

        // Prevent duplicates for same doctor/date_time
        $dtSql = $dt->format('Y-m-d H:i:s');
        $chk = $conn->prepare("SELECT COUNT(*) c FROM schedule WHERE doctor_id=? AND date_time=?");
        $chk->bind_param("is", $doctorId, $dtSql);
        $chk->execute();
        $exists = (int)$chk->get_result()->fetch_assoc()['c'];
        if ($exists > 0) {
            throw new Exception('This slot already exists.');
        }

        // Insert
        $ins = $conn->prepare("INSERT INTO schedule(doctor_id,date_time) VALUES(?,?)");
        $ins->bind_param("is",$doctorId,$dtSql);
        $ins->execute();

        header("Location: doctorDashboard.php?doctor_id={$doctorId}&slot_added=1");
        exit;
    } catch (Exception $e) {
        $msg = urlencode($e->getMessage());
        header("Location: doctorDashboard.php?doctor_id={$doctorId}&slot_error={$msg}");
        exit;
    }
}

/* =========================
   Appointment query (with filters)
   ========================= */
$sql   = "SELECT a.id,a.name,a.email,a.contact,s.date_time AS appt_dt
          FROM appointments a
          JOIN schedule s ON a.booking_slot=s.schedule_id
          WHERE a.doctor_id=?";
$params = [$doctorId]; $types = "i";
if ($search !== '') {
    $sql .= " AND (a.name LIKE CONCAT('%', ?, '%') OR a.email LIKE CONCAT('%', ?, '%'))";
    $params[] = $search; $params[] = $search; $types .= "ss";
}
if ($dateFrom !== '') { $sql .= " AND DATE(s.date_time) >= ?"; $params[]=$dateFrom; $types.="s"; }
if ($dateTo   !== '') { $sql .= " AND DATE(s.date_time) <= ?"; $params[]=$dateTo;   $types.="s"; }
$sql .= " ORDER BY s.date_time ASC";
$appStmt = $conn->prepare($sql);
$appStmt->bind_param($types, ...$params);
$appStmt->execute();
$apps = $appStmt->get_result();

/* =========================
   KPIs
   ========================= */
$todayStmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM appointments a
    JOIN schedule s ON a.booking_slot=s.schedule_id
    WHERE a.doctor_id=? AND DATE(s.date_time)=CURDATE()
");
$todayStmt->bind_param("i",$doctorId); $todayStmt->execute();
$kpiToday = (int)($todayStmt->get_result()->fetch_assoc()['c'] ?? 0);

$upcomingStmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM appointments a
    JOIN schedule s ON a.booking_slot=s.schedule_id
    WHERE a.doctor_id=? AND s.date_time>=NOW()
");
$upcomingStmt->bind_param("i",$doctorId); $upcomingStmt->execute();
$kpiUpcoming = (int)($upcomingStmt->get_result()->fetch_assoc()['c'] ?? 0);

$freeStmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM schedule s
    LEFT JOIN appointments a ON a.booking_slot=s.schedule_id
    WHERE s.doctor_id=? AND s.date_time>=NOW() AND a.id IS NULL
");
$freeStmt->bind_param("i",$doctorId); $freeStmt->execute();
$kpiFree = (int)($freeStmt->get_result()->fetch_assoc()['c'] ?? 0);

/* =========================
   Next 6 upcoming appts
   ========================= */
$nextStmt = $conn->prepare("
    SELECT a.name,a.email,a.contact,s.date_time
    FROM appointments a
    JOIN schedule s ON a.booking_slot=s.schedule_id
    WHERE a.doctor_id=? AND s.date_time>=NOW()
    ORDER BY s.date_time ASC
    LIMIT 6
");
$nextStmt->bind_param("i",$doctorId);
$nextStmt->execute();
$nextApps = $nextStmt->get_result();

/* =========================
   Next 10 slots
   ========================= */
$slotStmt = $conn->prepare("
    SELECT s.date_time, CASE WHEN a.id IS NULL THEN 'available' ELSE 'booked' END AS slot_status
    FROM schedule s
    LEFT JOIN appointments a ON a.booking_slot=s.schedule_id
    WHERE s.doctor_id=? AND s.date_time>=NOW()
    ORDER BY s.date_time ASC
    LIMIT 10
");
$slotStmt->bind_param("i",$doctorId);
$slotStmt->execute();
$slots = $slotStmt->get_result();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Doctor Dashboard | Skinith</title>
<link rel="stylesheet" href="css/adminDashboard.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  .cal-grid .day.selected {
    background: #711491 !important;
    color: #fff !important;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(113, 20, 145, 0.3);
  }
  .cal-grid .day {
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .cal-grid .day:hover {
    background: #eef2ff;
  }
  .add-slot-form input[type="date"],
  .add-slot-form input[type="time"] {
    outline: none;
    border: 1px solid #cfe0ff;
    transition: border-color 0.2s;
  }
  .add-slot-form input[type="date"]:focus,
  .add-slot-form input[type="time"]:focus {
    border-color: #711491;
  }
  .topgrid .avatar img {
    object-position: top center; /* Focus on the head/face instead of cropping from center */
  }
</style>
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">
      <img src="img/logo1.png" alt="Skinith">
    </div>
    <nav class="nav">
      <a class="active" href="doctorDashboard.php?doctor_id=<?= (int)$doctorId ?>">Dashboard</a>
      <a href="index.html">Home</a>
      <a href="doctors.php">Profiles</a>
      <a href="treatment.html">Services</a>
      <a href="contact.html">Location</a>
      <a href="login.php">Logout</a>

      <div class="footer">
        <div class="doctor-switch">
          <small style="display:block; margin-bottom:6px; font-weight:600; color:#2b3b4e;">Switch Doctor</small>
          <form method="get" action="doctorDashboard.php">
            <input type="number" name="doctor_id" value="<?= (int)$doctorId ?>" style="width:100%; height:38px; border-radius:10px; border:1px solid #cfe0ff; padding: 0 12px; margin-bottom: 8px; outline:none;">
            <button type="submit" style="width:100%; height:38px; border-radius:10px; border:none; background:#711491; color:#fff; font-weight:700; cursor:pointer;">Load</button>
          </form>
        </div>
      </div>
    </nav>
  </aside>

  <!-- Main Panel -->
  <main class="main">

    <!-- ADMIN-STYLE HERO (Topband) -->
    <?php
      // Better greeting
      $hour = (int)date('G'); // 0..23
      if ($hour >= 5 && $hour <= 11)       { $greeting = 'Good morning,'; }
      elseif ($hour <= 16)                 { $greeting = 'Good afternoon,'; }
      elseif ($hour <= 20)                 { $greeting = 'Good evening,'; }
      else                                 { $greeting = 'Good night,'; }

      // Clean name so it doesn't already start with Dr/Dr.
      $nameRaw   = trim($doctor['name'] ?? ('Doctor #'.$doctorId));
      $nameClean = preg_replace('/^\s*Dr\.?\s*/i', '', $nameRaw);
    ?>
    <section class="topband">
      <div class="topgrid">
        <div class="avatar">
          <img src="<?= htmlspecialchars($doctorImg) ?>" onerror="this.src='img/logo1.png';" alt="Doctor">
        </div>

        <div class="hello">
          <h3 style="margin:0; font-size:22px; color:#0b2035; font-weight:800;">Dr. <?= htmlspecialchars($nameClean) ?></h3>
          <div style="opacity:.75; font-size:14px; margin-top:4px; color:#2b3b4e; font-weight:600;">
            <?= htmlspecialchars($doctor['specialist'] ?? '') ?> · Kuala Lumpur
            <?php if(!empty($_GET['slot_error'])): ?>
              <small class="text-danger d-block mt-1" style="color:red; font-weight:600;"><?= htmlspecialchars($_GET['slot_error']) ?></small>
            <?php elseif(!empty($_GET['slot_added'])): ?>
              <small class="text-success d-block mt-1" style="color:green; font-weight:600;">Slot added successfully!</small>
            <?php endif; ?>
          </div>

          <!-- Add Slot Form -->
          <form method="post" class="add-slot-form" style="margin-top: 15px; display: flex; gap: 8px; flex-wrap: wrap;">
            <input type="hidden" name="action" value="add_slot">
            <input type="date" name="slot_date" id="slot_date" required style="height:38px; border-radius:10px; border:1px solid #cfe0ff; padding: 0 10px; outline:none; background:#fff;">
            <input type="time" name="slot_time" id="slot_time" step="300" required style="height:38px; border-radius:10px; border:1px solid #cfe0ff; padding: 0 10px; outline:none; background:#fff;">
            <button type="submit" style="height:38px; padding:0 20px; border-radius:10px; border:none; background:#711491; color:#fff; font-weight:700; cursor:pointer;">Add Slot</button>
          </form>
        </div>

        <!-- Search Bar -->
        <form class="search" action="doctorDashboard.php" method="get" role="search">
          <input type="hidden" name="doctor_id" value="<?= (int)$doctorId ?>">
          <div class="searchbox" style="width:100%;">
            <input
              type="text"
              name="q"
              value="<?= htmlspecialchars($search) ?>"
              placeholder="Search patients or email"
              style="font-weight:600;"
            >
            <button type="submit">Search</button>
          </div>
        </form>
      </div>
    </section>

    <!-- KPI cards -->
    <section class="kpis">
      <div class="card kpi">
        <h6>Today’s Appointments</h6>
        <div class="num"><?= $kpiToday ?></div>
      </div>
      <div class="card kpi">
        <h6>Upcoming Appointments</h6>
        <div class="num"><?= $kpiUpcoming ?></div>
      </div>
      <div class="card kpi">
        <h6>Free Future Slots</h6>
        <div class="num"><?= $kpiFree ?></div>
      </div>
      <div class="card kpi">
        <h6>Patient Messages</h6>
        <div class="num">—</div>
      </div>
    </section>

    <!-- Two-column layout: next appointments & calendar -->
    <section class="grid-2">
      <!-- Next Appointments -->
      <div class="card table-card">
        <div class="table-top">
          <h4>Next Appointments</h4>
        </div>

        <div class="table-wrap">
          <table class="lite-table">
            <thead>
              <tr>
                <th style="width:70px">#</th>
                <th>Patient</th>
                <th>Email</th>
                <th style="min-width:240px">Date &amp; Time</th>
                <th style="width:100px"></th>
              </tr>
            </thead>
            <tbody>
              <?php if($nextApps->num_rows===0): ?>
                <tr><td colspan="5" class="text-muted" style="text-align:center;">No upcoming appointments.</td></tr>
              <?php else: $idx=1; while($row=$nextApps->fetch_assoc()): ?>
                <tr>
                  <td><span class="pill"><?= $idx++ ?></span></td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td><?= date('D, d M Y · h:i a', strtotime($row['date_time'])) ?></td>
                  <td>
                    <div style="display:flex; gap:6px;">
                      <a class="ghost-btn" href="mailto:<?= htmlspecialchars($row['email']) ?>" title="Email" style="padding:6px 10px; display:inline-flex; align-items:center; justify-content:center;"><i class="bi bi-envelope"></i></a>
                      <a class="ghost-btn" href="tel:<?= htmlspecialchars($row['contact']) ?>" title="Call" style="padding:6px 10px; display:inline-flex; align-items:center; justify-content:center;"><i class="bi bi-telephone"></i></a>
                    </div>
                  </td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Calendar + slots -->
      <div class="card calendar-card" id="skCalendar">
        <div class="cal-head">
          <div class="cal-title" style="align-items: flex-start;">
            <div class="cal-year skcal-year" style="font-size:22px; line-height:1; font-weight:800; color:#a289ff;">Year</div>
            <div class="cal-month skcal-month" style="font-size:16px; line-height:1.1; font-weight:800; color:#54a0ff; margin-top:2px;">Month</div>
          </div>
          <div>
            <button type="button" class="btn skcal-prev-year" style="padding:6px 10px; border:0; background:#f2f6ff; color:#3b5bfd; border-radius:10px; font-weight:700; cursor:pointer;">&laquo;</button>
            <button type="button" class="btn skcal-prev-month" style="padding:6px 10px; border:0; background:#f2f6ff; color:#3b5bfd; border-radius:10px; font-weight:700; cursor:pointer;">&lsaquo;</button>
            <button type="button" class="btn skcal-next-month" style="padding:6px 10px; border:0; background:#f2f6ff; color:#3b5bfd; border-radius:10px; font-weight:700; cursor:pointer;">&rsaquo;</button>
            <button type="button" class="btn skcal-next-year" style="padding:6px 10px; border:0; background:#f2f6ff; color:#3b5bfd; border-radius:10px; font-weight:700; cursor:pointer;">&raquo;</button>
          </div>
        </div>

        <div class="cal-grid">
          <!-- Weekday strip -->
          <div class="dow sun">Sun</div>
          <div class="dow">Mon</div>
          <div class="dow">Tue</div>
          <div class="dow">Wed</div>
          <div class="dow">Thu</div>
          <div class="dow">Fri</div>
          <div class="dow sat">Sat</div>

          <!-- Day cells will be appended here by script -->
          <div class="skcal-days" style="display:contents;"></div>
        </div>

        <div class="slots">
          <?php if($slots->num_rows===0): ?>
            <div class="slot"><div class="when">No upcoming slots.</div></div>
          <?php else: while($s=$slots->fetch_assoc()): ?>
            <div class="slot">
              <div class="when"><?= date('D, d M Y · h:i a', strtotime($s['date_time'])) ?></div>
              <div class="st <?= $s['slot_status']==='available' ? 'ok' : 'bad' ?>"><?= ucfirst($s['slot_status']) ?></div>
            </div>
          <?php endwhile; endif; ?>
        </div>
      </div>
    </section>
  </main>

<!-- Helper: set min date today & prefill time -->
<script>
  (function(){
    const pad = n => String(n).padStart(2,'0');
    const now = new Date();
    const yyyy = now.getFullYear(), mm = pad(now.getMonth()+1), dd = pad(now.getDate());
    const hh = pad(now.getHours()), mi = pad(now.getMinutes());
    const dateInput = document.getElementById('slot_date');
    const timeInput = document.getElementById('slot_time');
    if (dateInput) dateInput.min = `${yyyy}-${mm}-${dd}`;
    if (timeInput && !timeInput.value) timeInput.value = `${hh}:${mi}`;
  })();
</script>

<!-- Interactive Calendar Script -->
<script>
(() => {
  const root = document.getElementById('skCalendar');
  if (!root) return;

  const elMonth = root.querySelector('.skcal-month');
  const elYear  = root.querySelector('.skcal-year');
  const daysBox = root.querySelector('.skcal-days');

  const prevM = root.querySelector('.skcal-prev-month');
  const nextM = root.querySelector('.skcal-next-month');
  const prevY = root.querySelector('.skcal-prev-year');
  const nextY = root.querySelector('.skcal-next-year');

  const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  let view = new Date(); view.setDate(1);
  let selected = null;

  const pad = n => (n<10 ? '0'+n : ''+n);
  const same = (a,b) => a && b && a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate();

  function render(){
    const y = view.getFullYear(), m = view.getMonth();
    elMonth.textContent = monthNames[m];
    elYear.textContent = y;

    daysBox.innerHTML = '';
    const first = new Date(y,m,1).getDay();
    const days  = new Date(y,m+1,0).getDate();
    const today = new Date();

    // leading blanks
    for (let i=0;i<first;i++){
      const b = document.createElement('div');
      daysBox.appendChild(b);
    }

    for (let d=1; d<=days; d++){
      const date = new Date(y,m,d);
      const dow  = date.getDay(); // 0 Sun .. 6 Sat

      const cell = document.createElement('div');
      cell.className = 'day';
      if (dow===0) cell.classList.add('sun');
      if (dow===6) cell.classList.add('sat');
      if (same(date, today)) cell.classList.add('is-today');
      if (selected && same(date, selected)) cell.classList.add('selected');
      cell.textContent = d;

      cell.addEventListener('click', () => {
        selected = date;
        const input = document.querySelector('input[name="slot_date"]');
        if (input) input.value = `${y}-${pad(m+1)}-${pad(d)}`; // YYYY-MM-DD
        render();
      });

      daysBox.appendChild(cell);
    }
  }

  prevM.addEventListener('click', ()=>{ view.setMonth(view.getMonth()-1); render(); });
  nextM.addEventListener('click', ()=>{ view.setMonth(view.getMonth()+1); render(); });
  prevY.addEventListener('click', ()=>{ view.setFullYear(view.getFullYear()-1); render(); });
  nextY.addEventListener('click', ()=>{ view.setFullYear(view.getFullYear()+1); render(); });

  render();
})();
</script>
</body>
</html>
