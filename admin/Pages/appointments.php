<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Get statistics
$total_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments");
$total_appointments = mysqli_fetch_assoc($total_appointments)['total'];

$today_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE appointment_date = CURDATE()");
$today_appointments = mysqli_fetch_assoc($today_appointments)['total'];

$pending_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'");
$pending_appointments = mysqli_fetch_assoc($pending_appointments)['total'];

$completed_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE status = 'completed'");
$completed_appointments = mysqli_fetch_assoc($completed_appointments)['total'];

$upcoming_appointments = mysqli_query($con, "SELECT COUNT(*) as total FROM appointments WHERE appointment_date >= CURDATE() AND status != 'cancelled' AND status != 'completed'");
$upcoming_appointments = mysqli_fetch_assoc($upcoming_appointments)['total'];

// Fetch appointments with related data
$sql = "SELECT a.*, 
               CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
               c.phone AS customer_phone,
               CONCAT(e.first_name, ' ', e.last_name) AS employee_name
        FROM appointments a
        LEFT JOIN customers c ON a.customer_id = c.customer_id
        LEFT JOIN employees e ON a.employee_id = e.employee_id
        ORDER BY a.appointment_date DESC, a.appointment_time ASC";
$result = $con->query($sql);
?>

<style>
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .summary-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border-left: 4px solid #464660;
        position: relative;
        overflow: hidden;
    }
    
    .summary-card .card-value {
        font-size: 28px;
        font-weight: 700;
        color: #464660;
    }
    
    .summary-card .card-label {
        font-size: 13px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .status-badge {
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
    
    .status-pending { background: #fff3cd; color: #856404; }
    .status-approved { background: #cce5ff; color: #004085; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    
    .filter-select {
        padding: 5px 10px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 13px;
        margin-right: 10px;
    }
    
    .action-btn {
        margin: 0 2px;
    }
    
    .appointment-row {
        transition: background 0.3s;
    }
    
    .appointment-row:hover {
        background: #f8f9fa;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600; color:#464660;">
            <i class="fas fa-calendar-alt"></i> Appointments
        </h1>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-cards">
    <div class="summary-card">
        <div class="card-value"><?= $total_appointments; ?></div>
        <div class="card-label">Total Appointments</div>
    </div>
    <div class="summary-card" style="border-left-color: #28a745;">
        <div class="card-value"><?= $today_appointments; ?></div>
        <div class="card-label">Today</div>
    </div>
    <div class="summary-card" style="border-left-color: #ffc107;">
        <div class="card-value"><?= $pending_appointments; ?></div>
        <div class="card-label">Pending</div>
    </div>
    <div class="summary-card" style="border-left-color: #17a2b8;">
        <div class="card-value"><?= $upcoming_appointments; ?></div>
        <div class="card-label">Upcoming</div>
    </div>
    <div class="summary-card" style="border-left-color: #6c757d;">
        <div class="card-value"><?= $completed_appointments; ?></div>
        <div class="card-label">Completed</div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-list"></i> Appointment Schedule</strong>
                    </div>
                    <div class="col-md-6 text-right">
                        <select class="filter-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        
                        <select class="filter-select" id="dateFilter">
                            <option value="">All Dates</option>
                            <option value="today">Today</option>
                            <option value="tomorrow">Tomorrow</option>
                            <option value="week">This Week</option>
                        </select>
                        
                        <a href="appointment_add.php" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> New Appointment
                        </a>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table id="appointmentsTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Customer</th>
                                <th>Employee</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th style="width: 120px;">Actions</th>
                            </thead>
                        <tbody id="appointmentsTableBody">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr class="appointment-row" data-status="<?= $row['status']; ?>" data-date="<?= $row['appointment_date']; ?>" data-id="<?= $row['appointment_id']; ?>">
                                    <td>
                                        <div><i class="fas fa-calendar-alt"></i> <?= date('M d, Y', strtotime($row['appointment_date'])); ?></div>
                                        <div class="text-muted"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($row['appointment_time'])); ?></div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['customer_name']); ?></strong>
                                        <?php if (!empty($row['customer_phone'])): ?>
                                            <br><small><i class="fas fa-phone"></i> <?= htmlspecialchars($row['customer_phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['employee_name']): ?>
                                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($row['employee_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['purpose'] ?: '—'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <?= ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm action-btn editAppointmentBtn" 
                                                data-id="<?= $row['appointment_id']; ?>" 
                                                data-toggle="tooltip" 
                                                title="Edit Appointment">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <button class="btn btn-danger btn-sm action-btn cancelAppointmentBtn" 
                                                data-id="<?= $row['appointment_id']; ?>"
                                                data-customer="<?= htmlspecialchars($row['customer_name']); ?>"
                                                data-date="<?= $row['appointment_date']; ?>"
                                                data-toggle="tooltip" 
                                                title="Cancel Appointment">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                        
                                        <a href="appointment-view.php?id=<?= $row['appointment_id']; ?>" 
                                           class="btn btn-info btn-sm action-btn" 
                                           data-toggle="tooltip" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="fas fa-calendar-times"></i> No appointments found.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Appointment Modal -->
<div class="modal fade" id="editAppointmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: #464660; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Appointment
                </h4>
            </div>
            <form id="editAppointmentForm">
                <div class="modal-body" id="editAppointmentContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i> Loading...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/dataTables/jquery.dataTables.min.js"></script>
<script src="../js/dataTables/dataTables.bootstrap.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#appointmentsTable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'desc']],
        language: {
            emptyTable: "No appointments found"
        }
    });

    // Status Filter
    $('#statusFilter').on('change', function() {
        var status = $(this).val();
        if (status === '') {
            table.column(4).search('').draw();
        } else {
            table.column(4).search('^' + status + '$', true, false).draw();
        }
    });

    // Date Filter
    $('#dateFilter').on('change', function() {
        var filter = $(this).val();
        var today = new Date().toISOString().split('T')[0];
        var tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];
        
        if (filter === '') {
            table.column(0).search('').draw();
        } else if (filter === 'today') {
            table.column(0).search(today, true, false).draw();
        } else if (filter === 'tomorrow') {
            table.column(0).search(tomorrow, true, false).draw();
        } else if (filter === 'week') {
            // Custom filtering for this week
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var date = new Date(data[0].split('<')[0]);
                    var now = new Date();
                    var weekStart = new Date(now.setDate(now.getDate() - now.getDay()));
                    var weekEnd = new Date(now.setDate(now.getDate() - now.getDay() + 6));
                    return date >= weekStart && date <= weekEnd;
                }
            );
            table.draw();
            $.fn.dataTable.ext.search.pop();
        }
    });

    // Edit Appointment - Load Modal
    $(document).on('click', '.editAppointmentBtn', function() {
        var id = $(this).data('id');
        $('#editAppointmentContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i> Loading...</div>');
        $('#editAppointmentModal').modal('show');
        
        $.ajax({
            url: 'appointment_edit_modal.php',
            type: 'GET',
            data: {id: id},
            success: function(response) {
                $('#editAppointmentContent').html(response);
            },
            error: function() {
                $('#editAppointmentContent').html('<p class="text-danger">Failed to load appointment data.</p>');
            }
        });
    });

    $(document).on('submit', '#editAppointmentForm', function(e) {
        e.preventDefault();

        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

        $.ajax({
            url: '../Functions/appointment_update_ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',

            success: function(res) {

                if (res.status === 'success') {

                    $('#editAppointmentModal').modal('hide');

                    // 🔥 UPDATE TABLE ROW
                    var row = $("tr[data-id='" + res.data.appointment_id + "']");

                    row.find("td:eq(0)").html(
                        '<div><i class="fas fa-calendar-alt"></i> ' + res.data.date + '</div>' +
                        '<div class="text-muted"><i class="fas fa-clock"></i> ' + res.data.time + '</div>'
                    );

                    row.find("td:eq(3)").text(res.data.purpose);
                    row.find("td:eq(4)").html(res.data.status_badge);

                    // 🔥 HIGHLIGHT EFFECT
                    row.css("background-color", "#d4edda");
                    setTimeout(function() {
                        row.css("background-color", "");
                    }, 1500);

                    // 🔥 SUCCESS ALERT
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        timer: 1200,
                        showConfirmButton: false
                    });

                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },

            error: function(xhr) {
                console.log(xhr.responseText);
                Swal.fire('Error', 'Something went wrong', 'error');
            },

            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Cancel Appointment
    $(document).on('click', '.cancelAppointmentBtn', function() {
        var appointment_id = $(this).data('id');
        var customer_name = $(this).data('customer');
        var date = $(this).data('date');
        
        Swal.fire({
            title: 'Cancel Appointment?',
            text: `Are you sure you want to cancel the appointment for ${customer_name} on ${date}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../Functions/appointment_cancel_ajax.php',
                    type: 'POST',
                    data: {id: appointment_id},
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Cancelled!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to cancel appointment', 'error');
                    }
                });
            }
        });
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>