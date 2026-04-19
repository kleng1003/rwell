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