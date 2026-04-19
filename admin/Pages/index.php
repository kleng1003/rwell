<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Total Appointments
$appointment_query = mysqli_query($con, "SELECT COUNT(*) AS total FROM appointments");
$appointment_data = mysqli_fetch_assoc($appointment_query);

// Pending Appointments
$pending_query = mysqli_query($con, "SELECT COUNT(*) AS total FROM appointments WHERE status = 'pending'");
$pending_data = mysqli_fetch_assoc($pending_query);

// Today's Appointments
$today_query = mysqli_query($con, "SELECT COUNT(*) AS total FROM appointments WHERE appointment_date = CURDATE()");
$today_data = mysqli_fetch_assoc($today_query);

// Total Employees
$employee_query = mysqli_query($con, "SELECT COUNT(*) AS total FROM employees WHERE status = 'active'");
$employee_data = mysqli_fetch_assoc($employee_query);

// Total Customers
$customer_query = mysqli_query($con, "SELECT COUNT(*) AS total FROM customers");
$customer_data = mysqli_fetch_assoc($customer_query);

// Low Stock Products
$lowstock_query = mysqli_query($con, "SELECT COUNT(*) AS total FROM products WHERE stock < 10 AND status = 'available'");
$lowstock_data = mysqli_fetch_assoc($lowstock_query);

// Total Products
$products_query = mysqli_query($con, "SELECT COUNT(*) AS total FROM products WHERE status = 'available'");
$products_data = mysqli_fetch_assoc($products_query);

// Total Services
$services_query = mysqli_query($con, "SELECT COUNT(*) AS total FROM services WHERE status = 'active'");
$services_data = mysqli_fetch_assoc($services_query);

// Get appointment statistics by status
$status_stats = [];
$statuses = ['pending', 'approved', 'completed', 'cancelled'];
foreach ($statuses as $status) {
    $result = mysqli_query($con, "SELECT COUNT(*) AS total FROM appointments WHERE status = '$status'");
    $data = mysqli_fetch_assoc($result);
    $status_stats[$status] = $data['total'];
}

