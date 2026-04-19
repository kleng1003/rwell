<?php
// Start session
session_start();

// Use absolute path based on document root
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/RWELL/';
include_once($root_path . 'admin/include/connection.php');

// Initialize variables with default values
$is_logged_in = isset($_SESSION['client_id']);
$client_info = null;
$first_name = '';
$last_name = '';
$client_phone = '';
$client_email = '';
$display_name = '';

// Check if user is logged in
if ($is_logged_in) {
    $client_id = $_SESSION['client_id'];
    
    // Check which columns exist in the table
    $columns_result = mysqli_query($con, "SHOW COLUMNS FROM tbl_client_accounts");
    $has_first_name = false;
    $has_last_name = false;
    $has_fullname = false;
    
    while ($col = mysqli_fetch_assoc($columns_result)) {
        if ($col['Field'] == 'first_name') $has_first_name = true;
        if ($col['Field'] == 'last_name') $has_last_name = true;
        if ($col['Field'] == 'fullname') $has_fullname = true;
    }
    
    // Build query based on available columns
    $select_fields = "client_id, username, email, contact_no";
    if ($has_first_name) $select_fields .= ", first_name";
    if ($has_last_name) $select_fields .= ", last_name";
    if ($has_fullname) $select_fields .= ", fullname";
    
    $client_query = "SELECT $select_fields FROM tbl_client_accounts WHERE client_id = ?";
    $stmt = $con->prepare($client_query);
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $client_result = $stmt->get_result();
    $client_info = $client_result->fetch_assoc();
    $stmt->close();
    
    // Get name from available fields
    if ($client_info) {
        if ($has_first_name && $has_last_name) {
            $first_name = $client_info['first_name'] ?? '';
            $last_name = $client_info['last_name'] ?? '';
            $display_name = trim($first_name . ' ' . $last_name);
        } elseif ($has_fullname) {
            $display_name = $client_info['fullname'] ?? '';
            $name_parts = explode(' ', trim($display_name), 2);
            $first_name = $name_parts[0] ?? '';
            $last_name = $name_parts[1] ?? '';
        } else {
            $display_name = $client_info['username'] ?? 'Client';
            $first_name = $display_name;
            $last_name = '';
        }
        
        $client_phone = $client_info['contact_no'] ?? '';
        $client_email = $client_info['email'] ?? '';
    }
}

// Get pre-selected service from URL
$preselected_service = isset($_GET['service']) ? (int)$_GET['service'] : null;

