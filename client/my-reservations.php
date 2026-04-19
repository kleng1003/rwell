<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: ../index.php');
    exit();
}

// Include database connection
require_once '../admin/include/connection.php';

$client_id = $_SESSION['client_id'];
$client_name = isset($_SESSION['client_name']) ? $_SESSION['client_name'] : 'Client';

// Get the customer_id linked to this client account
$customer_id = null;

if (isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'];
} else {
    // Try to get from database
    $customer_query = mysqli_query($con, "SELECT customer_id FROM tbl_client_accounts WHERE client_id = $client_id");
    if ($customer_query && mysqli_num_rows($customer_query) > 0) {
        $cust = mysqli_fetch_assoc($customer_query);
        $customer_id = $cust['customer_id'];
        $_SESSION['customer_id'] = $customer_id;
    }
}

// If still no customer_id, try to find by phone/email
if (!$customer_id && isset($_SESSION['client_contact'])) {
    $phone = $_SESSION['client_contact'];
    $customer_query = mysqli_query($con, "SELECT customer_id FROM customers WHERE phone = '$phone' LIMIT 1");
    if ($customer_query && mysqli_num_rows($customer_query) > 0) {
        $cust = mysqli_fetch_assoc($customer_query);
        $customer_id = $cust['customer_id'];
        $_SESSION['customer_id'] = $customer_id;
        
        // Update the client account with this customer_id
        mysqli_query($con, "UPDATE tbl_client_accounts SET customer_id = $customer_id WHERE client_id = $client_id");
    }
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$reservations = [];

if ($customer_id) {
    // Build query based on filter
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
    
    // Fetch reservations
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
            e.last_name as employee_last_name
        FROM appointments a
        LEFT JOIN customer_services cs ON a.appointment_id = cs.appointment_id
        LEFT JOIN services s ON cs.service_id = s.service_id
        LEFT JOIN employees e ON a.employee_id = e.employee_id
        WHERE $where_clause
        GROUP BY a.appointment_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ";
    
    $reservations_result = $con->query($reservations_query);
    if ($reservations_result) {
        $reservations = $reservations_result->fetch_all(MYSQLI_ASSOC);
    }
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-warning';
        case 'approved':
            return 'bg-success';
        case 'confirmed':
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - R-Well Salon & Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
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
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="../index.php">✨ R-Well Salon & Spa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div id="navbarNav" class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">
                        <i class="bi bi-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="my-reservations.php">
                        <i class="bi bi-calendar-check"></i> My Reservations
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($client_name); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-gear"></i> Profile Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1 class="display-6 mb-2">
            <i class="bi bi-calendar-check me-2"></i>My Reservations
        </h1>
        <p class="lead mb-0">View and manage all your appointments</p>
    </div>
</div>

<!-- Main Content -->
<div class="container mb-5">
    <!-- Filter Section -->
    <div class="filter-card">
        <div class="card-body">
            <div class="d-flex justify-content-center flex-wrap">
                <a href="?filter=all" class="btn btn-filter m-1 <?php echo $filter == 'all' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-list-ul"></i> All Reservations
                </a>
                <a href="?filter=upcoming" class="btn btn-filter m-1 <?php echo $filter == 'upcoming' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-calendar-event"></i> Upcoming
                </a>
                <a href="?filter=completed" class="btn btn-filter m-1 <?php echo $filter == 'completed' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-check-circle"></i> Completed
                </a>
                <a href="?filter=cancelled" class="btn btn-filter m-1 <?php echo $filter == 'cancelled' ? 'active' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-x-circle"></i> Cancelled
                </a>
            </div>
        </div>
    </div>

    <!-- Reservations List -->
    <?php if (empty($reservations)): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x"></i>
            <h3>No reservations found</h3>
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
                        echo "You haven't made any reservations yet.";
                }
                ?>
            </p>
            <a href="../assets/pages/appointment.php" class="btn btn-book text-white px-4 py-2" style="background: linear-gradient(135deg, #e91e63, #ff6b6b); border: none;">
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
                            <?php if (in_array(strtolower($reservation['status']), ['pending', 'confirmed', 'approved'])): ?>
                                <?php if (strtotime($reservation['appointment_date']) >= strtotime('today')): ?>
                                    <button class="btn reschedule-btn m-1" onclick="rescheduleAppointment(<?php echo $reservation['appointment_id']; ?>)">
                                        <i class="bi bi-pencil"></i> Reschedule
                                    </button>
                                    <button class="btn cancel-btn m-1" onclick="cancelAppointment(<?php echo $reservation['appointment_id']; ?>)">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </button>
                                <?php endif; ?>
                            <?php elseif ($reservation['status'] == 'completed'): ?>
                                <button class="btn btn-outline-success m-1" onclick="bookAgain(<?php echo $reservation['service_id'] ?? ''; ?>)">
                                    <i class="bi bi-arrow-repeat"></i> Book Again
                                </button>
                                <button class="btn btn-outline-primary m-1" onclick="leaveReview(<?php echo $reservation['appointment_id']; ?>)">
                                    <i class="bi bi-star"></i> Leave Review
                                </button>
                            <?php endif; ?>
                            
                            <a href="reservation-details.php?id=<?php echo $reservation['appointment_id']; ?>" class="btn btn-outline-secondary m-1">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer style="background-color: #2c3e50; color: #e2e2e2; text-align: center; padding: 30px 0; margin-top: 50px;">
    <div class="container">
        <p class="mb-0">&copy; 2025 R-Well Salon & Spa. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function cancelAppointment(appointmentId) {
        if (confirm('Are you sure you want to cancel this appointment?')) {
            fetch('cancel-appointment.php', {
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
        window.location.href = '../assets/pages/reschedule.php?id=' + appointmentId;
    }
    
    function bookAgain(serviceId) {
        if (serviceId) {
            window.location.href = '../assets/pages/appointment.php?service=' + serviceId;
        } else {
            window.location.href = '../assets/pages/appointment.php';
        }
    }
    
    function leaveReview(appointmentId) {
        window.location.href = 'leave-review.php?id=' + appointmentId;
    }
</script>
</body>
</html>