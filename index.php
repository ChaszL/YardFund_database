<?php
include 'db_connection.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; // School project: plain text

    // 1. Query the master users table to find the account
    $sql = "SELECT * FROM users WHERE u_email = '$email' AND u_password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $role = $user['u_role'];

        // 2. Redirect based on role and pass the correct Foreign ID in the URL
        if ($role == 'admin') {
            // Use the Admin ID
            header("Location: admin_dashboard.php?id=" . $user['a_id']);
        } 
        else if ($role == 'donor') {
            // Use the Donor ID
            header("Location: browse_students.php?id=" . $user['d_id']);
        } 
        else if ($role == 'student') {
            // Use the Student ID (Note: This will be NULL/0 if not yet approved)
            header("Location: student_dashboard.php?id=" . $user['s_id']);
        }
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Yard Fund | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .login-card { width: 100%; max-width: 400px; border: none; border-radius: 15px; }
        .logo-text { color: #0d6efd; font-weight: 800; letter-spacing: -1px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card mx-auto shadow-lg login-card">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="logo-text">Yard Fund</h1>
                <p class="text-muted small text-uppercase fw-bold">Scholarship Portal Login</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger py-2 small text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success py-2 small text-center"><?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="user@vsu.edu" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary fw-bold py-2 shadow-sm">Sign In</button>
                    <a href="register.php" class="btn btn-outline-secondary btn-sm border-0">Create New Account</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>