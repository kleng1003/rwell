<?php
// Start session
session_start();

// Include database connection
require_once 'admin/include/connection.php';

// Fetch all active services from database
$services_query = "SELECT * FROM services WHERE status = 'active' ORDER BY created_at DESC";
$services_result = $con->query($services_query);
$services = $services_result->fetch_all(MYSQLI_ASSOC);

// Service icons mapping based on category or name
function getServiceIcon($service_name, $category) {
    $icons = [
        'hair' => 'bi-scissors',
        'haircut' => 'bi-scissors',
        'styling' => 'bi-scissors',
        'facial' => 'bi-brush',
        'skincare' => 'bi-brush',
        'massage' => 'bi-spa',
        'body' => 'bi-spa',
        'nails' => 'bi-hand-index-thumb',
        'manicure' => 'bi-hand-index-thumb',
        'pedicure' => 'bi-hand-index-thumb',
        'waxing' => 'bi-droplet',
        'makeup' => 'bi-eyeglasses',
        'color' => 'bi-palette',
        'hair color' => 'bi-palette'
    ];
    
    $service_lower = strtolower($service_name);
    $category_lower = strtolower($category);
    
    // Check for keywords in service name
    foreach ($icons as $keyword => $icon) {
        if (strpos($service_lower, $keyword) !== false) {
            return $icon;
        }
    }
    
    // Default icon
    return 'bi-star-fill';
}

