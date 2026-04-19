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
            $_SESSION['customer_id'] = $user['customer_id'];

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
$services_query = "SELECT * FROM services WHERE status = 'active' ORDER BY category ASC, service_name ASC";
$services_result = $con->query($services_query);
$services = $services_result ? $services_result->fetch_all(MYSQLI_ASSOC) : [];

// Group services by category
$grouped_services = [];
foreach ($services as $service) {
    $category = !empty($service['category']) ? $service['category'] : 'Other Services';
    $grouped_services[$category][] = $service;
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['client_id']);
$client_name = $is_logged_in && isset($_SESSION['client_name']) ? $_SESSION['client_name'] : '';
$client_id = $is_logged_in ? (int)$_SESSION['client_id'] : null;

// Determine which page to show
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Fetch reservations if on my-reservations page
$reservations = [];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($page == 'my-reservations' && $is_logged_in) {
    $customer_id = null;

    if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
        $customer_id = (int) $_SESSION['customer_id'];
    } else {
        $cust_query = mysqli_query($con, "SELECT customer_id FROM tbl_client_accounts WHERE client_id = $client_id LIMIT 1");
        if ($cust_query && mysqli_num_rows($cust_query) > 0) {
            $cust = mysqli_fetch_assoc($cust_query);
            if (!empty($cust['customer_id'])) {
                $customer_id = (int) $cust['customer_id'];
                $_SESSION['customer_id'] = $customer_id;
            }
        }
    }

    if ($customer_id) {
        $where_clause = "a.customer_id = $customer_id";
        switch ($filter) {
            case 'upcoming':
                $where_clause .= " AND a.appointment_date >= CURDATE() AND a.status IN ('pending', 'approved', 'confirmed')";
                break;
            case 'completed':
                $where_clause .= " AND a.status = 'completed'";
                break;
            case 'cancelled':
                $where_clause .= " AND a.status = 'cancelled'";
                break;
            case 'all':
            default:
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
                s.service_name,
                s.price,
                s.duration,
                s.service_id,
                e.first_name as employee_first_name,
                e.last_name as employee_last_name,
                c.first_name as customer_first,
                c.last_name as customer_last,
                c.phone as customer_phone,
                c.email as customer_email
            FROM appointments a
            LEFT JOIN customer_services cs ON a.appointment_id = cs.appointment_id
            LEFT JOIN services s ON cs.service_id = s.service_id
            LEFT JOIN employees e ON a.employee_id = e.employee_id
            LEFT JOIN customers c ON a.customer_id = c.customer_id
            WHERE $where_clause
            GROUP BY a.appointment_id
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ";

        $reservations_result = $con->query($reservations_query);
        if ($reservations_result) {
            $reservations = $reservations_result->fetch_all(MYSQLI_ASSOC);
        }
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
        if (strpos($service_lower, $keyword) !== false || strpos($category_lower, $keyword) !== false) {
            return $icon;
        }
    }

    return 'bi-stars';
}

