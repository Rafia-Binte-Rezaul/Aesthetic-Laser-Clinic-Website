<?php
require 'config.php';  // $conn

// Greeting (local time)
date_default_timezone_set('Asia/Kuala_Lumpur');
$h = (int)date('G');
$hello = ($h>=5 && $h<12) ? 'Good morning' : (($h<17) ? 'Good afternoon' : 'Good evening');

// doctor filter (optional)
$doctorFilter = isset($_GET['doctor']) && ctype_digit($_GET['doctor']) ? (int)$_GET['doctor'] : 0;

// search
$q = trim($_GET['q'] ?? '');

// KPIs
$today = date('Y-m-d');
$kpi_today = $conn->query(
  "SELECT COUNT(*) c FROM appointments a
   JOIN schedule s ON a.booking_slot=s.schedule_id
   WHERE DATE(s.date_time)='$today'".
   ($doctorFilter? " AND a.doctor_id=$doctorFilter":"")
)->fetch_assoc()['c'] ?? 0;

$kpi_upcoming = $conn->query(
  "SELECT COUNT(*) c FROM appointments a
   JOIN schedule s ON a.booking_slot=s.schedule_id
   WHERE s.date_time > NOW()".
   ($doctorFilter? " AND a.doctor_id=$doctorFilter":"")
)->fetch_assoc()['c'] ?? 0;

$kpi_free = $conn->query(
  "SELECT COUNT(*) c FROM schedule s
   LEFT JOIN appointments a ON a.booking_slot=s.schedule_id
   WHERE s.date_time > NOW() AND a.id IS NULL".
   ($doctorFilter? " AND s.doctor_id=$doctorFilter":"")
)->fetch_assoc()['c'] ?? 0;

// Patient Messages (remarks) — counts non-empty remarks in appointments, respects doctor filter
$kpi_msgs = $conn->query(
  "SELECT COUNT(*) c
   FROM appointments a
   WHERE a.remarks IS NOT NULL AND a.remarks <> ''".
   ($doctorFilter? " AND a.doctor_id=$doctorFilter":"")
)->fetch_assoc()['c'] ?? 0;

// Next appointments (limited, scrollable)
$wheres = [];
$wheres[] = "s.date_time > NOW()";
if ($doctorFilter) $wheres[] = "a.doctor_id=$doctorFilter";
if ($q!==''){
  $qq = $conn->real_escape_string($q);
  $wheres[] = "(a.name LIKE '%$qq%' OR a.email LIKE '%$qq%')";
}
$whereSql = $wheres ? ('WHERE '.implode(' AND ',$wheres)) : '';

$next = $conn->query(
  "SELECT a.id,a.name,a.email,d.name AS doctor,s.date_time
   FROM appointments a
   JOIN doctor d ON d.id=a.doctor_id
   JOIN schedule s ON s.schedule_id=a.booking_slot
   $whereSql
   ORDER BY s.date_time ASC
   LIMIT 30"
);

// Doctors for filter
$doctors = $conn->query("SELECT id,name FROM doctor ORDER BY name");

// Calendar: mark busy days in the visible month (server-side)
$y = (int)($_GET['y'] ?? date('Y'));
$m = (int)($_GET['m'] ?? date('n'));
$start = "$y-$m-01";
$end   = date('Y-m-t', strtotime($start));
$busyDays = [];
$resDays = $conn->query(
  "SELECT DATE(s.date_time) d, COUNT(*) c
   FROM appointments a
   JOIN schedule s ON s.schedule_id=a.booking_slot
   WHERE DATE(s.date_time) BETWEEN '$start' AND '$end'".
   ($doctorFilter? " AND a.doctor_id=$doctorFilter":"").
   " GROUP BY DATE(s.date_time)"
);
while($r=$resDays->fetch_assoc()){ $busyDays[$r['d']] = (int)$r['c']; }

// Upcoming slots (with booked status)
$slots = $conn->query(
  "SELECT s.schedule_id,s.date_time,
          IF(a.id IS NULL,'Available','Booked') AS status
   FROM schedule s
   LEFT JOIN appointments a ON a.booking_slot=s.schedule_id".
   ($doctorFilter? " WHERE s.doctor_id=$doctorFilter":"").
   " ORDER BY s.date_time ASC LIMIT 40"
);

