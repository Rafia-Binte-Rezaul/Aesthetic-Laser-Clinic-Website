<?php
session_start();

/* ---- CONFIG: change if your folder name is different ---- */
$BASE = '/skinith1/'; // must start & end with a slash if you use an absolute path in header()

/* ---- DB ---- */
require_once __DIR__ . '/config.php';

$loginMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Normalize email
    $email = strtolower($email);

    // Fetch user (prepared statement)
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res  = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        // Good login
        session_regenerate_id(true);

        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role']      = $user['role'] ?? 'patient';

        if ($_SESSION['role'] === 'admin') {
            header("Location: {$BASE}adminDashboard.php");
            exit;
        }

        if ($_SESSION['role'] === 'doctor') {
            // Find linked doctor.id via doctor.user_id
            $stmt = $conn->prepare("SELECT id FROM doctor WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $docRes = $stmt->get_result();
            $doc    = $docRes->fetch_assoc();
            $stmt->close();

            if ($doc) {
                $_SESSION['doctor_id'] = (int)$doc['id'];
                header("Location: {$BASE}doctorDashboard.php?doctor_id=" . (int)$doc['id']);
                exit;
            }

            // If somehow not linked, just go to dashboard without id (or home)
            header("Location: {$BASE}doctorDashboard.php");
            exit;
        }

        // Default: patient or unknown roles -> redirect target or site home
        if (isset($_SESSION['redirect_to'])) {
            $redirectTo = $_SESSION['redirect_to'];
            unset($_SESSION['redirect_to']);
            header("Location: " . $redirectTo);
            exit;
        }

        header("Location: {$BASE}index.html");
        exit;
    } else {
        // Bad login (generic message)
        $loginMessage = "<span style='color:#e85c5c;'>Invalid email or password!</span>";
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rezaul Rafia Binte-Final Year Project</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <!-- Icons / Libs -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Styles -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
    <link href="css/navResponsive.css" rel="stylesheet">

</head>
<body>
    <!-- Spinner -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    <!-- Nav -->
     <nav class="navbar">
  <div class="nav-left">
    <div class="nav-brand">
       <img src="img/logo.png" alt="Skinith Logo" class="logo-img" />
    </div>
  </div>

  <div class="nav-center">
    <div class="nav-links">
      <a href="index.html" >Home</a>
      <a href="about.html" >About Us</a>
      <a href="treatment.html" >Treatments</a>
      <a href="doctors.php" >Doctors</a>
      <a href="contact.html" >Contact</a>
    </div>
  </div>

  <div class="nav-right">
    <a href="appointment.php" class="book-btn">BOOK APPOINTMENT</a>
    <a href="https://wa.me/601133179934" target="_blank" class="whatsapp-nav">
      <i class="fab fa-whatsapp" style="font-size:32px; color:#12c661;"></i>
    </a>
  </div>
</nav>

    <!-- Header -->
    <!-- <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Login/Sign Up</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center">
                            <li class="breadcrumb-item"><a class="text-white" href="#">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="#">Pages</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">About</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div> -->

    <?php if ($loginMessage !== ""): ?>
      <div style="margin-bottom:10px;text-align:center;"><?= $loginMessage ?></div>
    <?php endif; ?>

    <!-- Login Card -->
    <div class="login-bg">
      <div class="login-card">
        <img src="img/logo.png" alt="Skinith Logo" class="login-logo">
        <h2>Welcome Back</h2>

        <form method="POST" action="login.php" novalidate>
          <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
          </div>

          <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>

          <button type="submit" class="login-btn">Login</button>
        </form>

        <div class="login-links">
          <a href="#">Forgot password?</a>
          <span> | </span>
          <a href="signUp.php">Sign up</a>
        </div>
      </div>
    </div>

   <!-- Footer Start -->
   <div class="footer-wave" style="margin:0; padding:0;">
  <svg viewBox="0 0 1440 100" width="100%" height="100" preserveAspectRatio="none" style="display:block;">
    <defs>
      <linearGradient id="footer-gradient" x1="0" y1="0" x2="1" y2="0" gradientUnits="objectBoundingBox">
        <stop offset="0%" stop-color="#8268bc"/>
        <stop offset="100%" stop-color="#e6bada"/>
      </linearGradient>
    </defs>
    <path 
      d="M0,50 Q360,0 720,50 T1440,50 L1440,100 L0,100 Z" 
      fill="url(#footer-gradient)"
    />
  </svg>
</div>


<div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
    <div class="container py-5">
        <div class="row g-5">

            <!-- Quick Links -->
            <div class="col-lg-3 col-md-6">
                <h4 class="text-white mb-3">Quick Links</h4>
                <a class="btn btn-link" href="about.html">About Us</a>
                <a class="btn btn-link" href="contact.html">Contact</a>
                <a class="btn btn-link" href="treatment.html">Treatments</a>
                <a class="btn btn-link" href="appointment.php">FAQs</a>
                <a class="btn btn-link" href="appointment.php">Book Appointment</a>

                <div class="d-flex pt-2">
    <a class="btn btn-social btn-facebook" href="#"><i class="fab fa-facebook-f"></i></a>
    <a class="btn btn-social btn-instagram" href="#"><i class="fab fa-instagram"></i></a>
    <a class="btn btn-social btn-tiktok" href="#"><i class="fab fa-tiktok"></i></a>
    <a class="btn btn-social btn-whatsapp" href="#"><i class="fab fa-whatsapp"></i></a>
</div>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6">
                <h4 class="text-white mb-3">Contact</h4>
                <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Skinith, Kuala Lumpur, Malaysia</p>
                <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+60 11-3317 9934</p>
                <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@skinith.com</p>
                

            </div>

            <!-- Gallery -->
<div class="col-lg-3 col-md-6">
    <h4 class="text-white mb-3">Gallery</h4>
    <div class="row g-2 pt-2">
        <div class="col-4"><img class="img-fluid gallery-img" src="img/course-1.jpg" alt=""></div>
        <div class="col-4"><img class="img-fluid gallery-img" src="img/course-2.jpg" alt=""></div>
        <div class="col-4"><img class="img-fluid gallery-img" src="img/course-3.jpg" alt=""></div>
        <div class="col-4"><img class="img-fluid gallery-img" src="img/course-2.jpg" alt=""></div>
        <div class="col-4"><img class="img-fluid gallery-img" src="img/course-3.jpg" alt=""></div>
        <div class="col-4"><img class="img-fluid gallery-img" src="img/course-1.jpg" alt=""></div>
    </div>
</div>


      <!-- Newsletter -->
<div class="col-lg-3 col-md-6">
  <h4 class="text-white mb-3">Newsletter</h4>
  <p>Subscribe to receive exclusive updates on beauty tips and skin treatment offers.</p>

  <form id="newsletterForm" method="post" class="position-relative mx-auto" style="max-width: 400px;">
    <input id="email" class="form-control border-0 w-100 py-3 ps-4 pe-5"
           type="email" name="email" placeholder="Your email" required>
    <button type="submit" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">
      Subscribe
    </button>
  </form>
  <div id="message" class="mt-2"></div>
</div>

    <!-- Copyright -->
    <div class="container">
        <div class="copyright">
            <div class="row">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    &copy; <a class="border-bottom" href="#">Skinith</a>, All Rights Reserved.<br><br>
                    Designed by <a class="border-bottom" href="#">Rafia Binte Rezaul</a>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <div class="footer-menu">
                        <a href="index.html">Home</a>
                        <a href="treatment.html">Treatments</a>
                        <a href="contact.html">Contact</a>
                        <a href="appointment.php">FAQs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Footer End -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>

    <script>
$(function(){
  $('#newsletterForm').on('submit', function(e){
    e.preventDefault(); // use AJAX instead of page reload
    $.post('subscribe.php', $(this).serialize(), function(res){
      res = $.trim(res);
      if(res === 'success'){
        $('#message').text('A notification has been sent to your email.').css('color','white');
        $('#email').val('');
        setTimeout(function(){
          $('#message').fadeOut(300, function(){
            $(this).text('').show();
          });
        }, 2500);
      } else if(res === 'invalid' || res === 'invalid_email'){
        $('#message').text('❌ Invalid email address.').css('color','crimson');
      } else {
        $('#message').text('❌ Something went wrong.').css('color','crimson');
      }
    });
  });
});
</script>

</body>
</html>
