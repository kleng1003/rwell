<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>

<style>
    /* Form Styles */
    .admin-card {
        max-width: 550px;
        margin: 20px auto;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(135deg, #464660 0%, #5a5a7a 100%);
        color: #fff;
        font-weight: 600;
        font-size: 18px;
        padding: 20px 25px;
        border-bottom: none;
    }
    
    .card-header i {
        margin-right: 10px;
    }
    
    .panel-body {
        padding: 30px 25px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-group label {
        font-weight: 600;
        color: #464660;
        margin-bottom: 8px;
        display: block;
        font-size: 14px;
    }
    
    .form-group label i {
        margin-right: 8px;
        color: #6c757d;
        width: 20px;
    }
    
    .required::after {
        content: " *";
        color: #dc3545;
        font-weight: bold;
    }
    
    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 15px;
        height: auto;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .form-control:focus {
        border-color: #464660;
        box-shadow: 0 0 0 0.2rem rgba(70,70,96,0.25);
        outline: none;
    }
    
    .input-feedback {
        font-size: 12px;
        margin-top: 5px;
        display: none;
    }
    
    .input-feedback.success {
        color: #28a745;
        display: block;
    }
    
    .input-feedback.error {
        color: #dc3545;
        display: block;
    }
    
    .password-strength {
        margin-top: 8px;
        font-size: 12px;
    }
    
    .strength-weak {
        color: #dc3545;
    }
    
    .strength-medium {
        color: #ffc107;
    }
    
    .strength-strong {
        color: #28a745;
    }
    
    .btn-custom {
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
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
    
    .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #9ca3af;
        z-index: 10;
    }
    
    .password-toggle:hover {
        color: #464660;
    }
    
    .input-wrapper {
        position: relative;
    }
    
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #17a2b8;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    
    .info-box i {
        color: #17a2b8;
        margin-right: 10px;
        font-size: 16px;
    }
    
    .info-box p {
        margin: 0;
        font-size: 13px;
        color: #0c5460;
    }
    
    /* Alert Styles */
    .alert-custom {
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 20px;
        border: none;
        font-size: 13px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    
    .alert i {
        margin-right: 8px;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600; color:#464660;">
            <i class="fas fa-user-plus"></i> Add New Admin
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default admin-card">
            <div class="panel-heading card-header">
                <i class="fas fa-user-shield"></i> Admin Registration Form
            </div>

            <div class="panel-body">
                <!-- Info Box -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <p>Create a new administrator account. Admin users have full access to all system features including user management, settings, and reports.</p>
                </div>

                <form method="POST" id="adminForm">
                    <!-- hidden defaults -->
                    <input type="hidden" name="role" value="admin">
                    <input type="hidden" name="status" value="active">

                    <div class="form-group">
                        <label class="required"><i class="fas fa-user"></i> Username</label>
                        <div class="input-wrapper">
                            <input type="text"
                                   name="username"
                                   id="username"
                                   class="form-control"
                                   placeholder="Enter username"
                                   autocomplete="off"
                                   required>
                        </div>
                        <div class="input-feedback" id="usernameFeedback"></div>
                    </div>

                    <div class="form-group">
                        <label class="required"><i class="fas fa-lock"></i> Password</label>
                        <div class="input-wrapper">
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="form-control"
                                   placeholder="Enter password"
                                   required>
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="input-feedback" id="passwordFeedback"></div>
                    </div>

                    <div class="form-group">
                        <label class="required"><i class="fas fa-lock"></i> Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password"
                                   name="cPassword"
                                   id="cPassword"
                                   class="form-control"
                                   placeholder="Re-type password"
                                   required>
                            <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
                        </div>
                        <div class="input-feedback" id="confirmFeedback"></div>
                    </div>

                    <div class="form-group text-right">
                        <a href="admin-account.php" class="btn btn-default-custom btn-custom">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                        <button type="submit"
                                name="register"
                                id="submitBtn"
                                class="btn btn-primary-custom btn-custom">
                            <i class="fas fa-save"></i> Create Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    console.log("Document ready - admin-add.php loaded");
    
    // Test button to check if AJAX is working
    $('#testButton').click(function() {
        console.log("Test button clicked");
        $('#testResult').html('<p style="color:blue;">Testing AJAX...</p>');
        
        $.ajax({
            url: '../Functions/admin_register.php',
            type: 'POST',
            data: {test: 'test'},
            dataType: 'json',
            success: function(res) {
                console.log("Test AJAX success:", res);
                $('#testResult').html('<p style="color:green;">AJAX working! Response: ' + JSON.stringify(res) + '</p>');
            },
            error: function(xhr, status, error) {
                console.error("Test AJAX error:", error);
                console.error("Status:", status);
                console.error("Response:", xhr.responseText);
                $('#testResult').html('<p style="color:red;">AJAX Error: ' + error + '<br>Response: ' + xhr.responseText + '</p>');
            }
        });
    });
    
    // Toggle password visibility
    $('#togglePassword').click(function() {
        const passwordInput = $('#password');
        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
    
    $('#toggleConfirmPassword').click(function() {
        const confirmInput = $('#cPassword');
        const type = confirmInput.attr('type') === 'password' ? 'text' : 'password';
        confirmInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });
    
    // Username availability check
    let usernameTimeout;
    $('#username').on('input', function() {
        clearTimeout(usernameTimeout);
        const username = $(this).val().trim();
        
        if (username.length < 3) {
            $('#usernameFeedback').removeClass('success error').addClass('error')
                .html('<i class="fas fa-exclamation-circle"></i> Username must be at least 3 characters');
            return;
        }
        
        usernameTimeout = setTimeout(function() {
            $.ajax({
                url: '../Functions/check_username.php',
                type: 'POST',
                data: {username: username},
                dataType: 'json',
                success: function(res) {
                    if (res.exists) {
                        $('#usernameFeedback').removeClass('success').addClass('error')
                            .html('<i class="fas fa-times-circle"></i> Username already taken');
                    } else {
                        $('#usernameFeedback').removeClass('error').addClass('success')
                            .html('<i class="fas fa-check-circle"></i> Username available');
                    }
                },
                error: function(xhr) {
                    console.error("Username check error:", xhr.responseText);
                }
            });
        }, 500);
    });
    
    // Password strength checker
    $('#password').on('keyup', function() {
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        
        let strengthHtml = '';
        if (password.length > 0) {
            if (strength < 2) {
                strengthHtml = '<span class="strength-weak"><i class="fas fa-exclamation-circle"></i> Weak password</span>';
            } else if (strength < 4) {
                strengthHtml = '<span class="strength-medium"><i class="fas fa-exclamation-triangle"></i> Medium password</span>';
            } else {
                strengthHtml = '<span class="strength-strong"><i class="fas fa-check-circle"></i> Strong password</span>';
            }
        }
        $('#passwordStrength').html(strengthHtml);
        checkPasswordMatch();
    });
    
    // Password match checker
    $('#cPassword').on('keyup', function() {
        checkPasswordMatch();
    });
    
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        return strength;
    }
    
    function checkPasswordMatch() {
        const password = $('#password').val();
        const confirm = $('#cPassword').val();
        
        if (confirm.length > 0) {
            if (password === confirm) {
                $('#confirmFeedback').removeClass('error').addClass('success')
                    .html('<i class="fas fa-check-circle"></i> Passwords match');
            } else {
                $('#confirmFeedback').removeClass('success').addClass('error')
                    .html('<i class="fas fa-times-circle"></i> Passwords do not match');
            }
        } else {
            $('#confirmFeedback').html('');
        }
    }
    
    // Form validation and submission
    $('#adminForm').submit(function(e) {
        e.preventDefault();
        
        console.log("Form submitted!");
        console.log("Username: " + $('#username').val());
        console.log("Password length: " + $('#password').val().length);
        
        // Validate fields
        const username = $('#username').val().trim();
        const password = $('#password').val();
        const confirm = $('#cPassword').val();
        
        if (username === '') {
            Swal.fire('Error', 'Username is required', 'error');
            return false;
        }
        
        if (username.length < 3) {
            Swal.fire('Error', 'Username must be at least 3 characters', 'error');
            return false;
        }
        
        if (password === '') {
            Swal.fire('Error', 'Password is required', 'error');
            return false;
        }
        
        if (password.length < 6) {
            Swal.fire('Error', 'Password must be at least 6 characters', 'error');
            return false;
        }
        
        if (password !== confirm) {
            Swal.fire('Error', 'Passwords do not match', 'error');
            return false;
        }
        
        // Get form data for debugging
        var formData = $(this).serialize();
        console.log("Form data: " + formData);
        
        // Submit form
        const submitBtn = $('#submitBtn');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Creating...').prop('disabled', true);
        
        $.ajax({
            url: '../Functions/admin_register.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                console.log("Response received:", res);
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: res.message || 'Admin account created successfully',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'admin-account.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Failed to create admin account'
                    });
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to create admin account. Please check console for details.'
                });
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
});
</script>