// Prev/Next month helpers for links
$prevTs = strtotime("$start -1 month");
$nextTs = strtotime("$start +1 month");
$prevY  = (int)date('Y', $prevTs);
$prevM  = (int)date('n', $prevTs);
$nextY  = (int)date('Y', $nextTs);
$nextM  = (int)date('n', $nextTs);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin • Dashboard</title>
  <link rel="stylesheet" href="css/adminDashboard.css">
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">
      <img src="img/logo1.png" alt="Skinith">
    </div>
    <nav class="nav">
      <a class="active" href="adminDashboard.php">Dashboard</a>
      <a href="appointmentPanel.php">Appointments</a>
      <a href="doctorPanel.php">Doctors</a>
      <a href="slotPanel.php">Slots</a>
      <a href="analytics.php">Analytics</a>
      <a href="index.html">Logout</a>

      <div class="footer">
        <div class="doctor-switch">
          <form method="get" action="adminDashboard.php">
            <select name="doctor">
              <option value="0"></option>
              <?php while($d=$doctors->fetch_assoc()): ?>
                <option value="<?=$d['id']?>" <?= $doctorFilter==$d['id']?'selected':''?>>
                  <?=htmlspecialchars($d['name'])?>
                </option>
              <?php endwhile; ?>
            </select>
            <button></button>
          </form>
          <small></small>
        </div>
      </div>
    </nav>
  </aside>

  <main class="main">
    <!-- Top band -->
    <section class="topband">
      <div class="topgrid">
        <div class="avatar">
          <img src="img/admin.jpg" alt="Admin">
        </div>

        <div class="hello">
          <h3><?=$hello?>, <strong>Admin</strong></h3>
          <div style="opacity:.75"></div>
        </div>

        <form class="search" method="get" action="adminDashboard.php">
          <?php if($doctorFilter): ?>
            <input type="hidden" name="doctor" value="<?=$doctorFilter?>">
          <?php endif; ?>
          <div class="searchbox">
            <input name="q" value="<?=htmlspecialchars($q)?>" placeholder="Search patients or email">
            <button type="submit">Search</button>
          </div>
        </form>
      </div>
    </section>

    <!-- KPIs -->
    <section class="kpis">
      <div class="card kpi">
        <h6>Today's Appointments</h6>
        <div class="num"><?=$kpi_today?></div>
      </div>
      <div class="card kpi">
        <h6>Upcoming Appointments</h6>
        <div class="num"><?=$kpi_upcoming?></div>
      </div>
      <div class="card kpi">
        <h6>Free Future Slots</h6>
        <div class="num"><?=$kpi_free?></div>
      </div>
      <div class="card kpi">
        <h6>Patient Messages</h6>
        <div class="num"><?=$kpi_msgs?></div>
      </div>
    </section>

    <!-- Appointments + Calendar grid -->
    <section class="grid-2">

      <!-- Next Appointments -->
      <div class="card table-card">
        <div class="table-top">
          <h4>Next Appointments</h4>
          <a class="top-link" href="appointmentPanel.php">Open full list</a>
        </div>

        <div class="table-wrap">
          <table class="lite-table">
            <thead>
              <tr>
                <th style="width:70px">#</th>
                <th>Patient</th>
                <th>Email</th>
                <th>Doctor</th>
                <th style="min-width:240px">Date &amp; Time</th>
                <th style="width:90px"></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($next && $next->num_rows): while ($r = $next->fetch_assoc()): ?>
                <tr>
                  <td><span class="pill"><?= htmlspecialchars($r['id']) ?></span></td>
                  <td><?= htmlspecialchars($r['name']) ?></td>
                  <td><?= htmlspecialchars($r['email']) ?></td>
                  <td><?= htmlspecialchars($r['doctor']) ?></td>
                  <td><?= date('D, d M Y · H:i a', strtotime($r['date_time'])) ?></td>
                  <td><a class="ghost-btn" href="appointmentEdit.php?id=<?= $r['id'] ?>">View</a></td>
                </tr>
              <?php endwhile; else: ?>
                <tr><td colspan="6">No appointments match.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Calendar + slots -->
      <div class="card calendar-card">
        <div class="cal-head">
          <!-- Printed calendar title (matches CSS you added) -->
          <div class="cal-title">
            <div class="cal-year"><?=date('Y', strtotime($start))?></div>
            <div class="cal-month"><?=date('F', strtotime($start))?></div>
          </div>
          <div>
            <a class="btn" style="padding:6px 10px"
               href="?doctor=<?=$doctorFilter?>&y=<?=$prevY?>&m=<?=$prevM?>">&lsaquo;</a>
            <a class="btn" style="padding:6px 10px"
               href="?doctor=<?=$doctorFilter?>&y=<?=$nextY?>&m=<?=$nextM?>">&rsaquo;</a>
          </div>
        </div>

        <div class="cal-grid">
          <!-- Weekday strip (fixed labels) -->
          <div class="dow sun">Sun</div><div class="dow">Mon</div><div class="dow">Tue</div>
          <div class="dow">Wed</div><div class="dow">Thu</div><div class="dow">Fri</div>
          <div class="dow sat">Sat</div>

          <?php
            // Leading blanks
            $firstDow = (int)date('w', strtotime($start)); // 0=Sun
            for($i=0; $i<$firstDow; $i++) echo "<div></div>";

            // Month days with weekend / today / busy classes
            $daysInMonth = (int)date('t', strtotime($start));
            for($d=1; $d<=$daysInMonth; $d++){
              $date = sprintf('%04d-%02d-%02d', $y, $m, $d);
              $w = (int)date('w', strtotime($date)); // 0=Sun, 6=Sat
              $cls='day';
              if($date===date('Y-m-d')) $cls.=' is-today';
              if(isset($busyDays[$date])) $cls.=' is-busy';
              if($w===0) $cls.=' sun';
              if($w===6) $cls.=' sat';
              echo "<div class='$cls'>$d</div>";
            }
          ?>
        </div>

        <div class="slots">
          <?php if($slots && $slots->num_rows): while($s=$slots->fetch_assoc()): ?>
            <div class="slot">
              <div class="when"><?=date('D, d M Y · H:i a', strtotime($s['date_time']))?></div>
              <div class="st <?=$s['status']=='Available'?'ok':'bad'?>"><?=htmlspecialchars($s['status'])?></div>
            </div>
          <?php endwhile; else: ?>
            <div class="slot"><div class="when">No upcoming slots.</div></div>
          <?php endif; ?>
        </div>
      </div>

    </section>
  </main>
</body>
</html>
