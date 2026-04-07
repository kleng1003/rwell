<?php
// ../reports/customer-list.php
include_once('../include/connection.php');

// Start session to check login
session_start();
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Get all customers
$sql = "SELECT * FROM customers ORDER BY created_at DESC";
$result = mysqli_query($con, $sql);

// Get appointment stats for each customer
$appointment_stats = [];
$stats_query = mysqli_query($con, "
    SELECT customer_id, COUNT(*) as appointment_count, MAX(appointment_date) as last_visit
    FROM appointments 
    GROUP BY customer_id
");
while ($stat = mysqli_fetch_assoc($stats_query)) {
    $appointment_stats[$stat['customer_id']] = $stat;
}

// Get company info
$company_name = "RWELL";
$report_title = "Customer List Report";
$report_date = date('F j, Y');
$report_time = date('h:i A');
$total_customers = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $company_name; ?> - Customer List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Print Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background: #fff;
        }
        
        .print-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Header Styles */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #464660;
        }
        
        .company-name {
            font-size: 32px;
            font-weight: 800;
            color: #464660;
            margin: 0;
            letter-spacing: 1px;
        }
        
        .report-title {
            font-size: 24px;
            color: #666;
            margin: 10px 0 5px;
            font-weight: 600;
        }
        
        .report-subtitle {
            color: #888;
            font-size: 14px;
            margin: 5px 0;
        }
        
        /* Info Bar */
        .info-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 5px solid #464660;
        }
        
        .info-item {
            font-size: 14px;
            color: #555;
        }
        
        .info-item i {
            color: #464660;
            margin-right: 8px;
            width: 16px;
        }
        
        .badge-total {
            background: #464660;
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
        }
        
        th {
            background: #464660;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid #5a5a7a;
        }
        
        td {
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        tr:hover {
            background: #f1f1f1;
        }
        
        .customer-name {
            font-weight: 700;
            color: #464660;
        }
        
        .contact-info {
            font-size: 12px;
            color: #666;
        }
        
        .contact-info i {
            width: 14px;
            color: #464660;
            margin-right: 5px;
        }
        
        .appointment-badge {
            background: #e3f2fd;
            color: #0d47a1;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        
        .loyal-badge {
            background: #fff3cd;
            color: #856404;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-left: 5px;
        }
        
        .address {
            max-width: 200px;
            font-size: 12px;
            color: #666;
        }
        
        .empty-data {
            color: #999;
            font-style: italic;
        }
        
        /* Footer */
        .report-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px dashed #ddd;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #777;
        }
        
        .signature-line {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature {
            text-align: center;
            width: 200px;
        }
        
        .signature-line-bottom {
            margin-top: 5px;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        /* Summary Cards */
        .summary-section {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .summary-card {
            flex: 1;
            background: linear-gradient(135deg, #464660, #64648c);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .summary-card .number {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .summary-card .label {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        /* Print Button */
        .print-button {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .btn-print {
            background: #464660;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .btn-print:hover {
            background: #5a5a7a;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        
        .btn-print i {
            margin-right: 8px;
        }
        
        /* Watermark */
        .watermark {
            position: fixed;
            bottom: 20px;
            right: 20px;
            opacity: 0.1;
            font-size: 80px;
            color: #464660;
            z-index: -1;
            transform: rotate(-15deg);
            pointer-events: none;
        }
        
        /* Print Media Query */
        @media print {
            .print-button {
                display: none;
            }
            
            .watermark {
                opacity: 0.05;
            }
            
            body {
                padding: 0;
            }
            
            th {
                background: #464660 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .summary-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .badge-total {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .appointment-badge, .loyal-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .summary-section {
                flex-direction: column;
            }
            
            .info-bar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        
        <!-- Print Button (hidden when printing) -->
        <div class="print-button">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Print / Save PDF
            </button>
            <button onclick="window.close()" class="btn-print" style="background: #6c757d; margin-left: 10px;">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        
        <!-- Watermark -->
        <div class="watermark">
            <i class="fas fa-users"></i>
        </div>
        
        <!-- Report Header -->
        <div class="report-header">
            <h1 class="company-name"><?php echo $company_name; ?></h1>
            <h2 class="report-title"><?php echo $report_title; ?></h2>
            <div class="report-subtitle">
                <i class="fas fa-calendar-alt"></i> Generated on: <?php echo $report_date . ' at ' . $report_time; ?>
            </div>
        </div>
        
        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="number"><?php echo $total_customers; ?></div>
                <div class="label">Total Customers</div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #28a745, #34ce57);">
                <div class="number">
                    <?php 
                    $active_count = 0;
                    mysqli_data_seek($result, 0);
                    while ($row = mysqli_fetch_assoc($result)) {
                        if (!isset($row['status']) || $row['status'] == 'active') {
                            $active_count++;
                        }
                    }
                    echo $active_count;
                    ?>
                </div>
                <div class="label">Active Customers</div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #ffc107, #ffdb6d);">
                <div class="number">
                    <?php 
                    $loyal_count = 0;
                    mysqli_data_seek($result, 0);
                    while ($row = mysqli_fetch_assoc($result)) {
                        if (isset($appointment_stats[$row['customer_id']]) && $appointment_stats[$row['customer_id']]['appointment_count'] > 5) {
                            $loyal_count++;
                        }
                    }
                    echo $loyal_count;
                    ?>
                </div>
                <div class="label">Loyal Customers</div>
            </div>
        </div>
        
        <!-- Info Bar -->
        <div class="info-bar">
            <div class="info-item">
                <i class="fas fa-file-pdf"></i> Report ID: #CUST-<?php echo date('Ymd') . '-' . rand(1000, 9999); ?>
            </div>
            <div class="info-item">
                <i class="fas fa-user"></i> Generated by: <?php echo $_SESSION['username']; ?>
            </div>
            <div class="badge-total">
                <i class="fas fa-database"></i> Total: <?php echo $total_customers; ?> customers
            </div>
        </div>
        
        <!-- Customers Table -->
        <table>
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="20%">Customer Name</th>
                    <th width="15%">Contact Number</th>
                    <th width="20%">Email Address</th>
                    <th width="20%">Address</th>
                    <th width="10%">Appointments</th>
                    <th width="10%">Last Visit</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                mysqli_data_seek($result, 0);
                $counter = 1;
                if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)): 
                        $fullName = $row["first_name"] . " " . $row["last_name"];
                        $appointment_count = isset($appointment_stats[$row['customer_id']]) ? $appointment_stats[$row['customer_id']]['appointment_count'] : 0;
                        $last_visit = isset($appointment_stats[$row['customer_id']]) ? $appointment_stats[$row['customer_id']]['last_visit'] : null;
                        
                        // Check if customer is loyal
                        $is_loyal = $appointment_count > 5;
                ?>
                <tr>
                    <td style="text-align: center; font-weight: 600;"><?php echo $counter++; ?></td>
                    <td>
                        <span class="customer-name">
                            <?php echo htmlspecialchars($fullName); ?>
                        </span>
                        <?php if ($is_loyal): ?>
                            <span class="loyal-badge">
                                <i class="fas fa-crown"></i> Loyal
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="contact-info">
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row["phone"]); ?>
                        </div>
                    </td>
                    <td>
                        <div class="contact-info">
                            <i class="fas fa-envelope"></i> 
                            <?php echo !empty($row["email"]) ? htmlspecialchars($row["email"]) : '<span class="empty-data">No email</span>'; ?>
                        </div>
                    </td>
                    <td>
                        <div class="address">
                            <?php echo !empty($row["address"]) ? htmlspecialchars($row["address"]) : '<span class="empty-data">No address</span>'; ?>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <span class="appointment-badge">
                            <i class="fas fa-calendar-check"></i> <?php echo $appointment_count; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($last_visit): ?>
                            <i class="fas fa-clock" style="color: #666; margin-right: 3px;"></i>
                            <?php echo date('M d, Y', strtotime($last_visit)); ?>
                        <?php else: ?>
                            <span class="empty-data">No visits</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 24px; color: #999; margin-bottom: 10px;"></i>
                        <br>
                        <span style="color: #999;">No customers found in the database.</span>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Additional Statistics -->
        <div style="margin: 30px 0 20px;">
            <table style="width: 100%; border: none; background: none;">
                <tr>
                    <td style="border: none; padding: 5px; background: none;">
                        <strong>Summary Statistics:</strong>
                    </td>
                </tr>
                <tr>
                    <td style="border: none; padding: 5px; background: none;">• Total Customers: <?php echo $total_customers; ?></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 5px; background: none;">• Customers with appointments: 
                        <?php 
                        $with_appointments = 0;
                        mysqli_data_seek($result, 0);
                        while ($row = mysqli_fetch_assoc($result)) {
                            if (isset($appointment_stats[$row['customer_id']])) {
                                $with_appointments++;
                            }
                        }
                        echo $with_appointments . ' (' . round(($with_appointments/$total_customers)*100, 1) . '%)';
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="border: none; padding: 5px; background: none;">• Loyal customers (5+ visits): <?php echo $loyal_count; ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Report Footer -->
        <div class="report-footer">
            <div>
                <i class="fas fa-check-circle" style="color: #28a745;"></i> This is a system-generated report
            </div>
            <div>
                Page 1 of 1
            </div>
        </div>
        
        <!-- Signature Lines -->
        <div class="signature-line">
            <div class="signature">
                <div>_________________________</div>
                <div>Prepared by</div>
                <div style="font-weight: 600;"><?php echo $_SESSION['username']; ?></div>
            </div>
            <div class="signature">
                <div>_________________________</div>
                <div>Date</div>
                <div><?php echo $report_date; ?></div>
            </div>
            <div class="signature">
                <div>_________________________</div>
                <div>Authorized Signature</div>
            </div>
        </div>
        
        <div class="signature-line-bottom" style="text-align: center; margin-top: 30px;">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> <?php echo $company_name; ?> - All Rights Reserved
        </div>
    </div>
    
    <script>
        // Auto-trigger print dialog (optional - uncomment if you want auto print)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>