// Get icon color based on category
function getIconColor($category) {
    $colors = [
        'hair' => 'text-primary',
        'nails' => 'text-danger',
        'skin' => 'text-success',
        'facial' => 'text-success',
        'massage' => 'text-warning',
        'body' => 'text-warning',
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
            return 'bg-warning text-dark';
        case 'approved':
        case 'confirmed':
            return 'bg-success';
        case 'completed':
            return 'bg-info text-dark';
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
      color: #2c3e50;
    }

    .navbar {
      background-color: #ffffff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }

    .navbar-brand {
      font-weight: 700;
      letter-spacing: 0.2px;
    }

    .nav-link {
      transition: color 0.3s ease;
      font-weight: 500;
    }

    .nav-link:hover {
      color: #e91e63 !important;
    }

    .nav-link.active {
      color: #e91e63 !important;
      font-weight: 600;
    }

    .dropdown-menu {
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.12);
      border-radius: 14px;
      overflow: hidden;
    }

    .dropdown-item {
      padding: 11px 20px;
      transition: background 0.3s ease;
    }

    .dropdown-item:hover {
      background: #fff5f7;
      color: #e91e63;
    }

    .dropdown-item.text-danger:hover {
      background: #fff1f1;
      color: #dc3545 !important;
    }

    .hero {
      background: url('./assets/img/BG.jpeg') no-repeat center center/cover;
      position: relative;
      color: #fff;
      text-align: center;
      padding: 140px 20px;
      overflow: hidden;
    }

    .hero::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(20,20,20,0.45), rgba(233,30,99,0.18));
      z-index: 1;
    }

    .hero .container {
      position: relative;
      z-index: 2;
    }

    .hero-badge {
      display: inline-block;
      padding: 8px 18px;
      border-radius: 30px;
      background: rgba(255,255,255,0.15);
      backdrop-filter: blur(4px);
      font-size: 0.9rem;
      margin-bottom: 18px;
      border: 1px solid rgba(255,255,255,0.2);
    }

    .hero h1 {
      font-size: 3.2rem;
      font-weight: 700;
      color: #fff;
      text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.35);
      margin-bottom: 15px;
    }

    .hero p {
      font-size: 1.15rem;
      max-width: 740px;
      margin: 0 auto 25px;
      color: rgba(255,255,255,0.95);
    }

    .btn-book {
      background: linear-gradient(135deg, #e91e63, #ff6b6b);
      border: none;
      border-radius: 30px;
      transition: all 0.3s ease;
      font-weight: 600;
      box-shadow: 0 8px 20px rgba(233,30,99,0.25);
    }

    .btn-book:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 25px rgba(233,30,99,0.32);
      background: linear-gradient(135deg, #d81b60, #ff5f6d);
    }

    .section-title {
      font-weight: 700;
      margin-bottom: 1rem;
      position: relative;
      display: inline-block;
    }

    .section-title:after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 65px;
      height: 3px;
      background: linear-gradient(90deg, #e91e63, #ff6b6b);
      border-radius: 2px;
    }

    .services-section {
      background: linear-gradient(180deg, #fffaf7 0%, #fff 100%);
    }

    .category-section {
      margin-bottom: 3.5rem;
    }

    .category-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 12px;
    }

    .category-title {
      font-size: 1.6rem;
      font-weight: 700;
      color: #2c3e50;
      margin: 0;
    }

    .category-subtitle {
      color: #8a8f98;
      font-size: 0.95rem;
      margin: 0;
    }

    .category-line {
      height: 3px;
      width: 85px;
      border-radius: 50px;
      background: linear-gradient(90deg, #e91e63, #ff6b6b);
    }

    .service-card {
      border: none;
      border-radius: 22px;
      transition: all 0.3s ease;
      background: white;
      box-shadow: 0 8px 30px rgba(0,0,0,0.06);
      height: 100%;
      overflow: hidden;
      position: relative;
    }

    .service-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 18px 38px rgba(0,0,0,0.12);
    }

    .service-card .card-body {
      padding: 1.75rem;
    }

    .service-badge {
      display: inline-block;
      padding: 6px 14px;
      border-radius: 30px;
      background: #fff1f5;
      color: #e91e63;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 12px;
    }

    .service-icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #fff1f5, #ffe7ef);
      font-size: 2rem;
      margin-bottom: 1rem;
      transition: transform 0.3s ease;
    }

    .service-card:hover .service-icon {
      transform: scale(1.08);
    }

    .service-name {
      font-size: 1.2rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 0.75rem;
    }

    .service-meta {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 1rem;
    }

    .duration-badge,
    .price-badge {
      padding: 7px 14px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .duration-badge {
      background: #f8f9fa;
      color: #666;
    }

    .price-badge {
      background: #fff1f5;
      color: #e91e63;
    }

    .service-description {
      color: #6c757d;
      font-size: 0.95rem;
      line-height: 1.7;
      margin: 0;
    }

    .no-services {
      text-align: center;
      background: white;
      border-radius: 20px;
      padding: 50px 20px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.06);
    }

    #about img {
      height: 340px;
      width: 100%;
      object-fit: cover;
      border-radius: 18px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.10);
    }

    .about-card {
      background: white;
      border: none;
      border-radius: 22px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.06);
      padding: 2rem;
    }

    .feature-check {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 14px;
      color: #495057;
      font-weight: 500;
    }

    .booking-card {
      border-radius: 24px;
      box-shadow: 0 12px 35px rgba(0,0,0,0.08);
      background: white;
    }

    .contact-tile i {
      color: #e91e63 !important;
    }

    footer {
      background-color: #2c3e50;
      color: #e2e2e2;
      text-align: center;
      padding: 40px 0 25px;
    }

    .footer-link {
      color: rgba(255,255,255,0.75);
      text-decoration: none;
    }

    .footer-link:hover {
      color: white;
    }

    .page-header {
      background: linear-gradient(135deg, #e91e63, #ff6b6b);
      color: white;
      padding: 40px 0;
      margin-bottom: 30px;
    }

    .filter-card {
      border: none;
      border-radius: 18px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.05);
      margin-bottom: 30px;
      background: white;
    }

    .reservation-card {
      border: none;
      border-radius: 18px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.06);
      margin-bottom: 20px;
      transition: transform 0.3s ease;
      background: white;
    }

    .reservation-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .status-badge {
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 18px;
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
      font-weight: 500;
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

    .modal-login {
      border-radius: 22px;
    }

    .modal-login .modal-content {
      border-radius: 22px;
      border: none;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }

    .modal-login .modal-header {
      background: linear-gradient(135deg, #e91e63, #ff6b6b);
      color: white;
      border-radius: 22px 22px 0 0;
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
      width: 72px;
      height: 72px;
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

    .user-dropdown-toggle {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .user-dropdown-toggle i {
      font-size: 1.2rem;
    }

    @media (max-width: 991px) {
      .hero {
        padding: 110px 20px;
      }

      .hero h1 {
        font-size: 2.4rem;
      }
    }

    @media (max-width: 576px) {
      .hero h1 {
        font-size: 2rem;
      }

      .hero p {
        font-size: 1rem;
      }

      .category-title {
        font-size: 1.35rem;
      }
    }
  </style>
</head>
<body>

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
          <li class="nav-item">
            <a href="./client/my-reservations.php" class="nav-link">
              <i class="bi bi-calendar-check"></i> My Appointments
            </a>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle user-dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle"></i>
              <?php echo htmlspecialchars($client_name); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
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

<section class="hero">
  <div class="container">
    <div class="hero-badge">
      Luxury • Beauty • Wellness
    </div>
    <h1>Welcome to R-Well Salon & Spa</h1>
    <p>Experience professional beauty care, premium treatments, and a relaxing atmosphere designed to make every visit feel special.</p>
    <a href="javascript:void(0)" class="btn btn-book btn-lg text-white mt-2 px-4 py-2" onclick="checkLoginAndBook()">
      <i class="bi bi-calendar-check"></i> Reserve Your Visit
    </a>
  </div>
</section>

<section id="services" class="py-5 services-section">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Our Premium Services</h2>
      <p class="text-muted mb-0">Explore our salon and spa treatments by category</p>
    </div>

    <?php if (!empty($grouped_services)): ?>
      <?php foreach ($grouped_services as $category => $category_services): ?>
        <div class="category-section">
          <div class="category-header">
            <div>
              <h3 class="category-title"><?php echo htmlspecialchars($category); ?></h3>
              <p class="category-subtitle">
                <?php echo count($category_services); ?> service<?php echo count($category_services) > 1 ? 's' : ''; ?> available
              </p>
            </div>
            <div class="category-line"></div>
          </div>

          <div class="row g-4">
            <?php foreach ($category_services as $service): ?>
              <div class="col-md-6 col-lg-4 d-flex">
                <div class="card service-card w-100">
                  <div class="card-body text-center d-flex flex-column">
                    <div class="service-badge">
                      <?php echo htmlspecialchars($category); ?>
                    </div>

                    <div class="service-icon <?php echo getIconColor($service['category'] ?? ''); ?>">
                      <i class="bi <?php echo getServiceIcon($service['service_name'], $service['category'] ?? ''); ?>"></i>
                    </div>

                    <h4 class="service-name">
                      <?php echo htmlspecialchars($service['service_name']); ?>
                    </h4>

                    <div class="service-meta">
                      <span class="duration-badge">
                        <i class="bi bi-clock me-1"></i>
                        <?php echo (int)$service['duration']; ?> mins
                      </span>
                      <span class="price-badge">
                        ₱<?php echo number_format($service['price'], 2); ?>
                      </span>
                    </div>

                    <p class="service-description mt-2">
                      <?php
                      $desc = htmlspecialchars($service['description']);
                      echo !empty($desc)
                        ? (strlen($desc) > 140 ? substr($desc, 0, 140) . '...' : $desc)
                        : 'Enjoy a premium salon and spa experience handled by our skilled professionals.';
                      ?>
                    </p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-services">
        <i class="bi bi-stars fs-1 text-muted"></i>
        <h4 class="mt-3">Services will be available soon</h4>
        <p class="text-muted mb-0">We’re currently updating our service menu. Please check back later.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<section id="about" class="py-5 bg-light">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-md-6">
        <div class="about-card">
          <h2 class="section-title text-start d-inline-block">About R-Well Salon & Spa</h2>
          <p class="mt-4" style="font-size: 1.05rem; line-height: 1.85;">
            At R-Well, we believe beauty and wellness go hand in hand. Our team of experienced professionals is dedicated to providing high-quality services in a relaxing and luxurious environment.
          </p>
          <p style="line-height: 1.85;">
            We use premium products and modern techniques to help you feel refreshed, confident, and cared for. Whether you're here for a simple haircut or a full self-care session, your comfort and satisfaction come first.
          </p>

          <div class="row mt-4">
            <div class="col-sm-6">
              <div class="feature-check">
                <i class="bi bi-check-circle-fill text-success"></i>
                <span>Expert Professionals</span>
              </div>
              <div class="feature-check">
                <i class="bi bi-check-circle-fill text-success"></i>
                <span>Premium Products</span>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="feature-check">
                <i class="bi bi-check-circle-fill text-success"></i>
                <span>Relaxing Atmosphere</span>
              </div>
              <div class="feature-check">
                <i class="bi bi-check-circle-fill text-success"></i>
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

<section id="booking" class="py-5 text-center" style="background: linear-gradient(135deg, #fff5f7 0%, #ffe6ea 100%);">
  <div class="container">
    <h2 class="section-title">Book an Appointment</h2>
    <p class="mb-4">Ready to experience our services? Schedule your visit today.</p>
    <div class="row justify-content-center">
      <div class="col-md-9 col-lg-8">
        <div class="card booking-card border-0 p-4">
          <div class="row text-center">
            <div class="col-md-4 mb-3 contact-tile">
              <i class="bi bi-telephone-fill fs-1"></i>
              <h6 class="mt-2">Call Us</h6>
              <p class="text-muted mb-0">(02) 1234 5678</p>
            </div>
            <div class="col-md-4 mb-3 contact-tile">
              <i class="bi bi-envelope-fill fs-1"></i>
              <h6 class="mt-2">Email Us</h6>
              <p class="text-muted mb-0">info@rwellsalon.com</p>
            </div>
            <div class="col-md-4 mb-3 contact-tile">
              <i class="bi bi-geo-alt-fill fs-1"></i>
              <h6 class="mt-2">Visit Us</h6>
              <p class="text-muted mb-0">Rizal, Cabugao, Ilocos Sur</p>
            </div>
          </div>
          <div class="mt-3">
            <a href="javascript:void(0)" onclick="checkLoginAndBook()" class="btn btn-book btn-lg text-white px-5">
              <i class="bi bi-calendar-check"></i> Book Your Appointment
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php elseif ($page == 'my-reservations' && $is_logged_in): ?>

<div class="page-header">
  <div class="container">
    <h1 class="display-6 mb-2">
      <i class="bi bi-calendar-check me-2"></i>My Appointments
    </h1>
    <p class="lead mb-0">View and manage all your appointments</p>
  </div>
</div>

<div class="container mb-5">
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
      <a href="javascript:void(0)" onclick="checkLoginAndBook()" class="btn btn-book text-white px-4 py-2" style="border: none;">
        <i class="bi bi-calendar-plus"></i> Book an Appointment
      </a>
    </div>
  <?php else: ?>
    <?php foreach ($reservations as $reservation): ?>
      <div class="reservation-card">
        <div class="card-body p-4">
          <div class="row align-items-center">
            <div class="col-md-8">
              <div class="d-flex align-items-center mb-3 flex-wrap gap-2">
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
              <?php if (in_array(strtolower($reservation['status']), ['pending', 'approved', 'confirmed'])): ?>
                <?php if (strtotime($reservation['appointment_date']) >= strtotime('today')): ?>
                  <button class="btn reschedule-btn m-1" onclick="rescheduleAppointment(<?php echo $reservation['appointment_id']; ?>)">
                    <i class="bi bi-pencil"></i> Reschedule
                  </button>
                  <button class="btn cancel-btn m-1" onclick="cancelAppointment(<?php echo $reservation['appointment_id']; ?>)">
                    <i class="bi bi-x-circle"></i> Cancel
                  </button>
                <?php endif; ?>
              <?php elseif (strtolower($reservation['status']) == 'completed'): ?>
                <button class="btn btn-outline-success m-1" onclick="bookAgain(<?php echo isset($reservation['service_id']) ? (int)$reservation['service_id'] : 'null'; ?>)">
                  <i class="bi bi-arrow-repeat"></i> Book Again
                </button>
              <?php endif; ?>

              <a href="client/reservation-details.php?id=<?php echo $reservation['appointment_id']; ?>" class="btn btn-outline-secondary m-1">
                <i class="bi bi-eye"></i> View Details
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<footer>
  <div class="container">
    <div class="row text-start text-md-start">
      <div class="col-md-4 mb-3">
        <h5>R-Well Salon & Spa</h5>
        <p class="small mb-0">Your beauty and relaxation destination for premium salon and spa care.</p>
      </div>
      <div class="col-md-4 mb-3">
        <h5>Quick Links</h5>
        <ul class="list-unstyled mb-0">
          <li><a href="index.php" class="footer-link">Home</a></li>
          <li><a href="index.php#services" class="footer-link">Services</a></li>
          <li><a href="index.php#about" class="footer-link">About Us</a></li>
          <?php if ($is_logged_in): ?>
          <li><a href="client/my-reservations.php" class="footer-link">My Appointments</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-md-4 mb-3">
        <h5>Follow Us</h5>
        <div class="d-flex gap-3">
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
      .catch(() => {
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

  <?php if ($login_error): ?>
    document.addEventListener('DOMContentLoaded', function() {
      var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
      loginModal.show();
    });
  <?php endif; ?>
</script>
</body>
</html>