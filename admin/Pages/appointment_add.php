<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if user is logged in
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

// Fetch Customers
$customers = $con->query("SELECT customer_id, first_name, last_name, phone FROM customers ORDER BY first_name ASC");

// Fetch Employees - WITHOUT schedule data to avoid duplicates
$employees = $con->query("
    SELECT DISTINCT e.employee_id, e.first_name, e.last_name, e.position
    FROM employees e
    WHERE e.status = 'active'
    ORDER BY e.first_name ASC
");

// Fetch work schedules separately for availability check
$work_schedules = [];
$schedule_query = $con->query("
    SELECT employee_id, day_of_week, start_time, end_time, is_day_off
    FROM employee_work_schedule
");
while ($schedule = $schedule_query->fetch_assoc()) {
    $work_schedules[$schedule['employee_id']][] = $schedule;
}

// Fetch services for the purpose section
$services = $con->query("SELECT * FROM services WHERE status='active' ORDER BY service_name ASC");
?>

<style>
    /* Add top padding to prevent navbar overlap */
    .page-wrapper {
        padding-top: 70px !important;
    }
    
    .container-fluid {
        padding-top: 20px;
    }
    
    .page-header {
        margin-top: 0;
        padding-bottom: 15px;
        border-bottom: 3px solid #464660;
    }
    
    .form-card {
        max-width: 800px;
        margin: 0 auto;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .panel-heading {
        background: linear-gradient(135deg, #464660 0%, #5a5a7a 100%);
        color: white;
        padding: 20px 25px;
        font-weight: 600;
        font-size: 18px;
    }
    
    .panel-heading i {
        margin-right: 8px;
    }
    
    .panel-body {
        padding: 30px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        font-weight: 600;
        color: #464660;
        margin-bottom: 8px;
        display: block;
    }
    
    .form-group label i {
        margin-right: 8px;
        color: #64648c;
        width: 20px;
    }
    
    .required::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
    
    .form-control, select.form-control {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 10px 15px;
        height: auto;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, select.form-control:focus {
        border-color: #464660;
        box-shadow: 0 0 0 0.2rem rgba(70,70,96,0.25);
        outline: none;
    }
    
    .service-checkbox {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 8px;
        border-left: 3px solid #464660;
        transition: all 0.3s;
    }
    
    .service-checkbox:hover {
        background: #e9ecef;
    }
    
    .service-checkbox input {
        margin-right: 8px;
    }
    
    .service-price {
        float: right;
        color: #28a745;
        font-weight: 600;
    }
    
    .template-buttons {
        margin-top: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .template-btn {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .template-btn:hover {
        background: #464660;
        color: white;
        border-color: #464660;
    }
    
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #17a2b8;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .info-box i {
        color: #17a2b8;
        margin-right: 8px;
    }
    
    .btn-custom {
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        margin-left: 10px;
    }
    
    .btn-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .btn-primary-custom {
        background: #464660;
        color: white;
        border: none;
    }
    
    .btn-primary-custom:hover {
        background: #5a5a7a;
    }
    
    .btn-default-custom {
        background: #6c757d;
        color: white;
        border: none;
    }
    
    .btn-default-custom:hover {
        background: #5a6268;
    }
    
    /* Custom Time Picker Styles */
    .time-picker-container {
        position: relative;
    }
    
    .time-picker-input {
        cursor: pointer;
        background-color: #fff;
    }
    
    .time-picker-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        max-height: 300px;
        overflow-y: auto;
        display: none;
        margin-top: 5px;
    }
    
    .time-picker-dropdown.show {
        display: block;
    }
    
    .time-picker-header {
        padding: 10px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        font-weight: 600;
        color: #464660;
        position: sticky;
        top: 0;
        background: white;
    }
    
    .time-slot-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 5px;
        padding: 10px;
    }
    
    .time-slot-option {
        padding: 8px 12px;
        text-align: center;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 13px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
    }
    
    .time-slot-option:hover {
        background: #464660;
        color: white;
        border-color: #464660;
    }
    
    .time-slot-option.selected {
        background: #28a745;
        color: white;
        border-color: #28a745;
    }
    
    .time-slot-option.booked {
        background: #f8d7da;
        color: #721c24;
        cursor: not-allowed;
        text-decoration: line-through;
        opacity: 0.6;
    }
    
    .time-slot-option.booked:hover {
        background: #f8d7da;
        transform: none;
    }
    
    .time-slot-option.disabled {
        background: #e9ecef;
        color: #adb5bd;
        cursor: not-allowed;
    }
    
    .time-picker-footer {
        padding: 10px 15px;
        border-top: 1px solid #dee2e6;
        background: #f8f9fa;
        font-size: 12px;
        color: #6c757d;
        text-align: center;
    }
    
    .time-picker-footer i {
        margin-right: 5px;
    }
    
    .working-hours-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        margin-left: 8px;
        background: #e7f3ff;
        color: #17a2b8;
    }
    
    .schedule-warning {
        color: #856404;
        background: #fff3cd;
        padding: 8px 12px;
        border-radius: 6px;
        margin-top: 5px;
        display: none;
    }
    
    .schedule-warning i {
        margin-right: 5px;
    }
    
    @media (max-width: 768px) {
        .time-slot-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .form-card {
            margin: 0 15px 30px 15px;
        }
        
        .panel-body {
            padding: 20px;
        }
        
        .btn-custom {
            padding: 8px 20px;
            margin-left: 5px;
        }
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600;color:#464660;">
            <i class="fas fa-calendar-plus"></i> Add New Appointment
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default form-card">
            <div class="panel-heading">
                <i class="fas fa-calendar-check"></i> Appointment Details
            </div>

            <div class="panel-body">
                <div id="alertMessage"></div>
                
                <form id="appointmentForm">
                    <!-- Customer -->
                    <div class="form-group">
                        <label class="required"><i class="fas fa-user"></i> Customer</label>
                        <select name="customer_id" id="customer_id" class="form-control" required>
                            <option value="">-- Select Customer --</option>
                            <?php while($c = $customers->fetch_assoc()): ?>
                                <option value="<?= $c['customer_id']; ?>" data-phone="<?= htmlspecialchars($c['phone']); ?>">
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                    <?php if ($c['phone']): ?> (<?= $c['phone']; ?>)<?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted">Select the customer for this appointment</small>
                    </div>

                    <!-- Employee -->
                    <div class="form-group">
                        <label class="required"><i class="fas fa-user-tie"></i> Employee / Staff</label>
                        <select name="employee_id" id="employee_id" class="form-control" required>
                            <option value="">-- Select Employee --</option>
                            <?php while($e = $employees->fetch_assoc()): 
                                $has_schedule = isset($work_schedules[$e['employee_id']]) && count($work_schedules[$e['employee_id']]) > 0;
                            ?>
                                <option value="<?= $e['employee_id']; ?>" data-has-schedule="<?= $has_schedule ? 'yes' : 'no'; ?>">
                                    <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?>
                                    (<?= htmlspecialchars($e['position']); ?>)
                                    <?php if (!$has_schedule): ?>
                                        - No schedule set
                                    <?php endif; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted">Select the staff member for this appointment</small>
                        <div id="scheduleWarning" class="schedule-warning">
                            <i class="fas fa-exclamation-triangle"></i> This employee doesn't have a work schedule set. Please set their schedule first in the employee management section.
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="form-group">
                        <label class="required"><i class="fas fa-calendar-alt"></i> Appointment Date</label>
                        <input type="date" name="appointment_date" id="appointment_date" class="form-control" 
                               min="<?= date('Y-m-d'); ?>" required>
                        <small class="text-muted">Select the preferred date</small>
                    </div>

                    <!-- Custom Time Picker -->
                    <div class="form-group">
                        <label class="required"><i class="fas fa-clock"></i> Appointment Time</label>
                        <div class="time-picker-container">
                            <input type="text" id="time_picker_input" class="form-control time-picker-input" 
                                   placeholder="Select time" readonly required>
                            <input type="hidden" name="appointment_time" id="appointment_time" required>
                            <div id="timePickerDropdown" class="time-picker-dropdown">
                                <div class="time-picker-header">
                                    <i class="fas fa-clock"></i> Available Time Slots
                                    <span id="workingHoursBadge" class="working-hours-badge"></span>
                                </div>
                                <div id="timeSlotGrid" class="time-slot-grid">
                                    <div class="text-center" style="grid-column: 1/-1; padding: 20px;">
                                        <i class="fas fa-spinner fa-spin"></i> Loading available slots...
                                    </div>
                                </div>
                                <div class="time-picker-footer">
                                    <i class="fas fa-info-circle"></i> Click on a time slot to select
                                </div>
                            </div>
                        </div>
                        <small class="text-muted">Click to select an available time slot</small>
                    </div>

                    <!-- Purpose / Services -->
                    <div class="form-group">
                        <label class="required"><i class="fas fa-info-circle"></i> Purpose / Services</label>
                        
                        <!-- Service Selection -->
                        <div class="info-box">
                            <i class="fas fa-tags"></i> Select services to include in the appointment:
                        </div>
                        <div class="row" id="servicesList">
                            <?php while($service = $services->fetch_assoc()): ?>
                                <div class="col-md-6">
                                    <div class="service-checkbox">
                                        <label>
                                            <input type="checkbox" class="service-check" value="<?= $service['service_id']; ?>" 
                                                   data-name="<?= htmlspecialchars($service['service_name']); ?>"
                                                   data-price="<?= $service['price']; ?>">
                                            <?= htmlspecialchars($service['service_name']); ?>
                                            <span class="service-price">₱<?= number_format($service['price'], 2); ?></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Special Instructions -->
                        <div class="form-group" style="margin-top: 15px;">
                            <label><i class="fas fa-pen"></i> Special Instructions / Notes</label>
                            <textarea name="purpose" id="purpose" class="form-control" rows="4" 
                                      placeholder="Describe the purpose of the appointment or any special instructions...
Examples:
- Client wants quiet environment
- Allergic to certain products
- Bring hair color samples
- Need to finish by specific time
- Preferred therapist: Maria"></textarea>
                        </div>
                        
                        <!-- Quick Templates -->
                        <div class="form-group">
                            <label><i class="fas fa-magic"></i> Quick Templates</label>
                            <div class="template-buttons">
                                <button type="button" class="template-btn" data-template="Regular haircut and styling">
                                    ✂️ Haircut
                                </button>
                                <button type="button" class="template-btn" data-template="Full body massage with lavender oil">
                                    💆 Massage
                                </button>
                                <button type="button" class="template-btn" data-template="Facial treatment with sensitive skin products">
                                    ✨ Facial
                                </button>
                                <button type="button" class="template-btn" data-template="Manicure and pedicure with gel polish">
                                    💅 Nail Care
                                </button>
                                <button type="button" class="template-btn" data-template="Hair coloring - medium brown, no bleach">
                                    🎨 Hair Color
                                </button>
                                <button type="button" class="template-btn" data-template="Hair treatment - keratin smoothing">
                                    🌟 Hair Treatment
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Status (hidden for add, default pending) -->
                    <input type="hidden" name="status" value="pending">

                    <div class="form-group text-right">
                        <a href="appointments.php" class="btn btn-default-custom btn-custom">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit" id="submitBtn" class="btn btn-primary-custom btn-custom">
                            <i class="fas fa-save"></i> Save Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../js/jquery.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Show warning if employee has no schedule
    $('#employee_id').change(function() {
        var hasSchedule = $(this).find(':selected').data('has-schedule');
        if (hasSchedule === 'no') {
            $('#scheduleWarning').show();
        } else {
            $('#scheduleWarning').hide();
        }
        // Reset time picker when employee changes
        $('#time_picker_input').val('');
        $('#appointment_time').val('');
        $('#timeSlotGrid').html('<div class="text-center" style="grid-column: 1/-1; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading available slots...</div>');
    });
    
    // Toggle time picker dropdown
    $('#time_picker_input').click(function(e) {
        e.stopPropagation();
        var date = $('#appointment_date').val();
        var employee_id = $('#employee_id').val();
        
        if (!date) {
            Swal.fire('Error', 'Please select a date first', 'error');
            return;
        }
        
        if (!employee_id) {
            Swal.fire('Error', 'Please select an employee first', 'error');
            return;
        }
        
        // Check if employee has schedule
        var hasSchedule = $('#employee_id').find(':selected').data('has-schedule');
        if (hasSchedule === 'no') {
            Swal.fire('Error', 'This employee has no work schedule set. Please set their schedule first.', 'error');
            return;
        }
        
        $('#timePickerDropdown').toggleClass('show');
        loadTimeSlots(date, employee_id);
    });
    
    // Close dropdown when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.time-picker-container').length) {
            $('#timePickerDropdown').removeClass('show');
        }
    });
    
    // Load time slots function
    function loadTimeSlots(date, employee_id) {
        $('#timeSlotGrid').html('<div class="text-center" style="grid-column: 1/-1; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Checking availability...</div>');
        
        $.ajax({
            url: '../Functions/get_time_slots.php',
            type: 'POST',
            data: {date: date, employee_id: employee_id},
            dataType: 'json',
            success: function(res) {
                if (res.error) {
                    $('#timeSlotGrid').html('<div class="text-center text-danger" style="grid-column: 1/-1; padding: 20px;">' + res.error + '</div>');
                    return;
                }
                
                // Update working hours badge
                $('#workingHoursBadge').text(res.start_time + ' - ' + res.end_time);
                
                var slotsHtml = '';
                if (res.slots && res.slots.length > 0) {
                    res.slots.forEach(function(slot) {
                        var isBooked = false;
                        if (res.booked_times && res.booked_times.includes(convertTo24Hour(slot))) {
                            isBooked = true;
                        }
                        
                        var additionalClass = '';
                        var onclickAttr = '';
                        
                        if (isBooked) {
                            additionalClass = 'booked';
                            onclickAttr = 'onclick="event.stopPropagation(); alert(\'This time slot is already booked.\')"';
                        } else {
                            onclickAttr = 'onclick="selectTimeSlot(\'' + slot + '\')"';
                        }
                        
                        slotsHtml += '<div class="time-slot-option ' + additionalClass + '" data-time="' + slot + '" ' + onclickAttr + '>' + slot + '</div>';
                    });
                    $('#timeSlotGrid').html(slotsHtml);
                } else {
                    $('#timeSlotGrid').html('<div class="text-center text-warning" style="grid-column: 1/-1; padding: 20px;">No available time slots for this date. Please try another date.</div>');
                }
            },
            error: function() {
                $('#timeSlotGrid').html('<div class="text-center text-danger" style="grid-column: 1/-1; padding: 20px;">Error loading time slots. Please try again.</div>');
            }
        });
    }
    
    // Select time slot function
    window.selectTimeSlot = function(timeSlot) {
        $('#time_picker_input').val(timeSlot);
        var time24 = convertTo24Hour(timeSlot);
        $('#appointment_time').val(time24);
        $('#timePickerDropdown').removeClass('show');
        
        // Highlight selected slot
        $('.time-slot-option').removeClass('selected');
        $('.time-slot-option[data-time="' + timeSlot + '"]').addClass('selected');
    };
    
    // Helper function to convert 12-hour format to 24-hour format
    function convertTo24Hour(time12h) {
        if (!time12h) return '';
        var time = time12h.match(/(\d+):(\d+)\s?(AM|PM)/i);
        if (!time) return time12h;
        
        var hours = parseInt(time[1]);
        var minutes = parseInt(time[2]);
        var period = time[3].toUpperCase();
        
        if (period === 'PM' && hours !== 12) {
            hours += 12;
        } else if (period === 'AM' && hours === 12) {
            hours = 0;
        }
        
        return hours.toString().padStart(2, '0') + ':' + minutes.toString().padStart(2, '0');
    }
    
    // Helper function to convert 24-hour format to 12-hour format
    function convertTo12Hour(time24) {
        if (!time24) return '';
        var parts = time24.split(':');
        var hours = parseInt(parts[0]);
        var minutes = parts[1];
        var period = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return hours + ':' + minutes + ' ' + period;
    }
    
    // Service selection to build purpose
    $('.service-check').change(function() {
        updatePurposeFromServices();
    });
    
    function updatePurposeFromServices() {
        var services = [];
        $('.service-check:checked').each(function() {
            services.push($(this).data('name'));
        });
        
        var currentPurpose = $('#purpose').val();
        // Remove existing service list
        var lines = currentPurpose.split('\n');
        var filteredLines = lines.filter(function(line) {
            return !line.startsWith('✓ ');
        });
        
        var newPurpose = filteredLines.join('\n').trim();
        
        if (services.length > 0) {
            newPurpose = (newPurpose ? newPurpose + '\n\n' : '') + 'Services Requested:\n' + services.map(function(s) { return '✓ ' + s; }).join('\n');
        }
        
        $('#purpose').val(newPurpose);
    }
    
    // Template buttons
    $('.template-btn').click(function() {
        var template = $(this).data('template');
        var currentText = $('#purpose').val();
        if (currentText) {
            $('#purpose').val(currentText + '\n\n' + template);
        } else {
            $('#purpose').val(template);
        }
    });
    
    // Set min date to today
    var today = new Date().toISOString().split('T')[0];
    $('#appointment_date').attr('min', today);
    
    // Refresh time slots when date changes
    $('#appointment_date').change(function() {
        var date = $(this).val();
        var employee_id = $('#employee_id').val();
        if (date && employee_id) {
            loadTimeSlots(date, employee_id);
        }
        $('#time_picker_input').val('');
        $('#appointment_time').val('');
    });
    
    // Form submission with AJAX - OPTIMIZED
    $('#appointmentForm').submit(function(e) {
        e.preventDefault();
        
        // Quick validation first (no AJAX)
        var customer_id = $('#customer_id').val();
        var employee_id = $('#employee_id').val();
        var date = $('#appointment_date').val();
        var time = $('#appointment_time').val();
        var purpose = $('#purpose').val();
        
        if (!customer_id) {
            Swal.fire('Error', 'Please select a customer', 'error');
            return false;
        }
        
        if (!employee_id) {
            Swal.fire('Error', 'Please select an employee', 'error');
            return false;
        }
        
        if (!date) {
            Swal.fire('Error', 'Please select a date', 'error');
            return false;
        }
        
        if (!time) {
            Swal.fire('Error', 'Please select a time', 'error');
            return false;
        }
        
        if (!purpose) {
            Swal.fire('Error', 'Please enter the purpose of the appointment', 'error');
            return false;
        }
        
        // OPTIMIZED: Disable button immediately and show loading
        var submitBtn = $('#submitBtn');
        var originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
        
        // OPTIMIZED: Use a simple POST without extra data processing
        $.ajax({
            url: '../Functions/appointment_add_ajax.php',
            type: 'POST',
            data: $(this).serialize(), // This is efficient
            dataType: 'json',
            timeout: 10000, // 10 second timeout
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'appointments.php';
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                var errorMsg = 'Failed to save appointment. ';
                if (status === 'timeout') {
                    errorMsg += 'Request timed out. Please try again.';
                } else {
                    errorMsg += 'Please try again.';
                }
                Swal.fire('Error', errorMsg, 'error');
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>