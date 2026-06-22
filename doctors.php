<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
require_once __DIR__ . '/config.php';

// Query doctors table
$result = $conn->query("SELECT * FROM doctor");
?>

<!DOCTYPE html>
<html lang="en">
<!-- ...rest of your HTML... -->

<head>
    <meta charset="utf-8">
    <title>Rezaul Rafia Binte-Final Year Project</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Poppins:wght@400;700&display=swap" rel="stylesheet">


    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

     
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">



    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/doctors.css" rel="stylesheet">
    <link href="css/navResponsive.css" rel="stylesheet">



</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->


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
      <a href="doctors.php" class="active">Doctors</a>
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


<section class="section__container doctors__container" id="doctor">
  <!-- <div class="doctors__header">
    <div class="doctors__header__content">
      <h2 class="section__header">Our Special Doctors</h2>
      <p>We take pride in our exceptional team of doctors, each a specialist in their respective fields.</p>
    </div> -->
    <div class="doctors__nav">
      <span><i class="ri-arrow-left-line"></i></span>
      <span><i class="ri-arrow-right-line"></i></span>
    </div>
  </div>
  <div class="doctors__grid">
    <?php
      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          echo "
            <div class='doctors__card'>
              <div class='doctors__card__image'>
                <img src='img/" . htmlspecialchars($row["doctor_image"]) . "' alt='Doctor Photo' />
                <div class='doctors__socials'>
                  <a href='appointment.php?doctor=" . urlencode($row["id"]) . "' class='doctor-book-btn'>Book an Appointment</a>
                </div>
              </div>
              <h4>" . htmlspecialchars($row["name"]) . "</h4>
              <p>" . htmlspecialchars($row["specialist"]) . "</p>
            </div>";
        }
      } else {
        echo "No doctors found.";
      }
    ?>
  </div>
</section>




    

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

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
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