// Get recent appointments
$recent_appointments_query = mysqli_query($con, "
    SELECT a.*, 
           c.first_name as customer_first, 
           c.last_name as customer_last,
           e.first_name as employee_first, 
           e.last_name as employee_last
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN employees e ON a.employee_id = e.employee_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC 
    LIMIT 10
");

// Get today's appointments
$today_appointments_query = mysqli_query($con, "
    SELECT a.*, 
           c.first_name as customer_first, 
           c.last_name as customer_last,
           e.first_name as employee_first, 
           e.last_name as employee_last
    FROM appointments a
    LEFT JOIN customers c ON a.customer_id = c.customer_id
    LEFT JOIN employees e ON a.employee_id = e.employee_id
    WHERE a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
");

// Get low stock products
$lowstock_products_query = mysqli_query($con, "
    SELECT product_id, product_name, stock, price, category
    FROM products 
    WHERE stock < 10 AND status = 'available'
    ORDER BY stock ASC
    LIMIT 5
");
?>

<style>
    /* Dashboard Styles - Matching your template's color scheme */
    .dashboard-header {
        background: linear-gradient(135deg, #2c3e50, #464660);
        color: white;
        padding: 25px 20px;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .stat-card {
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 20px;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        background: #fff;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .stat-card .panel-heading {
        padding: 20px;
        border-bottom: none;
        color: white;
    }
    
    .stat-card .huge {
        font-size: 36px;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 5px;
    }
    
    .stat-card .stat-icon {
        font-size: 48px;
        opacity: 0.3;
        position: absolute;
        top: 15px;
        right: 15px;
    }
    
    .stat-card .panel-footer {
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 12px 20px;
    }
    
    .stat-card .panel-footer a {
        text-decoration: none;
        color: #495057;
        display: block;
    }
    
    .stat-card .panel-footer:hover {
        background: #e9ecef;
    }
    
    /* Card Colors - Matching your template's dark theme */
    .card-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
    .card-success { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .card-warning { background: linear-gradient(135deg, #ffc107, #d39e00); }
    .card-danger { background: linear-gradient(135deg, #dc3545, #bd2130); }
    .card-info { background: linear-gradient(135deg, #17a2b8, #117a8b); }
    .card-dark { background: linear-gradient(135deg, #343a40, #1d2124); }
    
    /* Table Styles */
    .recent-card {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        background: white;
        border: none;
    }
    
    .recent-card .panel-heading {
        background: white;
        color: #495057;
        padding: 15px 20px;
        border-bottom: 2px solid #f0f0f0;
        font-weight: 600;
    }
    
    .recent-card .panel-body {
        padding: 0;
        background: white;
    }
    
    .recent-card table {
        margin-bottom: 0;
    }
    
    .recent-card th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        padding: 12px !important;
        border-bottom: 2px solid #dee2e6;
    }
    
    .recent-card td {
        padding: 12px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    /* Status Badges */
    .status-badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        text-transform: capitalize;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #cce5ff; color: #004085; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    
    /* Quick Actions */
    .quick-actions {
        margin-bottom: 25px;
    }
    
    .action-btn {
        border-radius: 4px;
        padding: 10px 20px;
        margin-right: 10px;
        font-weight: 500;
        transition: all 0.3s;
        border: 1px solid #ddd;
        background: #fff;
        color: #495057;
        display: inline-block;
        margin-bottom: 10px;
    }
    
    .action-btn:hover {
        background: #464660;
        color: #fff;
        border-color: #191919;
        text-decoration: none;
    }
    
    .action-btn i {
        margin-right: 5px;
    }
    
    /* Widgets */
    .widget {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .widget-title {
        font-size: 16px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .schedule-item {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
    }
    
    .schedule-time {
        background: #f8f9fa;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        color: #495057;
        margin-right: 12px;
        min-width: 75px;
    }
    
    .schedule-info {
        flex: 1;
    }
    
    .schedule-info strong {
        color: #191919;
        display: block;
        margin-bottom: 2px;
        font-size: 14px;
    }
    
    .schedule-info small {
        color: #6c757d;
        font-size: 12px;
    }
    
    /* Metric Grid */
    .metric-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 10px;
    }
    
    .metric-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        text-align: center;
    }
    
    .metric-value {
        font-size: 24px;
        font-weight: 700;
        color: #191919;
        line-height: 1.2;
    }
    
    .metric-label {
        font-size: 12px;
        color: #6c757d;
        font-weight: 500;
    }
    
    /* Position relative for icon positioning */
    .panel-heading {
        position: relative;
        min-height: 100px;
    }
</style>

<div class="container-fluid">

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row">
            <div class="col-md-8">
                <h1 style="font-weight:600; margin:0;">Dashboard</h1>
                <p style="margin:10px 0 0; opacity:0.8;">Welcome back, <?php echo $_SESSION['username']; ?>!</p>
            </div>
            <div class="col-md-4 text-right">
                <span style="background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 4px;">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="appointments.php?action=new" class="action-btn">
            <i class="fas fa-plus"></i> New Appointment
        </a>
        <a href="customers.php?action=add" class="action-btn">
            <i class="fas fa-user-plus"></i> Add Customer
        </a>
        <a href="products.php?action=add" class="action-btn">
            <i class="fas fa-box"></i> Add Product
        </a>
        <a href="employees.php?action=add" class="action-btn">
            <i class="fas fa-user-tie"></i> Add Employee
        </a>
    </div>

    <!-- Statistics Cards Row 1 -->
    <div class="row">
        <!-- Total Appointments -->
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="panel-heading card-primary">
                    <div class="huge"><?php echo $appointment_data['total']; ?></div>
                    <div>Total Appointments</div>
                    <i class="fas fa-calendar-check stat-icon"></i>
                </div>
                <div class="panel-footer">
                    <a href="appointments.php">
                        <span class="pull-left">View Details</span>
                        <span class="pull-right"><i class="fas fa-arrow-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Today's Appointments -->
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="panel-heading card-success">
                    <div class="huge"><?php echo $today_data['total']; ?></div>
                    <div>Today's Schedule</div>
                    <i class="fas fa-clock stat-icon"></i>
                </div>
                <div class="panel-footer">
                    <a href="appointments.php?date=today">
                        <span class="pull-left">View Schedule</span>
                        <span class="pull-right"><i class="fas fa-arrow-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pending Appointments -->
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="panel-heading card-warning">
                    <div class="huge"><?php echo $pending_data['total']; ?></div>
                    <div>Pending</div>
                    <i class="fas fa-hourglass-half stat-icon"></i>
                </div>
                <div class="panel-footer">
                    <a href="appointments.php?status=pending">
                        <span class="pull-left">Review</span>
                        <span class="pull-right"><i class="fas fa-arrow-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Total Customers -->
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="panel-heading card-info">
                    <div class="huge"><?php echo $customer_data['total']; ?></div>
                    <div>Total Customers</div>
                    <i class="fas fa-users stat-icon"></i>
                </div>
                <div class="panel-footer">
                    <a href="customers.php">
                        <span class="pull-left">View Customers</span>
                        <span class="pull-right"><i class="fas fa-arrow-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row 2 -->
    <div class="row">
        <!-- Active Employees -->
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="panel-heading card-dark">
                    <div class="huge"><?php echo $employee_data['total']; ?></div>
                    <div>Active Staff</div>
                    <i class="fas fa-user-tie stat-icon"></i>
                </div>
                <div class="panel-footer">
                    <a href="employees.php">
                        <span class="pull-left">View Staff</span>
                        <span class="pull-right"><i class="fas fa-arrow-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Services -->
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="panel-heading card-success">
                    <div class="huge"><?php echo $services_data['total']; ?></div>
                    <div>Services</div>
                    <i class="fas fa-tags stat-icon"></i>
                </div>
                <div class="panel-footer">
                    <a href="services.php">
                        <span class="pull-left">View Services</span>
                        <span class="pull-right"><i class="fas fa-arrow-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="panel-heading card-primary">
                    <div class="huge"><?php echo $products_data['total']; ?></div>
                    <div>Products</div>
                    <i class="fas fa-boxes stat-icon"></i>
                </div>
                <div class="panel-footer">
                    <a href="products.php">
                        <span class="pull-left">View Products</span>
                        <span class="pull-right"><i class="fas fa-arrow-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-lg-3 col-md-6">
            <div class="stat-card">
                <div class="panel-heading card-danger">
                    <div class="huge"><?php echo $lowstock_data['total']; ?></div>
                    <div>Low Stock Items</div>
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                </div>
                <div class="panel-footer">
                    <a href="products.php?filter=lowstock">
                        <span class="pull-left">Check Inventory</span>
                        <span class="pull-right"><i class="fas fa-arrow-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row">
        <!-- Recent Appointments Table -->
        <div class="col-lg-8">
            <div class="panel recent-card">
                <div class="panel-heading">
                    <i class="fas fa-history" style="margin-right: 8px;"></i>
                    Recent Appointments
                    <div class="pull-right">
                        <a href="appointments.php" class="btn btn-xs btn-default">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($recent_appointments_query) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($recent_appointments_query)): ?>
                                        <tr>
                                            <td>#<?php echo $row['appointment_id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($row['customer_first'] . ' ' . $row['customer_last']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['employee_first'] . ' ' . $row['employee_last']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="appointments.php?view=<?php echo $row['appointment_id']; ?>" class="btn btn-xs btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="appointments.php?edit=<?php echo $row['appointment_id']; ?>" class="btn btn-xs btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No recent appointments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Today's Schedule Widget -->
            <div class="widget">
                <div class="widget-title">
                    <i class="fas fa-calendar-day"></i> Today's Schedule
                    <span class="badge pull-right"><?php echo $today_data['total']; ?></span>
                </div>
                <div class="schedule-list">
                    <?php 
                    if (mysqli_num_rows($today_appointments_query) > 0):
                        while($apt = mysqli_fetch_assoc($today_appointments_query)): 
                    ?>
                        <div class="schedule-item">
                            <span class="schedule-time"><?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></span>
                            <div class="schedule-info">
                                <strong><?php echo htmlspecialchars($apt['customer_first'] . ' ' . $apt['customer_last']); ?></strong>
                                <small>with <?php echo htmlspecialchars($apt['employee_first']); ?></small>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <p class="text-muted text-center">No appointments scheduled for today</p>
                    <?php endif; ?>
                </div>
                <a href="appointments.php?date=today" class="btn btn-default btn-block" style="margin-top: 15px;">
                    View Full Schedule <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <!-- Low Stock Alerts Widget -->
            <?php if (mysqli_num_rows($lowstock_products_query) > 0): ?>
            <div class="widget">
                <div class="widget-title">
                    <i class="fas fa-exclamation-circle" style="color: #dc3545;"></i> Low Stock Alerts
                    <span class="badge badge-danger pull-right"><?php echo $lowstock_data['total']; ?></span>
                </div>
                <div class="alert-list">
                    <?php while($product = mysqli_fetch_assoc($lowstock_products_query)): ?>
                        <div class="schedule-item">
                            <span class="schedule-time" style="background: #f8d7da; color: #721c24;">
                                Stock: <?php echo $product['stock']; ?>
                            </span>
                            <div class="schedule-info">
                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                <small><?php echo $product['category']; ?> • ₱<?php echo number_format($product['price'], 2); ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <a href="products.php?filter=lowstock" class="btn btn-default btn-block" style="margin-top: 15px;">
                    Manage Inventory <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?>

            <!-- Quick Stats Widget -->
            <div class="widget">
                <div class="widget-title">
                    <i class="fas fa-chart-pie"></i> Appointment Status
                </div>
                <div class="metric-grid">
                    <div class="metric-item">
                        <div class="metric-value"><?php echo $status_stats['pending']; ?></div>
                        <div class="metric-label">Pending</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-value"><?php echo $status_stats['approved']; ?></div>
                        <div class="metric-label">Approved</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-value"><?php echo $status_stats['completed']; ?></div>
                        <div class="metric-label">Completed</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-value"><?php echo $status_stats['cancelled']; ?></div>
                        <div class="metric-label">Cancelled</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>