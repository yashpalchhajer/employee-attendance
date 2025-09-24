<?php
require_once '../config.php';
requireLogin();
requireAdmin();

$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_employee'])) {
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = $_POST['full_name'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'employee')");
            $stmt->execute([$username, $password, $full_name]);
            $message = "Employee added successfully!";
        } catch (Exception $e) {
            $message = "Error: Username already exists or database error.";
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $user_id = $_POST['user_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'employee'");
        $stmt->execute([$new_status, $user_id]);
        $message = "Employee status updated!";
    }
    
    if (isset($_POST['delete_employee'])) {
        $user_id = $_POST['user_id'];
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$user_id]);
        $message = "Employee deleted successfully!";
    }
}

// Get all employees
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'employee' ORDER BY full_name");
$stmt->execute();
$employees = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Manage Employees</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> | 
                <a href="../logout.php">Logout</a>
            </div>
        </header>
        
        <nav class="admin-nav">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="employees.php" class="nav-link active">Manage Employees</a>
            <a href="attendance.php" class="nav-link">View Attendance</a>
        </nav>
        
        <div class="main-content">
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <h3>Add New Employee</h3>
                <form method="POST" class="employee-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_employee" class="btn-primary">Add Employee</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="employees-list">
                <h3>Employee List</h3>
                <?php if ($employees): ?>
                    <table class="employees-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo $employee['id']; ?></td>
                                    <td><?php echo htmlspecialchars($employee['username']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $employee['status']; ?>">
                                            <?php echo ucfirst($employee['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($employee['created_at'])); ?></td>
                                    <td class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $employee['status'] == 'active' ? 'disabled' : 'active'; ?>">
                                            <button type="submit" name="toggle_status" class="btn-small btn-<?php echo $employee['status'] == 'active' ? 'warning' : 'success'; ?>">
                                                <?php echo $employee['status'] == 'active' ? 'Disable' : 'Enable'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                            <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                            <button type="submit" name="delete_employee" class="btn-small btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No employees found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>