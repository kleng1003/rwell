<?php
include_once('../include/template.php');
include_once('../include/connection.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['userid']) || empty($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get logs with filters
$where = "1=1";
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $user_id = mysqli_real_escape_string($con, $_GET['user_id']);
    $where .= " AND user_id = '$user_id'";
}
if (isset($_GET['action']) && !empty($_GET['action'])) {
    $action = mysqli_real_escape_string($con, $_GET['action']);
    $where .= " AND action LIKE '%$action%'";
}
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $date_from = mysqli_real_escape_string($con, $_GET['date_from']);
    $where .= " AND DATE(created_at) >= '$date_from'";
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_to = mysqli_real_escape_string($con, $_GET['date_to']);
    $where .= " AND DATE(created_at) <= '$date_to'";
}

$sql = "SELECT * FROM activity_logs WHERE $where ORDER BY created_at DESC LIMIT 1000";
$result = mysqli_query($con, $sql);

// Get unique users for filter
$users = mysqli_query($con, "SELECT DISTINCT user_id, username FROM activity_logs ORDER BY username");
?>

<style>
    .filter-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .log-entry {
        border-left: 3px solid #464660;
        margin-bottom: 10px;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 4px;
    }
    
    .log-time {
        font-size: 12px;
        color: #6c757d;
    }
    
    .log-user {
        font-weight: 600;
        color: #464660;
    }
    
    .log-action {
        color: #28a745;
        font-weight: 500;
    }
    
    .log-details {
        font-size: 13px;
        color: #6c757d;
        margin-top: 5px;
    }
    
    .log-ip {
        font-size: 11px;
        color: #adb5bd;
        margin-top: 5px;
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header" style="font-weight:600; color:#464660;">
            <i class="fas fa-history"></i> System Activity Logs
        </h1>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <form method="GET" class="form-inline">
        <div class="form-group">
            <label>User:</label>
            <select name="user_id" class="form-control">
                <option value="">All Users</option>
                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                    <option value="<?= $user['user_id']; ?>" <?= (isset($_GET['user_id']) && $_GET['user_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($user['username']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Action:</label>
            <input type="text" name="action" class="form-control" value="<?= $_GET['action'] ?? ''; ?>" placeholder="Search action...">
        </div>
        
        <div class="form-group">
            <label>Date From:</label>
            <input type="date" name="date_from" class="form-control" value="<?= $_GET['date_from'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <label>Date To:</label>
            <input type="date" name="date_to" class="form-control" value="<?= $_GET['date_to'] ?? ''; ?>">
        </div>
        
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="activity_logs.php" class="btn btn-default">Reset</a>
    </form>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <strong><i class="fas fa-list"></i> Activity Logs</strong>
            </div>
            <div class="panel-body">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($result)): ?>
                        <div class="log-entry">
                            <div class="log-time">
                                <i class="fas fa-clock"></i> 
                                <?= date('M d, Y h:i:s A', strtotime($log['created_at'])); ?>
                            </div>
                            <div class="log-user">
                                <i class="fas fa-user"></i> 
                                <?= htmlspecialchars($log['username']); ?>
                                <span class="label label-<?= $log['role'] == 'admin' ? 'danger' : 'info'; ?>">
                                    <?= ucfirst($log['role']); ?>
                                </span>
                            </div>
                            <div class="log-action">
                                <i class="fas fa-<?= strpos($log['action'], 'login') !== false ? 'sign-in-alt' : (strpos($log['action'], 'added') !== false ? 'plus' : (strpos($log['action'], 'updated') !== false ? 'edit' : 'info-circle')); ?>"></i>
                                <?= htmlspecialchars($log['action']); ?>
                            </div>
                            <?php if (!empty($log['details'])): ?>
                                <div class="log-details">
                                    <i class="fas fa-info-circle"></i> 
                                    <?= htmlspecialchars($log['details']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="log-ip">
                                <i class="fas fa-network-wired"></i> IP: <?= htmlspecialchars($log['ip_address']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted text-center">No activity logs found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>