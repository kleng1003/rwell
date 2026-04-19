<?php
// Start session
session_start();

// Include database connection
require_once 'admin/include/connection.php';

// Process login form submission
$login_error = '';
$login_success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_action'])) {
    $username = trim(mysqli_real_escape_string($con, $_POST['username']));
    $password = $_POST['password'];
    
    $sql = "SELECT client_id, first_name, last_name, username, password, email, contact_no, customer_id 
            FROM tbl_client_accounts 
            WHERE username = '$username'";
    
    $result = mysqli_query($con, $sql);
    
    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['client_id'] = $user['client_id'];
            $_SESSION['client_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['client_username'] = $user['username'];
            $_SESSION['client_email'] = $user['email'];
            $_SESSION['client_contact'] = $user['contact_no'];
            $_SESSION['customer_id'] = $user['customer_id']; // Store customer_id
            
            $login_success = "Login successful! Redirecting...";
            echo "<script>setTimeout(function() { window.location.href = 'index.php'; }, 1500);</script>";
        } else {
            $login_error = "Invalid username or password.";
        }
    } else {
        $login_error = "Invalid username or password.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Fetch all active services from database
$services_query = "SELECT * FROM services WHERE status = 'active' ORDER BY created_at DESC";
$services_result = $con->query($services_query);
$services = $services_result->fetch_all(MYSQLI_ASSOC);

// Check if user is logged in
$is_logged_in = isset($_SESSION['client_id']);
// Use null coalescing operator to avoid undefined array key warning
$client_name = $is_logged_in && isset($_SESSION['client_name']) ? $_SESSION['client_name'] : '';
$client_id = $is_logged_in ? $_SESSION['client_id'] : null;

// Determine which page to show
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Fetch reservations if on my-reservations page
$reservations = [];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($page == 'my-reservations' && $is_logged_in) {
    // Build query based on filter
    $customer_id = $_SESSION['customer_id'];
    $where_clause = "a.customer_id = $customer_id";
    switch ($filter) {
        case 'upcoming':
            $where_clause .= " AND a.appointment_date >= CURDATE() AND a.status IN ('pending', 'approved')";
            break;
        case 'completed':
            $where_clause .= " AND a.status = 'completed'";
            break;
        case 'cancelled':
            $where_clause .= " AND a.status = 'cancelled'";
            break;
        case 'all':
        default:
            // No additional filter
            break;
    }
    
    $reservations_query = "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.purpose,
            a.status,
            a.created_at,
            a.notes,
            s.service_name,
            s.price,
            s.duration,
            e.first_name as employee_first_name,
            e.last_name as employee_last_name,
            c.first_name as customer_first,
            c.last_name as customer_last,
            c.phone as customer_phone,
            c.email as customer_email
        FROM appointments a
        LEFT JOIN services s ON a.service_id = s.service_id
        LEFT JOIN employees e ON a.employee_id = e.employee_id
        LEFT JOIN customers c ON a.customer_id = c.customer_id
        WHERE $where_clause
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ";
    
    $reservations_result = $con->query($reservations_query);
    if ($reservations_result) {
        $reservations = $reservations_result->fetch_all(MYSQLI_ASSOC);
    }
}

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
    
    foreach ($icons as $keyword => $icon) {
        if (strpos($service_lower, $keyword) !== false) {
            return $icon;
        }
    }
    
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

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-warning';
        case 'approved':
            return 'bg-success';
        case 'completed':
            return 'bg-info';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Function to format date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Function to format time
function formatTime($time) {
    return date('g:i A', strtotime($time));
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
    .navbar-brand {
      font-weight: bold;
    }
    .nav-link {
      transition: color 0.3s ease;
    }
    .nav-link:hover {
      color: #e91e63 !important;
    }
    .nav-link.active {
      color: #e91e63 !important;
      font-weight: 500;
    }
    .dropdown-menu {
      border: none;
      box-shadow: 0 5px 20px rgba(0,0,0,0.1);
      border-radius: 10px;
    }
    .dropdown-item {
      padding: 10px 20px;
      transition: background 0.3s ease;
    }
    .dropdown-item:hover {
      background: #fff5f7;
      color: #e91e63;
    }
    .dropdown-item.text-danger:hover {
      background: #fee;
      color: #dc3545 !important;
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
    
    /* My Reservations Page Styles */
    .page-header {
      background: linear-gradient(135deg, #e91e63, #ff6b6b);
      color: white;
      padding: 40px 0;
      margin-bottom: 30px;
    }
    .filter-card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    .reservation-card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.05);
      margin-bottom: 20px;
      transition: transform 0.3s ease;
    }
    .reservation-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .status-badge {
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
    }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }
    .empty-state i {
      font-size: 4rem;
      color: #e91e63;
      margin-bottom: 20px;
    }
    .btn-filter {
      border-radius: 25px;
      padding: 8px 20px;
      margin: 0 5px;
    }
    .btn-filter.active {
      background: linear-gradient(135deg, #e91e63, #ff6b6b);
      color: white;
      border: none;
    }
    .detail-item {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }
    .detail-item i {
      width: 24px;
      color: #e91e63;
      margin-right: 10px;
    }
    .cancel-btn {
      background: transparent;
      border: 2px solid #dc3545;
      color: #dc3545;
      padding: 8px 20px;
      border-radius: 25px;
      transition: all 0.3s ease;
    }
    .cancel-btn:hover {
      background: #dc3545;
      color: white;
    }
    .reschedule-btn {
      background: transparent;
      border: 2px solid #e91e63;
      color: #e91e63;
      padding: 8px 20px;
      border-radius: 25px;
      transition: all 0.3s ease;
    }
    .reschedule-btn:hover {
      background: #e91e63;
      color: white;
    }
    
    /* Login Modal Custom Styles */
    .modal-login {
      border-radius: 20px;
    }
    .modal-login .modal-content {
      border-radius: 20px;
      border: none;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    .modal-login .modal-header {
      background: linear-gradient(135deg, #e91e63, #ff6b6b);
      color: white;
      border-radius: 20px 20px 0 0;
      border: none;
      padding: 20px;
    }
    .modal-login .modal-header .btn-close {
      filter: brightness(0) invert(1);
    }
    .modal-login .modal-body {
      padding: 30px;
    }
    .login-icon {
      width: 70px;
      height: 70px;
      background: linear-gradient(135deg, #e91e63, #ff6b6b);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
    }
    .login-icon i {
      font-size: 35px;
      color: white;
    }
    .input-group-custom {
      position: relative;
      margin-bottom: 20px;
    }
    .input-group-custom i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #e91e63;
      z-index: 10;
    }
    .input-group-custom input {
      padding-left: 45px;
      height: 50px;
      border-radius: 25px;
      border: 1px solid #e0e0e0;
      transition: all 0.3s ease;
    }
    .input-group-custom input:focus {
      border-color: #e91e63;
      box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.25);
    }
    .btn-login {
      background: linear-gradient(135deg, #e91e63, #ff6b6b);
      border: none;
      border-radius: 25px;
      padding: 12px;
      font-weight: bold;
      font-size: 16px;
      transition: all 0.3s ease;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
    }
    .register-link {
      text-align: center;
      margin-top: 20px;
    }
    .register-link a {
      color: #e91e63;
      text-decoration: none;
      font-weight: bold;
    }
    .register-link a:hover {
      text-decoration: underline;
    }
    .alert-custom {
      border-radius: 25px;
      border: none;
    }
    
    /* User dropdown styles */
    .user-dropdown-toggle {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .user-dropdown-toggle i {
      font-size: 1.2rem;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">✨ R-Well Salon & Spa</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="navbarNav" class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item">
          <a href="index.php" class="nav-link <?php echo $page == 'home' ? 'active' : ''; ?>">Home</a>
        </li>
        
        <?php if ($is_logged_in): ?>
          <!-- My Appointments Link -->
          <li class="nav-item">
            <a href="./client/my-reservations.php" class="nav-link">
              <i class="bi bi-calendar-check"></i> My Appointments
            </a>
          </li>
          
          <!-- User Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle user-dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle"></i>
              <?php echo htmlspecialchars($client_name); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <!-- <li>
                <a class="dropdown-item" href="client/dashboard.php">
                  <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
              </li> -->
              <!-- <li>
                <a class="dropdown-item" href="index.php?page=my-reservations">
                  <i class="bi bi-calendar-check me-2"></i>My Appointments
                </a>
              </li> -->
              <li>
                <a class="dropdown-item" href="client/profile.php">
                  <i class="bi bi-person me-2"></i>Profile Settings
                </a>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="?logout=1">
                  <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <!-- Login Button -->
          <li class="nav-item">
            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#loginModal">
              <i class="bi bi-box-arrow-in-right"></i> Log in
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-login">
      <div class="modal-header">
        <h5 class="modal-title w-100 text-center" id="loginModalLabel">
          <i class="bi bi-person-circle me-2"></i>Client Login
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="login-icon">
          <i class="bi bi-scissors"></i>
        </div>
        
        <?php if ($login_error): ?>
          <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php echo $login_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        
        <?php if ($login_success): ?>
          <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $login_success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        
        <form method="POST" action="">
          <input type="hidden" name="login_action" value="1">
          
          <div class="input-group-custom">
            <i class="bi bi-person"></i>
            <input type="text" class="form-control" name="username" placeholder="Username" required autocomplete="off">
          </div>
          
          <div class="input-group-custom">
            <i class="bi bi-lock"></i>
            <input type="password" class="form-control" name="password" placeholder="Password" required>
          </div>
          
          <button type="submit" class="btn btn-login text-white w-100">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login
          </button>
          
          <div class="register-link">
            <p class="mb-0">Don't have an account? <a href="client/register.php">Register here</a></p>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($page == 'home'): ?>
<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <h1>Welcome to R-Well Salon & Spa</h1>
    <p>Experience luxury, beauty, and relaxation at its finest</p>
    <a href="javascript:void(0)" class="btn btn-book btn-lg text-white mt-3 px-4 py-2" onclick="checkLoginAndBook()">
      <i class="bi bi-calendar-check"></i> Book an Appointment
    </a>
  </div>
</section>

<!-- Services Section -->
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
                  <a href="javascript:void(0)" onclick="checkLoginAndBook(<?php echo $service['service_id']; ?>)" class="btn btn-service-book">
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
              <p class="text-muted">Rizal, Cabugao, Ilocos Sur</p>
            </div>
          </div>
          <div class="mt-3">
            <a href="javascript:void(0)" onclick="checkLoginAndBook()" class="btn btn-book btn-lg text-white px-5">
              <i class="bi bi-calendar-check"></i> Make a Reservation
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php elseif ($page == 'my-reservations' && $is_logged_in): ?>
<!-- My Reservations Page -->
<div class="page-header">
  <div class="container">
    <h1 class="display-6 mb-2">
      <i class="bi bi-calendar-check me-2"></i>My Appointments
    </h1>
    <p class="lead mb-0">View and manage all your appointments</p>
  </div>
</div>

<div class="container mb-5">
  <!-- Filter Section -->
  <div class="filter-card">
    <div class="card-body">
      <div class="d-flex justify-content-center flex-wrap">
        <a href="?page=my-reservations&filter=all" class="btn btn-filter m-1 <?php echo $filter == 'all' ? 'active' : 'btn-outline-secondary'; ?>">
          <i class="bi bi-list-ul"></i> All
        </a>
        <a href="?page=my-reservations&filter=upcoming" class="btn btn-filter m-1 <?php echo $filter == 'upcoming' ? 'active' : 'btn-outline-secondary'; ?>">
          <i class="bi bi-calendar-event"></i> Upcoming
        </a>
        <a href="?page=my-reservations&filter=completed" class="btn btn-filter m-1 <?php echo $filter == 'completed' ? 'active' : 'btn-outline-secondary'; ?>">
          <i class="bi bi-check-circle"></i> Completed
        </a>
        <a href="?page=my-reservations&filter=cancelled" class="btn btn-filter m-1 <?php echo $filter == 'cancelled' ? 'active' : 'btn-outline-secondary'; ?>">
          <i class="bi bi-x-circle"></i> Cancelled
        </a>
      </div>
    </div>
  </div>

  <!-- Reservations List -->
  <?php if (empty($reservations)): ?>
    <div class="empty-state">
      <i class="bi bi-calendar-x"></i>
      <h3>No appointments found</h3>
      <p class="text-muted mb-4">
        <?php
        switch ($filter) {
          case 'upcoming':
            echo "You don't have any upcoming appointments.";
            break;
          case 'completed':
            echo "You don't have any completed appointments.";
            break;
          case 'cancelled':
            echo "You don't have any cancelled appointments.";
            break;
          default:
            echo "You haven't made any appointments yet.";
        }
        ?>
      </p>
      <a href="javascript:void(0)" onclick="checkLoginAndBook()" class="btn btn-book text-white px-4 py-2" style="background: linear-gradient(135deg, #e91e63, #ff6b6b); border: none;">
        <i class="bi bi-calendar-plus"></i> Book an Appointment
      </a>
    </div>
  <?php else: ?>
    <?php foreach ($reservations as $reservation): ?>
      <div class="reservation-card">
        <div class="card-body p-4">
          <div class="row align-items-center">
            <div class="col-md-8">
              <div class="d-flex align-items-center mb-3">
                <h5 class="mb-0 me-3">
                  <?php echo htmlspecialchars($reservation['service_name'] ?: 'General Appointment'); ?>
                </h5>
                <span class="status-badge <?php echo getStatusBadgeClass($reservation['status']); ?>">
                  <?php echo ucfirst($reservation['status']); ?>
                </span>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="detail-item">
                    <i class="bi bi-calendar"></i>
                    <span><?php echo formatDate($reservation['appointment_date']); ?></span>
                  </div>
                  <div class="detail-item">
                    <i class="bi bi-clock"></i>
                    <span><?php echo formatTime($reservation['appointment_time']); ?></span>
                  </div>
                  <?php if ($reservation['duration']): ?>
                  <div class="detail-item">
                    <i class="bi bi-hourglass-split"></i>
                    <span><?php echo $reservation['duration']; ?> minutes</span>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="col-md-6">
                  <?php if ($reservation['employee_first_name']): ?>
                  <div class="detail-item">
                    <i class="bi bi-person-badge"></i>
                    <span><?php echo htmlspecialchars($reservation['employee_first_name'] . ' ' . $reservation['employee_last_name']); ?></span>
                  </div>
                  <?php endif; ?>
                  <?php if ($reservation['price']): ?>
                  <div class="detail-item">
                    <i class="bi bi-tag"></i>
                    <span>₱<?php echo number_format($reservation['price'], 2); ?></span>
                  </div>
                  <?php endif; ?>
                  <?php if ($reservation['purpose']): ?>
                  <div class="detail-item">
                    <i class="bi bi-chat-dots"></i>
                    <span><?php echo htmlspecialchars($reservation['purpose']); ?></span>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              
              <div class="mt-3">
                <small class="text-muted">
                  <i class="bi bi-clock-history"></i> 
                  Booked on: <?php echo formatDate($reservation['created_at']); ?>
                </small>
              </div>
            </div>
            
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
              <?php if ($reservation['status'] == 'pending' || $reservation['status'] == 'approved'): ?>
                <?php if (strtotime($reservation['appointment_date']) >= strtotime('today')): ?>
                  <button class="btn reschedule-btn m-1" onclick="rescheduleAppointment(<?php echo $reservation['appointment_id']; ?>)">
                    <i class="bi bi-pencil"></i> Reschedule
                  </button>
                  <button class="btn cancel-btn m-1" onclick="cancelAppointment(<?php echo $reservation['appointment_id']; ?>)">
                    <i class="bi bi-x-circle"></i> Cancel
                  </button>
                <?php endif; ?>
              <?php elseif ($reservation['status'] == 'completed'): ?>
                <button class="btn btn-outline-success m-1" onclick="bookAgain(<?php echo $reservation['service_id'] ?? 'null'; ?>)">
                  <i class="bi bi-arrow-repeat"></i> Book Again
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

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
          <li><a href="index.php" class="text-decoration-none text-white-50">Home</a></li>
          <li><a href="index.php#about" class="text-decoration-none text-white-50">About Us</a></li>
          <?php if ($is_logged_in): ?>
          <li><a href="index.php?page=my-reservations" class="text-decoration-none text-white-50">My Appointments</a></li>
          <?php endif; ?>
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
  // Function to check login before booking
  function checkLoginAndBook(serviceId = null) {
    <?php if (!$is_logged_in): ?>
      const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
      loginModal.show();
      
      const modalBody = document.querySelector('#loginModal .modal-body');
      const existingAlert = modalBody.querySelector('.alert-info');
      if (!existingAlert) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-info alert-custom';
        alertDiv.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i>Please log in to make a reservation.';
        modalBody.insertBefore(alertDiv, modalBody.firstChild);
        
        setTimeout(() => {
          if (alertDiv.parentNode) {
            alertDiv.remove();
          }
        }, 5000);
      }
      
      if (serviceId) {
        sessionStorage.setItem('booking_service_id', serviceId);
      }
      sessionStorage.setItem('booking_intent', 'true');
    <?php else: ?>
      let url = './assets/pages/appointment.php';
      if (serviceId) {
        url += '?service=' + serviceId;
      }
      window.location.href = url;
    <?php endif; ?>
  }
  
  function cancelAppointment(appointmentId) {
    if (confirm('Are you sure you want to cancel this appointment?')) {
      fetch('client/cancel-appointment.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'appointment_id=' + appointmentId
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          alert('Appointment cancelled successfully!');
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('An error occurred. Please try again.');
      });
    }
  }
  
  function rescheduleAppointment(appointmentId) {
    window.location.href = 'assets/pages/reschedule.php?id=' + appointmentId;
  }
  
  function bookAgain(serviceId) {
    let url = './assets/pages/appointment.php';
    if (serviceId && serviceId !== 'null') {
      url += '?service=' + serviceId;
    }
    window.location.href = url;
  }
  
  // Auto-show modal if there was a login error
  <?php if ($login_error): ?>
    document.addEventListener('DOMContentLoaded', function() {
      var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
      loginModal.show();
    });
  <?php endif; ?>
</script>
</body>
</html>