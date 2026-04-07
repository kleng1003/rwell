<?php
// Use absolute path based on document root
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/RWELL/';
include_once($root_path . 'admin/include/connection.php');

// Fetch employees for dropdown
$employees_query = mysqli_query($con, "
    SELECT e.employee_id, e.first_name, e.last_name, e.position
    FROM employees e
    WHERE e.status = 'active'
    ORDER BY e.first_name ASC
");

// Fetch services for dropdown
$services_query = mysqli_query($con, "
    SELECT service_id, service_name, price
    FROM services
    WHERE status = 'active'
    ORDER BY service_name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Appointment Calendar | RWell Salon & Spa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    /* Your existing styles */
    body {
      background: linear-gradient(135deg, #fff5f0 0%, #ffe8e0 100%);
      font-family: 'Segoe UI', sans-serif;
    }
    
    .navbar {
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    #calendar {
      max-width: 1000px;
      margin: 30px auto;
      background: white;
      padding: 25px;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.1);
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
    
    .modal-header {
      background: linear-gradient(135deg, #e91e63 0%, #d81b60 100%);
      color: white;
      border: none;
    }
    
    .modal-header .btn-close {
      background-color: white;
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
    
    .required::after {
      content: " *";
      color: #dc3545;
    }
    
    .time-slots {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
      max-height: 200px;
      overflow-y: auto;
      padding: 10px;
      background: #f8f9fa;
      border-radius: 10px;
    }
    
    .time-slot {
      background: white;
      border: 1px solid #dee2e6;
      padding: 8px 16px;
      border-radius: 25px;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .time-slot:hover, .time-slot.selected {
      background: #e91e63;
      color: white;
      border-color: #e91e63;
    }
    
    .time-slot.booked {
      background: #f8d7da;
      color: #721c24;
      cursor: not-allowed;
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
    }
    
    .btn-secondary-custom {
      background: #6c757d;
      color: white;
      padding: 8px 20px;
      border-radius: 25px;
      text-decoration: none;
    }
    
    .btn-secondary-custom:hover {
      background: #5a6268;
      color: white;
    }
    
    .working-hours-info {
      background: #e7f3ff;
      padding: 10px 15px;
      border-radius: 10px;
      margin-bottom: 15px;
      font-size: 13px;
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
    
    @media (max-width: 768px) {
      #calendar {
        margin: 20px;
        padding: 15px;
      }
    }
  </style>
</head>
<body>

<nav class="navbar navbar-light bg-light shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">
      <i class="bi bi-scissors"></i> RWell Salon & Spa
    </a>
    <a href="../../index.php" class="btn-secondary-custom">
      <i class="bi bi-arrow-left"></i> Back to Home
    </a>
  </div>
</nav>

<div id="calendar"></div>

<!-- Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="appointmentForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-check"></i> Book an Appointment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="appointmentDate" name="appointment_date" />
        
        <!-- Customer Name - Split into First and Last -->
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="required"><i class="bi bi-person"></i> First Name</label>
              <input type="text" class="form-control" id="firstName" name="first_name" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="required"><i class="bi bi-person"></i> Last Name</label>
              <input type="text" class="form-control" id="lastName" name="last_name" required>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label class="required"><i class="bi bi-telephone"></i> Phone Number</label>
              <input type="tel" class="form-control" id="customerPhone" name="customer_phone" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="bi bi-envelope"></i> Email</label>
              <input type="email" class="form-control" id="customerEmail" name="customer_email">
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
              <label><i class="bi bi-geo-alt"></i> Address</label>
              <input type="text" class="form-control" id="customerAddress" name="customer_address">
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="bi bi-person-badge"></i> Preferred Staff</label>
              <select class="form-select" id="employeeId" name="employee_id">
                <option value="">Any Available Staff</option>
                <?php while($employee = mysqli_fetch_assoc($employees_query)): ?>
                  <option value="<?= $employee['employee_id']; ?>">
                    <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?> 
                    (<?= htmlspecialchars($employee['position']); ?>)
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><i class="bi bi-tags"></i> Select Service</label>
              <select class="form-select" id="serviceId" name="service_id">
                <option value="">Select a service</option>
                <?php while($service = mysqli_fetch_assoc($services_query)): ?>
                  <option value="<?= $service['service_id']; ?>">
                    <?= htmlspecialchars($service['service_name']); ?> - ₱<?= number_format($service['price'], 2); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
        </div>
        
        <div class="form-group">
          <label class="required"><i class="bi bi-clock"></i> Preferred Time</label>
          <input type="hidden" id="appointmentTime" name="appointment_time">
          <div id="timeSlots" class="time-slots">
            <div class="text-muted">Select a date first</div>
          </div>
        </div>
        
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
  <div class="modal-dialog modal-sm">
    <div class="modal-content text-center">
      <div class="modal-body" style="padding: 40px;">
        <i class="bi bi-check-circle-fill" style="font-size: 60px; color: #28a745;"></i>
        <h4 class="mt-3">Booking Submitted!</h4>
        <p class="text-muted">Your appointment request has been sent. We will contact you shortly to confirm.</p>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  let selectedDate = '';
  
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    validRange: { start: new Date().toISOString().split('T')[0] },
    selectable: true,
    headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
    // Return empty array to remove all displayed appointments from calendar slots
    events: function(info, successCallback, failureCallback) {
      // Return empty array - no appointments will be displayed on calendar
      successCallback([]);
    },
    eventColor: '#e91e63',
    dateClick: function(info) {
      selectedDate = info.dateStr;
      document.getElementById('appointmentDate').value = selectedDate;
      loadTimeSlots(selectedDate, document.getElementById('employeeId').value);
      new bootstrap.Modal(document.getElementById('appointmentModal')).show();
    }
  });
  
  calendar.render();
  
  function loadTimeSlots(date, employeeId) {
    const timeSlotsDiv = document.getElementById('timeSlots');
    timeSlotsDiv.innerHTML = '<div class="text-center"><span class="loading-spinner"></span> Loading available slots...</div>';
    
    fetch('get_time_slots.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'date=' + encodeURIComponent(date) + '&employee_id=' + encodeURIComponent(employeeId)
    })
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        timeSlotsDiv.innerHTML = '<div class="text-danger">' + data.error + '</div>';
        return;
      }
      
      if (data.slots && data.slots.length > 0) {
        let html = '<div class="working-hours-info"><i class="bi bi-clock-history"></i> Working hours: ' + data.start_time + ' - ' + data.end_time + '</div>';
        data.slots.forEach(slot => {
          const isBooked = data.booked_times && data.booked_times.includes(slot);
          html += `<div class="time-slot ${isBooked ? 'booked' : ''}" data-time="${slot}" onclick="${isBooked ? '' : 'selectTimeSlot(this, \'' + slot + '\')'}">
            ${slot} ${isBooked ? '(Booked)' : ''}
          </div>`;
        });
        timeSlotsDiv.innerHTML = html;
      } else {
        timeSlotsDiv.innerHTML = '<div class="text-warning">No available time slots for this date. Please try another date.</div>';
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
  
document.getElementById('appointmentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const phone = document.getElementById('customerPhone').value.trim();
    const time = document.getElementById('appointmentTime').value;
    
    if (!firstName || !lastName || !phone || !time) {
        alert('Please fill all required fields');
        return;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    const original = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner"></span> Processing...';
    btn.disabled = true;
    
    const formData = new FormData(this);
    
    // Log form data for debugging
    console.log('Submitting form data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    fetch('process_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Get raw response first
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            if (data.status === 'success') {
                bootstrap.Modal.getInstance(document.getElementById('appointmentModal')).hide();
                new bootstrap.Modal(document.getElementById('successModal')).show();
                this.reset();
                document.getElementById('timeSlots').innerHTML = '<div class="text-muted">Select a date first</div>';
                
                // Note: Calendar events are disabled - no appointment will be added to calendar display
                // Uncomment the lines below if you want to add the appointment back to calendar after booking
                /*
                const eventTitle = firstName + ' ' + lastName + ' - Appointment';
                const eventStart = selectedDate + 'T' + time;
                calendar.addEvent({
                    title: eventTitle,
                    start: eventStart,
                    color: '#e91e63'
                });
                */
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error('JSON Parse error:', e);
            alert('Server error: ' + text.substring(0, 200));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Error booking appointment. Please check console for details.');
    })
    .finally(() => {
        btn.innerHTML = original;
        btn.disabled = false;
    });
});
});
</script>
</body>
</html>