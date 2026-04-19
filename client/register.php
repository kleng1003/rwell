<?php
// Start session
session_start();

// Include database connection
require_once '../admin/include/connection.php';

// Check if user is already logged in
if (isset($_SESSION['client_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim(mysqli_real_escape_string($con, $_POST['first_name']));
    $last_name = trim(mysqli_real_escape_string($con, $_POST['last_name']));
    $username = trim(mysqli_real_escape_string($con, $_POST['username']));
    $email = trim(mysqli_real_escape_string($con, $_POST['email']));
    $contact_no = trim(mysqli_real_escape_string($con, $_POST['contact_no']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    // Validate first name
    if (empty($first_name)) {
        $errors[] = "First name is required.";
    } elseif (strlen($first_name) < 2) {
        $errors[] = "First name must be at least 2 characters long.";
    }
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters long.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    } else {
        $check_username = mysqli_query($con, "SELECT client_id FROM tbl_client_accounts WHERE username = '$username'");
        if (mysqli_num_rows($check_username) > 0) {
            $errors[] = "Username already taken. Please choose another one.";
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        $check_email = mysqli_query($con, "SELECT client_id FROM tbl_client_accounts WHERE email = '$email'");
        if (mysqli_num_rows($check_email) > 0) {
            $errors[] = "Email already registered. Please use another email or login.";
        }
    }
    
    // Validate contact number
    if (empty($contact_no)) {
        $errors[] = "Contact number is required.";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $contact_no)) {
        $errors[] = "Please enter a valid contact number (10-15 digits).";
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    }
    
    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Validate terms agreement
    if (!isset($_POST['terms'])) {
        $errors[] = "You must agree to the Terms and Conditions.";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        
        $insert_query = "INSERT INTO tbl_client_accounts (first_name, last_name, username, password, contact_no, email, created_at) 
                        VALUES ('$first_name', '$last_name', '$username', '$hashed_password', '$contact_no', '$email', NOW())";
        
        if (mysqli_query($con, $insert_query)) {
            $client_id = mysqli_insert_id($con);
            
            if (function_exists('logActivity')) {
                logActivity("New client registered", "Client: $first_name ' ' $last_name, Username: $username, ID: $client_id");
            }
            
            $success = "Registration successful! You can now login to your account.";
            
            echo "<script>
                setTimeout(function() {
                    window.location.href = '../index.php';
                }, 3000);
            </script>";
        } else {
            $error = "Registration failed. Please try again later.";
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - R-Well Salon & Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #fff5f7 0%, #ffe6ea 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            max-width: 550px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .register-header {
            background: linear-gradient(135deg, #e91e63, #ff6b6b);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h2 {
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .register-body {
            padding: 40px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .input-group-custom {
            position: relative;
        }
        .input-group-custom i.password-toggle {
            position: absolute;
            right: 15px;
            left: auto;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            z-index: 20;
            pointer-events: auto;
            font-size: 1.2rem;
            padding: 5px;
        }

        .input-group-custom i.password-toggle:hover {
            color: #e91e63;
        }

        
        .input-group-custom i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #e91e63;
            z-index: 10;
            font-size: 1.1rem;
        }
        
        .input-group-custom input {
            padding-left: 45px;
            padding-right: 45px; /* Add padding for the eye icon */
            height: 50px;
            border-radius: 25px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        
        .input-group-custom input:focus {
            border-color: #e91e63;
            box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.15);
            outline: none;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #e91e63, #ff6b6b);
            border: none;
            border-radius: 25px;
            padding: 12px;
            font-weight: bold;
            font-size: 16px;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(233, 30, 99, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .login-link a {
            color: #e91e63;
            text-decoration: none;
            font-weight: bold;
        }
        
        .login-link a:hover {
            color: #d81b60;
            text-decoration: underline;
        }
        
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 12px 20px;
            margin-bottom: 25px;
        }
        
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #e91e63;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        
        .back-home:hover {
            color: #d81b60;
        }
        
        .back-home i {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #e91e63;
        }
        
        .terms-checkbox {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .terms-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            cursor: pointer;
        }
        
        .terms-checkbox label {
            margin-bottom: 0;
            cursor: pointer;
        }
        
        .terms-checkbox a {
            color: #e91e63;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <a href="../index.php" class="back-home">
        <i class="bi bi-arrow-left"></i> Back to Home
    </a>

    <div class="register-container">
        <div class="register-header">
            <h2><i class="bi bi-person-plus-fill me-2"></i>Create Account</h2>
            <p>Join R-Well Salon & Spa today</p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-custom">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $success; ?>
                    <br><small>Redirecting to login page...</small>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm" novalidate>
                <!-- First Name and Last Name -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="input-group-custom">
                                <i class="bi bi-person"></i>
                                <input type="text" class="form-control" name="first_name" id="first_name"
                                       placeholder="First Name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="input-group-custom">
                                <i class="bi bi-person"></i>
                                <input type="text" class="form-control" name="last_name" id="last_name"
                                       placeholder="Last Name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-group-custom">
                        <i class="bi bi-at"></i>
                        <input type="text" class="form-control" name="username" id="username"
                               placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-group-custom">
                        <i class="bi bi-envelope"></i>
                        <input type="email" class="form-control" name="email" id="email"
                               placeholder="Email Address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-group-custom">
                        <i class="bi bi-telephone"></i>
                        <input type="tel" class="form-control" name="contact_no" id="contact_no"
                               placeholder="Contact Number" value="<?php echo isset($_POST['contact_no']) ? htmlspecialchars($_POST['contact_no']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-group-custom">
                        <i class="bi bi-lock"></i>
                        <input type="password" class="form-control" name="password" id="password"
                            placeholder="Password" required autocomplete="new-password">
                        <i class="bi bi-eye-slash password-toggle" id="togglePassword" style="cursor: pointer;"></i>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group-custom">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password"
                            placeholder="Confirm Password" required autocomplete="new-password">
                        <i class="bi bi-eye-slash password-toggle" id="toggleConfirmPassword" style="cursor: pointer;"></i>
                    </div>
                </div>
                
                <div class="terms-checkbox">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms">
                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <span class="btn-text">Create Account</span>
                </button>
                
                <div class="login-link">
                    <p class="mb-0">Already have an account? <a href="../index.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }
            
            if (toggleConfirmPassword && confirmPasswordInput) {
                toggleConfirmPassword.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPasswordInput.setAttribute('type', type);
                    this.classList.toggle('bi-eye');
                    this.classList.toggle('bi-eye-slash');
                });
            }
            
            // Form validation
            const form = document.getElementById('registerForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = passwordInput.value;
                    const confirm = confirmPasswordInput.value;
                    
                    if (password !== confirm) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                    }
                });
            }
        });
    </script>
</body>
</html>