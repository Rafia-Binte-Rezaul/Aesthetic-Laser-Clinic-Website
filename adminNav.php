<?php if (!isset($active)) $active = ''; ?>
<nav class="navbar navbar-expand-lg mb-3"
     style="background-image:linear-gradient(90deg,#d3f7f5 0%,#b5ddf8 55%,#c2bdf9 80%,#e790d3 100%);
            border-radius:8px; position:sticky; top:0; z-index:1100;">
  <div class="container-fluid">

    <a class="navbar-brand d-flex align-items-center" href="appointmentPanel.php">
      <img src="img/logo1.png" alt="Skinith" style="height:60px; width:auto;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminTopNav"
            aria-controls="adminTopNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="adminTopNav">
      <ul class="navbar-nav">

        <!-- Appointments -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $active==='appointments'?'active':'' ?>" href="appointmentPanel.php"
             id="navAppointments" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Appointments
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navAppointments">
            <li><a class="dropdown-item" href="appointmentPanel.php">View</a></li>
            <li><a class="dropdown-item" href="appointmentAdd.php">Add Appointment</a></li>
            <li><a class="dropdown-item" href="appointmentEdit.php">Edit Appointment</a></li>
            <li><a class="dropdown-item" href="appointmentDelete.php">Delete Appointment</a></li>

          </ul>
        </li>

        <!-- Doctors -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $active==='doctors'?'active':'' ?>" href="doctorPanel.php"
             id="navDoctors" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Doctors
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navDoctors">
            <li><a class="dropdown-item" href="doctorPanel.php">View</a></li>
            <li><a class="dropdown-item" href="doctorAdd.php">Add Doctor</a></li>
            <li><a class="dropdown-item" href="doctorEdit.php">Edit Doctor</a></li>
            <li><a class="dropdown-item" href="doctorDelete.php">Delete Doctor</a></li>
          </ul>
        </li>

        <!-- Schedules/Slots -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $active==='slots'?'active':'' ?>" href="slotPanel.php"
             id="navSlots" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Schedules
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navSlots">
            <li><a class="dropdown-item" href="slotPanel.php">View</a></li>
            <li><a class="dropdown-item" href="slotAdd.php">Add Schedule</a></li>
            <li><a class="dropdown-item" href="slotEdit.php">Edit Schedule</a></li>
            <li><a class="dropdown-item" href="slotDelete.php">Delete Schedule</a></li>
          </ul>
        </li>

        <!-- Doctors -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $active==='doctors'?'active':'' ?>" href="adminDashboard.php"
             id="navDoctors" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Dashboard
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navDoctors">
            <li><a class="dropdown-item" href="adminDashboard.php">Home Page</a></li>
            <!-- <li><a class="dropdown-item" href="appointment.php">Booking System</a></li>
            <li><a class="dropdown-item" href="treatment.html">Service Page</a></li> -->
          </ul>
        </li>

      </ul>
    </div>
  </div>
</nav>
