<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include database connection
include_once('connection.php');

// Include activity logger after connection is established
include_once('activity_logger.php');

// Log page access only for specific pages (optional, can be commented out if too many logs)
// Uncomment the line below if you want to log every page view
// logActivity("Viewed " . basename($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>RWELL</title>
<link rel="icon" href="../images/logo.png">

<!-- CSS -->
<link href="../css/bootstrap.min.css" rel="stylesheet">
<link href="../css/metisMenu.min.css" rel="stylesheet">
<link href="../css/dataTables/dataTables.bootstrap.css" rel="stylesheet">
<link href="../css/dataTables/dataTables.responsive.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>

<div id="wrapper">

    <!-- Navigation -->
    <nav class="navbar navbar-fixed-top" style="background-color: #191919;" role="navigation">
        <div class="navbar-header">
            <a class="navbar-brand" href="#" style="color: #999;">RWELL</a>
        </div>

        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>

        <ul class="nav navbar-right navbar-top-links">
            <li class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#" style="font-size:15px;">
                    <i class="fa fa-user fa-fw"></i> <?php echo $_SESSION['username']; ?> 
                    <?php if ($_SESSION['role'] == 'employee'): ?>
                        <span class="label label-info">Employee</span>
                    <?php else: ?>
                        <span class="label label-danger">Admin</span>
                    <?php endif; ?>
                    <b class="caret"></b>
                </a>
                <ul class="dropdown-menu dropdown-user">
                    <li>
                        <a href="../Pages/admin-view.php?id=<?php echo $_SESSION['userid']; ?>">
                            <i class="fa fa-user fa-fw"></i> User Profile
                        </a>
                    </li>
                    <li class="divider"></li>
                    <li>
                        <a href="../Functions/log_out.php">
                            <i class="fa fa-sign-out fa-fw"></i> Logout
                        </a>
                    </li>
                </ul>
            </li>
        </ul>

        <!-- Sidebar - Role-based menu -->
        <div class="navbar-default sidebar" role="navigation">
            <div class="sidebar-nav navbar-collapse">
                <ul class="nav" id="side-menu">
                    <li class="sidebar-search">
                        <div class="input-group custom-search-form">
                            <img style="border-radius: 50%;" src="../../assets/img/logo.png"> RWELL
                        </div>
                    </li>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <!-- Admin Menu -->
                        <li><a href="../Pages/index.php"><i class="fas fa-columns fa-fw"></i> Dashboard</a></li>
                        <li><a href="../Pages/admin-account.php"><i class="fas fa-user-shield fa-fw"></i> Admins</a></li>
                        <li><a href="../Pages/employees.php"><i class="fas fa-user-tie fa-fw"></i> Employees</a></li>
                        <li><a href="../Pages/services.php"><i class="fas fa-spa fa-fw"></i> Services</a></li>
                        <li><a href="../Pages/customers.php"><i class="fas fa-user fa-fw"></i> Customers</a></li>
                        <li><a href="../Pages/suppliers.php"><i class="fas fa-truck fa-fw"></i> Suppliers</a></li>
                        <li><a href="../Pages/products.php"><i class="fas fa-box fa-fw"></i> Products</a></li>
                        <li><a href="../Pages/appointments.php"><i class="fas fa-calendar fa-fw"></i> Appointments</a></li>
                        <li><a href="../Pages/activity_logs.php"><i class="fas fa-history fa-fw"></i> Activity Logs</a></li>
                    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'employee'): ?>
                        <!-- Employee Menu -->
                        <li><a href="../Pages/employee_dashboard.php"><i class="fas fa-columns fa-fw"></i> Dashboard</a></li>
                        <li><a href="../Pages/customers.php"><i class="fas fa-user fa-fw"></i> Customers</a></li>
                        <li><a href="../Pages/appointments.php?my=true"><i class="fas fa-calendar fa-fw"></i> My Appointments</a></li>
                        <li><a href="../Pages/appointment_add.php"><i class="fas fa-plus fa-fw"></i> New Appointment</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Wrapper -->
    <div id="page-wrapper">
        <div class="container-fluid">
            <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
             