// Fetch employees for dropdown
$employees_query = mysqli_query($con, "
    SELECT e.employee_id, e.first_name, e.last_name, e.position
    FROM employees e
    WHERE e.status = 'active'
    ORDER BY e.first_name ASC
");

// Fetch services for dropdown (multiple selection)
$services_query = mysqli_query($con, "
    SELECT service_id, service_name, description, price, duration
    FROM services
    WHERE status = 'active'
    ORDER BY service_name ASC
");

$services = [];
while ($service = mysqli_fetch_assoc($services_query)) {
    $services[] = $service;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Book Appointment | RWell Salon & Spa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  <style>
    body {
      background: linear-gradient(135deg, #fff5f0 0%, #ffe8e0 100%);
      font-family: 'Segoe UI', sans-serif;
    }
    
    .navbar {
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .page-container {
      max-width: 1200px;
      margin: 30px auto;
      padding: 0 20px;
    }
    
    .calendar-card {
      background: white;
      padding: 25px;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }
    
    #calendar {
      max-width: 100%;
      margin: 0 auto;
    }
    
    .fc-toolbar-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #464660;
    }
    
    .fc-button-primary {
      background-color: #e91e63 !important;
      border-color: #e91e63 !important;
    }
    
    .fc-day-today {
      background-color: #fff5f7 !important;
    }
    
    .fc-day-past {
      opacity: 0.6;
      cursor: not-allowed !important;
    }
    
    .fc-day-past .fc-daygrid-day-number {
      color: #999;
    }
    
    .modal-header {
      background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%);
      color: white;
      border: none;
    }
    
    .modal-header .btn-close {
      filter: brightness(0) invert(1);
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      font-weight: 600;
      color: #464660;
      margin-bottom: 8px;
      display: block;
    }
    
    .form-group label i {
      margin-right: 8px;
      color: #e91e63;
    }
    
    .form-control, .form-select {
      border: 2px solid #e9ecef;
      border-radius: 10px;
      padding: 12px 15px;
      transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #e91e63;
      box-shadow: 0 0 0 0.2rem rgba(233,30,99,0.25);
      outline: none;
    }
    
    .form-control[readonly] {
      background-color: #f8f9fa;
      cursor: default;
    }
    
    .required::after {
      content: " *";
      color: #dc3545;
    }
    
    .time-slots {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
      max-height: 250px;
      overflow-y: auto;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 10px;
    }
    
    .time-slot {
      background: white;
      border: 2px solid #dee2e6;
      padding: 10px 20px;
      border-radius: 25px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .time-slot:hover {
      border-color: #e91e63;
      background: #fff5f7;
    }
    
    .time-slot.selected {
      background: #e91e63;
      color: white;
      border-color: #e91e63;
    }
    
    .time-slot.booked {
      background: #f8d7da;
      color: #721c24;
      cursor: not-allowed;
      border-color: #f5c6cb;
      text-decoration: line-through;
    }
    
    .btn-submit {
      background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%);
      color: white;
      border: none;
      padding: 12px 30px;
      border-radius: 50px;
      font-weight: 600;
      width: 100%;
      transition: all 0.3s;
    }
    
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(233,30,99,0.3);
      color: white;
    }
    
    .btn-secondary-custom {
      background: #6c757d;
      color: white;
      padding: 8px 20px;
      border-radius: 25px;
      text-decoration: none;
      transition: all 0.3s;
    }
    
    .btn-secondary-custom:hover {
      background: #5a6268;
      color: white;
    }
    
    .working-hours-info {
      background: #e7f3ff;
      padding: 12px 15px;
      border-radius: 10px;
      margin-bottom: 15px;
      font-size: 14px;
    }
    
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 2px solid #f3f3f3;
      border-top: 2px solid #e91e63;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .selected-services-summary {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 15px;
      margin: 15px 0;
    }
    
    .service-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #e0e0e0;
    }
    
    .service-item:last-child {
      border-bottom: none;
    }
    
    .service-total {
      font-weight: 700;
      color: #e91e63;
      font-size: 1.1rem;
      margin-top: 10px;
      padding-top: 10px;
      border-top: 2px solid #e0e0e0;
    }
    
    .login-prompt {
      background: #fff3cd;
      border: 1px solid #ffecb5;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    .logged-in-badge {
      background: #d4edda;
      color: #155724;
      padding: 8px 15px;
      border-radius: 25px;
      display: inline-block;
      margin-bottom: 15px;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple {
      border: 2px solid #e9ecef !important;
      border-radius: 10px !important;
      padding: 8px 12px !important;
      min-height: 50px;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple:focus {
      border-color: #e91e63 !important;
      box-shadow: 0 0 0 0.2rem rgba(233,30,99,0.25) !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection__choice {
      background: #e91e63 !important;
      color: white !important;
      border: none !important;
      border-radius: 20px !important;
      padding: 5px 12px !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection__choice__remove {
      color: white !important;
      margin-right: 5px !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection__choice__remove:hover {
      color: #ffd1dc !important;
    }
    
    @media (max-width: 768px) {
      .page-container {
        margin: 20px;
        padding: 0;
      }
      
      .calendar-card {
        padding: 15px;
      }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-light bg-light shadow-sm">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="bi bi-scissors"></i> RWell Salon & Spa
    </a>
    <div>
      <?php if ($is_logged_in): ?>
        <span class="logged-in-badge me-3">
          <i class="bi bi-person-check"></i> Welcome, <?php echo htmlspecialchars($display_name ?: 'Client'); ?>
        </span>
      <?php endif; ?>
      <a href="../../index.php" class="btn-secondary-custom">
        <i class="bi bi-arrow-left"></i> Back to Home
      </a>
    </div>
  </div>
</nav>

<div class="page-container">
  <div class="calendar-card">
    <div class="text-center mb-4">
      <h3><i class="bi bi-calendar-heart me-2" style="color: #e91e63;"></i>Select Appointment Date</h3>
      <p class="text-muted">Click on an available date to book your appointment</p>
    </div>
    <div id="calendar"></div>
  </div>
</div>

<!-- Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <form id="appointmentForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-check"></i> Book an Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="appointmentDate" name="appointment_date" />
        
        <?php if (!$is_logged_in): ?>
        <div class="login-prompt">
          <i class="bi bi-info-circle-fill me-2"></i>
          <strong>Not logged in?</strong> Your information will be saved as a guest. 
          <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a> for faster booking!
        </div>
        <?php endif; ?>
        
        <!-- Customer Name -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="required"><i class="bi bi-person"></i> First Name</label>
              <input type="text" class="form-control" id="firstName" name="first_name" 
                     value="<?php echo htmlspecialchars($first_name); ?>" 
                     <?php echo $is_logged_in ? 'readonly' : 'required'; ?>>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="bi bi-person"></i> Last Name (Optional)</label>
              <input type="text" class="form-control" id="lastName" name="last_name" 
                     value="<?php echo htmlspecialchars($last_name); ?>" 
                     <?php echo $is_logged_in ? 'readonly' : ''; ?>>
            </div>
          </div>
        </div>
        
        <!-- Contact Info -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="required"><i class="bi bi-telephone"></i> Phone Number</label>
              <input type="tel" class="form-control" id="customerPhone" name="customer_phone" 
                     value="<?php echo htmlspecialchars($client_phone); ?>" 
                     <?php echo $is_logged_in ? 'readonly' : 'required'; ?>>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="bi bi-envelope"></i> Email</label>
              <input type="email" class="form-control" id="customerEmail" name="customer_email" 
                     value="<?php echo htmlspecialchars($client_email); ?>" 
                     <?php echo $is_logged_in ? 'readonly' : ''; ?>>
            </div>
          </div>
        </div>
        
        <!-- Address -->
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label><i class="bi bi-geo-alt"></i> Address</label>
              <input type="text" class="form-control" id="customerAddress" name="customer_address" 
                     placeholder="Enter your address">
            </div>
          </div>
        </div>
        
        <!-- Staff and Services -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="bi bi-person-badge"></i> Preferred Staff (Optional)</label>
              <select class="form-select" id="employeeId" name="employee_id">
                <option value="">-- No Preference --</option>
                <?php 
                mysqli_data_seek($employees_query, 0);
                while($employee = mysqli_fetch_assoc($employees_query)): 
                ?>
                  <option value="<?= $employee['employee_id']; ?>">
                    <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="required"><i class="bi bi-tags"></i> Select Services</label>
              <select class="form-select" id="serviceIds" name="service_ids[]" multiple required style="width: 100%;">
                <?php foreach ($services as $service): ?>
                  <option value="<?= $service['service_id']; ?>" 
                          data-price="<?= $service['price']; ?>"
                          data-duration="<?= $service['duration']; ?>"
                          <?= ($preselected_service == $service['service_id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($service['service_name']); ?> - ₱<?= number_format($service['price'], 2); ?> (<?= $service['duration']; ?> min)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        
        <!-- Selected Services Summary -->
        <div id="selectedServicesSummary" class="selected-services-summary" style="display: none;">
          <h6 class="mb-3"><i class="bi bi-bag-check"></i> Selected Services</h6>
          <div id="serviceList"></div>
          <div class="service-total">
            <span>Total Amount:</span>
            <span id="totalAmount">₱0.00</span>
          </div>
          <!-- <div class="mt-2 text-muted small">
            <i class="bi bi-clock"></i> Total Duration: <span id="totalDuration">0</span> minutes
          </div> -->
        </div>
        
        <!-- Time Slots -->
        <div class="form-group mt-3">
          <label class="required"><i class="bi bi-clock"></i> Preferred Time</label>
          <input type="hidden" id="appointmentTime" name="appointment_time">
          <div id="timeSlots" class="time-slots">
            <div class="text-muted">Select services to view available time slots</div>
          </div>
        </div>
        
        <!-- Notes -->
        <div class="form-group">
          <label><i class="bi bi-chat"></i> Special Requests / Notes</label>
          <textarea class="form-control" id="purpose" name="purpose" rows="2" placeholder="Any special requests or notes..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn-submit"><i class="bi bi-calendar-check"></i> Book Now</button>
      </div>
    </form>
  </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center border-0 shadow-lg" style="border-radius: 20px;">
      <div class="modal-body p-5">
        <div class="mb-4">
          <i class="bi bi-check-circle-fill" style="font-size: 70px; color: #28a745;"></i>
        </div>
        <h3 class="mb-3">Booking Confirmed!</h3>
        <p class="text-muted mb-4">Your appointment has been successfully booked. We look forward to serving you!</p>
        <div class="d-grid gap-2">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal" style="background: #e91e63; border: none; padding: 12px;">
            <i class="bi bi-check-lg me-2"></i>Done
          </button>
          <a href="../../index.php?page=my-reservations" class="btn btn-outline-secondary">
            <i class="bi bi-calendar-check me-2"></i>View My Appointments
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  let selectedDate = '';
  
  // Initialize Select2 for multiple service selection
  $('#serviceIds').select2({
    theme: 'bootstrap-5',
    placeholder: 'Select one or more services',
    allowClear: true,
    closeOnSelect: false,
    dropdownParent: $('#appointmentModal')
  });
  
  // Update services summary when selection changes
  $('#serviceIds').on('change', function() {
    updateServicesSummary();
    if (selectedDate) {
      loadTimeSlots(selectedDate, document.getElementById('employeeId').value);
    }
  });
  
  // Calculate and display selected services summary
  function updateServicesSummary() {
    const selectedOptions = $('#serviceIds option:selected');
    const summaryDiv = document.getElementById('selectedServicesSummary');
    const serviceListDiv = document.getElementById('serviceList');
    const totalAmountSpan = document.getElementById('totalAmount');
    const totalDurationSpan = document.getElementById('totalDuration');
    
    if (!summaryDiv || !serviceListDiv || !totalAmountSpan || !totalDurationSpan) {
      console.warn('Summary elements not found');
      return;
    }
    
    if (selectedOptions.length === 0) {
      summaryDiv.style.display = 'none';
      return;
    }
    
    summaryDiv.style.display = 'block';
    let html = '';
    let totalAmount = 0;
    let totalDuration = 0;
    
    selectedOptions.each(function() {
      const text = $(this).text();
      const serviceName = text.split(' - ')[0];
      const price = parseFloat($(this).data('price')) || 0;
      const duration = parseInt($(this).data('duration')) || 0;
      
      totalAmount += price;
      totalDuration += duration;
      
      html += `
        <div class="service-item">
          <span><i class="bi bi-check-circle-fill text-success me-2"></i>${serviceName}</span>
          <span>₱${price.toFixed(2)}</span>
        </div>
      `;
    });
    
    serviceListDiv.innerHTML = html;
    totalAmountSpan.textContent = '₱' + totalAmount.toFixed(2);
    totalDurationSpan.textContent = totalDuration;
  }
  
  function calculateTotalDuration() {
    let totalDuration = 0;
    $('#serviceIds option:selected').each(function() {
      totalDuration += parseInt($(this).data('duration')) || 0;
    });
    return totalDuration;
  }
  
  // Initialize calendar with tomorrow as minimum date
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const tomorrowStr = tomorrow.toISOString().split('T')[0];
  
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    validRange: { 
      start: tomorrowStr
    },
    selectable: true,
    selectAllow: function(selectInfo) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      return selectInfo.start >= today;
    },
    headerToolbar: { 
      left: 'prev,next today', 
      center: 'title', 
      right: '' 
    },
    events: function(info, successCallback) {
      successCallback([]);
    },
    eventColor: '#e91e63',
    dateClick: function(info) {
      const clickedDate = new Date(info.dateStr);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      if (clickedDate < today) {
        alert('Cannot book appointments for past dates. Please select a future date.');
        return;
      }
      
      selectedDate = info.dateStr;
      document.getElementById('appointmentDate').value = selectedDate;
      
      const timeSlotsDiv = document.getElementById('timeSlots');
      if (timeSlotsDiv) {
        timeSlotsDiv.innerHTML = '<div class="text-muted">Select services to view available time slots</div>';
      }
      document.getElementById('appointmentTime').value = '';
      
      new bootstrap.Modal(document.getElementById('appointmentModal')).show();
    },
    dayCellDidMount: function(info) {
      const cellDate = new Date(info.date);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      if (cellDate < today) {
        info.el.style.opacity = '0.5';
        info.el.style.cursor = 'not-allowed';
      }
    }
  });
  
  calendar.render();
  
  function loadTimeSlots(date, employeeId) {
    const timeSlotsDiv = document.getElementById('timeSlots');
    if (!timeSlotsDiv) return;
    
    const selectedServices = $('#serviceIds').val();
    
    if (!selectedServices || selectedServices.length === 0) {
      timeSlotsDiv.innerHTML = '<div class="text-warning"><i class="bi bi-exclamation-triangle"></i> Please select at least one service to view available time slots.</div>';
      return;
    }
    
    const totalDuration = calculateTotalDuration();
    
    timeSlotsDiv.innerHTML = '<div class="text-center"><span class="loading-spinner"></span> Loading available slots...</div>';
    
    const formData = new FormData();
    formData.append('date', date);
    formData.append('employee_id', employeeId);
    formData.append('duration', totalDuration);
    formData.append('services', JSON.stringify(selectedServices));
    
    fetch('get_time_slots.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        timeSlotsDiv.innerHTML = '<div class="text-danger"><i class="bi bi-exclamation-circle"></i> ' + data.error + '</div>';
        return;
      }
      
      if (data.slots && data.slots.length > 0) {
        let html = `<div class="working-hours-info">
          <i class="bi bi-clock-history"></i> Working hours: ${data.start_time} - ${data.end_time}
        </div>`;
        
        data.slots.forEach(slot => {
          const isBooked = data.booked_times && data.booked_times.includes(slot);
          html += `<div class="time-slot ${isBooked ? 'booked' : ''}" data-time="${slot}" onclick="${isBooked ? '' : 'selectTimeSlot(this, \'' + slot + '\')'}">
            ${slot} ${isBooked ? '(Booked)' : ''}
          </div>`;
        });
        timeSlotsDiv.innerHTML = html;
      } else {
        timeSlotsDiv.innerHTML = '<div class="text-warning"><i class="bi bi-calendar-x"></i> No available time slots. Please try another date or select fewer services.</div>';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      timeSlotsDiv.innerHTML = '<div class="text-danger">Error loading time slots. Please try again.</div>';
    });
  }
  
  window.selectTimeSlot = function(element, slot) {
    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
    element.classList.add('selected');
    const time24 = convertTo24Hour(slot);
    document.getElementById('appointmentTime').value = time24;
  };
  
  function convertTo24Hour(time12h) {
    const match = time12h.match(/(\d+):(\d+)\s?(AM|PM)/i);
    if (!match) return time12h;
    let hours = parseInt(match[1]);
    const minutes = match[2];
    const period = match[3].toUpperCase();
    if (period === 'PM' && hours !== 12) hours += 12;
    if (period === 'AM' && hours === 12) hours = 0;
    return hours.toString().padStart(2, '0') + ':' + minutes;
  }
  
  document.getElementById('employeeId').addEventListener('change', function() {
    if (selectedDate) loadTimeSlots(selectedDate, this.value);
  });
  
  // Form submission
  document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const phoneInput = document.getElementById('customerPhone');
    const timeInput = document.getElementById('appointmentTime');
    const dateInput = document.getElementById('appointmentDate');
    const emailInput = document.getElementById('customerEmail');
    const addressInput = document.getElementById('customerAddress');
    const purposeInput = document.getElementById('purpose');
    const employeeInput = document.getElementById('employeeId');
    
    if (!firstNameInput || !phoneInput || !timeInput || !dateInput) {
      console.error('Required form elements not found');
      alert('Form error: Required fields not found. Please refresh the page.');
      return;
    }
    
    const firstName = firstNameInput.value.trim();
    const lastName = lastNameInput ? lastNameInput.value.trim() : '';
    const phone = phoneInput.value.trim();
    const time = timeInput.value;
    const appointmentDate = dateInput.value;
    const email = emailInput ? emailInput.value.trim() : '';
    const address = addressInput ? addressInput.value.trim() : '';
    const purpose = purposeInput ? purposeInput.value.trim() : '';
    const employeeId = employeeInput ? employeeInput.value : '';
    const selectedServices = $('#serviceIds').val();
    
    let errorMessages = [];
    
    if (!appointmentDate) errorMessages.push('Appointment date is missing.');
    if (!firstName) errorMessages.push('First name is required.');
    if (!phone) errorMessages.push('Phone number is required.');
    if (!selectedServices || selectedServices.length === 0) errorMessages.push('Please select at least one service.');
    if (!time) errorMessages.push('Please select a preferred time.');
    
    if (errorMessages.length > 0) {
      alert('Please fix the following:\n\n• ' + errorMessages.join('\n• '));
      return;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    const original = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner"></span> Processing...';
    btn.disabled = true;
    
    const formData = new FormData();
    formData.append('first_name', firstName);
    formData.append('last_name', lastName);
    formData.append('customer_phone', phone);
    formData.append('customer_email', email);
    formData.append('customer_address', address);
    formData.append('appointment_date', appointmentDate);
    formData.append('appointment_time', time);
    formData.append('purpose', purpose);
    formData.append('employee_id', employeeId);
    
    if (selectedServices && selectedServices.length > 0) {
      selectedServices.forEach(serviceId => {
        formData.append('service_ids[]', serviceId);
      });
    }
    
    fetch('process_booking.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.text())
    .then(text => {
      console.log('Response:', text);
      try {
        const data = JSON.parse(text);
        if (data.status === 'success') {
          const appointmentModal = bootstrap.Modal.getInstance(document.getElementById('appointmentModal'));
          if (appointmentModal) appointmentModal.hide();
          
          new bootstrap.Modal(document.getElementById('successModal')).show();
          
          this.reset();
          $('#serviceIds').val(null).trigger('change');
          document.getElementById('timeSlots').innerHTML = '<div class="text-muted">Select services to view available time slots</div>';
          updateServicesSummary();
          calendar.refetchEvents();
        } else {
          alert('Error: ' + (data.message || 'Unknown error'));
        }
      } catch (e) {
        console.error('JSON Parse error:', e);
        alert('Server error. Please try again.');
      }
    })
    .catch(error => {
      console.error('Fetch error:', error);
      alert('Error booking appointment. Please try again.');
    })
    .finally(() => {
      btn.innerHTML = original;
      btn.disabled = false;
    });
  });
  
  // Initialize services summary
  updateServicesSummary();
});
</script>
</body>
</html>