// Get icon color based on category
function getIconColor($category) {
    $colors = [
        'hair' => 'text-primary',
        'nails' => 'text-danger',
        'skincare' => 'text-success',
        'massage' => 'text-warning',
        'waxing' => 'text-info',
        'makeup' => 'text-secondary'
    ];
    
    $category_lower = strtolower($category);
    
    foreach ($colors as $key => $color) {
        if (strpos($category_lower, $key) !== false) {
            return $color;
        }
    }
    
    return 'text-primary';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>R-Well Salon & Spa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #fffaf7;
    }
    .navbar {
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .hero {
      background: url('./assets/img/BG.jpeg') no-repeat center center/cover;
      position: relative;
      color: #fff;
      text-align: center;
      padding: 120px 20px;
    }
    .hero h1 {
      font-size: 3rem;
      font-weight: bold;
      color: #fff;
      text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.5);
    }
    .hero p {
      font-size: 1.2rem;
    }
    .hero::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.4);
      z-index: 1;
    }
    .hero h1, .hero p {
      position: relative;
      z-index: 2;
      color: #ffffff;
    }
    .hero .container {
      position: relative;
      z-index: 2;
    }
    .btn-book {
      background-color: #e91e63;
      border: none;
      transition: all 0.3s ease;
    }
    .btn-book:hover {
      background-color: #d81b60;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(233, 30, 99, 0.3);
    }
    .section-title {
      font-weight: 700;
      margin-bottom: 2rem;
      position: relative;
      display: inline-block;
    }
    .section-title:after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, #e91e63, #ff6b6b);
      border-radius: 2px;
    }
    .service-card {
      border: none;
      border-radius: 15px;
      transition: all 0.3s ease;
      background: white;
      box-shadow: 0 5px 20px rgba(0,0,0,0.05);
      height: 100%;
    }
    .service-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }
    .service-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      display: inline-block;
      transition: transform 0.3s ease;
    }
    .service-card:hover .service-icon {
      transform: scale(1.1);
    }
    .price-tag {
      font-size: 1.25rem;
      font-weight: bold;
      color: #e91e63;
      margin: 10px 0;
    }
    .duration-badge {
      background: #f8f9fa;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      color: #666;
      display: inline-block;
    }
    .service-description {
      color: #666;
      font-size: 0.9rem;
      line-height: 1.5;
      margin: 15px 0;
    }
    .service-card .card-body {
      padding: 1.5rem;
    }
    .service-footer {
      margin-top: auto;
      padding-top: 1rem;
    }
    .btn-service-book {
      background: transparent;
      border: 2px solid #e91e63;
      color: #e91e63;
      padding: 8px 20px;
      border-radius: 25px;
      transition: all 0.3s ease;
      font-size: 0.9rem;
    }
    .btn-service-book:hover {
      background: #e91e63;
      color: white;
      transform: translateY(-2px);
    }
    #about img{
      height: 300px;
      width: 90%;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    footer {
      background-color: #2c3e50;
      color: #e2e2e2;
      text-align: center;
      padding: 30px 0;
    }
    .service-loading {
      text-align: center;
      padding: 50px;
    }
    .no-services {
      text-align: center;
      padding: 50px;
      background: #f8f9fa;
      border-radius: 15px;
    }
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .service-card {
      animation: fadeIn 0.5s ease-out;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">✨ R-Well Salon & Spa</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navbarNav" class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav">
        <li class="nav-item"><a href="#services" class="nav-link">Services</a></li>
        <li class="nav-item"><a href="#about" class="nav-link">About</a></li>
        <li class="nav-item"><a href="#booking" class="nav-link">Book</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <h1>Welcome to R-Well Salon & Spa</h1>
    <p>Experience luxury, beauty, and relaxation at its finest</p>
    <a href="#booking" class="btn btn-book btn-lg text-white mt-3 px-4 py-2">
      <i class="bi bi-calendar-check"></i> Book an Appointment ijouijmi,
    </a>
  </div>
</section>

<!-- Services Section - Dynamic from Database -->
<section id="services" class="py-5">
  <div class="container">
    <h2 class="text-center section-title">Our Premium Services</h2>
    <p class="text-center text-muted mb-5">Discover our wide range of professional services tailored just for you</p>
    
    <div class="row g-4">
      <?php if (count($services) > 0): ?>
        <?php foreach ($services as $service): ?>
          <div class="col-md-6 col-lg-4 d-flex">
            <div class="card service-card w-100">
              <div class="card-body d-flex flex-column">
                <div class="text-center">
                  <div class="service-icon <?php echo getIconColor($service['category'] ?? ''); ?>">
                    <i class="bi <?php echo getServiceIcon($service['service_name'], $service['category'] ?? ''); ?>"></i>
                  </div>
                  <h4 class="mt-3 mb-2"><?php echo htmlspecialchars($service['service_name']); ?></h4>
                  <div class="duration-badge">
                    <i class="bi bi-clock"></i> <?php echo $service['duration']; ?> minutes
                  </div>
                  <div class="price-tag">
                    ₱<?php echo number_format($service['price'], 2); ?>
                  </div>
                  <p class="service-description">
                    <?php 
                    $desc = htmlspecialchars($service['description']);
                    echo !empty($desc) ? (strlen($desc) > 120 ? substr($desc, 0, 120) . '...' : $desc) : 'Experience this premium service from our expert professionals.';
                    ?>
                  </p>
                </div>
                <div class="service-footer text-center mt-auto">
                  <a href="./assets/pages/appointment.php?service=<?php echo $service['service_id']; ?>" class="btn btn-service-book">
                    <i class="bi bi-calendar-plus"></i> Book Now
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="no-services">
            <i class="bi bi-emoji-smile fs-1 text-muted"></i>
            <h4 class="mt-3">Coming Soon!</h4>
            <p class="text-muted">Our services are being updated. Please check back later.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- About Section -->
<section id="about" class="py-5 bg-light">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6">
        <h1 class="section-title text-start d-inline-block">About R-Well Salon and Spa</h1>
        <p class="mt-4" style="font-size: 1.1rem; line-height: 1.8;">At R-Well, we believe that beauty and wellness go hand in hand. 
          Our team of experienced professionals is dedicated to providing you with the highest quality services 
          in a relaxing and luxurious environment.</p>
        <p>We use only premium products and the latest techniques to ensure you leave feeling refreshed, 
          rejuvenated, and beautiful. Whether you're here for a simple haircut or a full day of pampering, 
          your satisfaction is our top priority.</p>
        <div class="mt-4">
          <div class="row">
            <div class="col-6">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <span>Expert Professionals</span>
              </div>
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <span>Premium Products</span>
              </div>
            </div>
            <div class="col-6">
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <span>Relaxing Atmosphere</span>
              </div>
              <div class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <span>Hygienic Standards</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <img src="./assets/img/image.png" alt="About R-Well Salon" class="img-fluid rounded shadow">
      </div>
    </div>
  </div>
</section>

<!-- Booking Section -->
<section id="booking" class="py-5 text-center" style="background: linear-gradient(135deg, #fff5f7 0%, #ffe6ea 100%);">
  <div class="container">
    <h2 class="section-title">Book an Appointment</h2>
    <p class="mb-4">Ready to experience our services? Schedule your appointment today!</p>
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow-sm border-0 p-4">
          <div class="row text-center">
            <div class="col-md-4 mb-3">
              <i class="bi bi-telephone-fill fs-1 text-primary"></i>
              <h6 class="mt-2">Call Us</h6>
              <p class="text-muted">(02) 1234 5678</p>
            </div>
            <div class="col-md-4 mb-3">
              <i class="bi bi-envelope-fill fs-1 text-primary"></i>
              <h6 class="mt-2">Email Us</h6>
              <p class="text-muted">info@rwellsalon.com</p>
            </div>
            <div class="col-md-4 mb-3">
              <i class="bi bi-geo-alt-fill fs-1 text-primary"></i>
              <h6 class="mt-2">Visit Us</h6>
              <p class="text-muted">123 Main St, Metro Manila</p>
            </div>
          </div>
          <div class="mt-3">
            <a href="./assets/pages/appointment.php" class="btn btn-book btn-lg text-white px-5">
              <i class="bi bi-calendar-check"></i> Make a Reservation
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="container">
    <div class="row">
      <div class="col-md-4 mb-3">
        <h5>R-Well Salon & Spa</h5>
        <p class="small">Your beauty and relaxation destination</p>
      </div>
      <div class="col-md-4 mb-3">
        <h5>Quick Links</h5>
        <ul class="list-unstyled">
          <li><a href="#services" class="text-decoration-none text-white-50">Services</a></li>
          <li><a href="#about" class="text-decoration-none text-white-50">About Us</a></li>
          <li><a href="#booking" class="text-decoration-none text-white-50">Book Appointment</a></li>
        </ul>
      </div>
      <div class="col-md-4 mb-3">
        <h5>Follow Us</h5>
        <div class="d-flex justify-content-center gap-3">
          <a href="#" class="text-white text-decoration-none"><i class="bi bi-facebook fs-4"></i></a>
          <a href="#" class="text-white text-decoration-none"><i class="bi bi-instagram fs-4"></i></a>
          <a href="#" class="text-white text-decoration-none"><i class="bi bi-twitter fs-4"></i></a>
        </div>
      </div>
    </div>
    <hr class="bg-white-50">
    <p class="mb-0 small">&copy; 2025 R-Well Salon & Spa. All rights reserved.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Add smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });

  // Add fade-in animation on scroll
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);

  document.querySelectorAll('.service-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    observer.observe(card);
  });
</script>
</body>
</html>