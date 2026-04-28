<?php
include 'db_connection.php';
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; 
    $role = $_POST['role'];

    // New Student Fields
    $major = isset($_POST['major']) ? $conn->real_escape_string($_POST['major']) : "";
    $gpa = isset($_POST['gpa']) ? (float)$_POST['gpa'] : 0.0;
    $class = isset($_POST['classification']) ? $conn->real_escape_string($_POST['classification']) : "";
    $tuition = isset($_POST['tuition']) ? (float)$_POST['tuition'] : 0.0;
    $urgency = isset($_POST['urgency']) ? $conn->real_escape_string($_POST['urgency']) : "";

    $check = $conn->query("SELECT u_id FROM users WHERE u_email = '$email'");
    if ($check->num_rows > 0) {
        $msg = "Error: This email is already registered.";
    } else {
        if ($role == 'donor') {
            $conn->query("INSERT INTO donors (d_name, d_email) VALUES ('$name', '$email')");
            $d_id = $conn->insert_id;
            $sql = "INSERT INTO users (u_email, u_password, u_role, d_id) VALUES ('$email', '$password', 'donor', $d_id)";
            $conn->query($sql);
        } else {
            // 1. Create the User login
            $sql = "INSERT INTO users (u_email, u_password, u_role) VALUES ('$email', '$password', 'student')";
            $conn->query($sql);

            // 2. Create the full Pending Claim with academic details
            $stmt = $conn->prepare("INSERT INTO pending_claims (c_name, c_email, c_major, c_classification, c_gpa, c_tuition, c_urgency, c_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("ssssdds", $name, $email, $major, $class, $gpa, $tuition, $urgency);
            $stmt->execute();
        }

        header("Location: index.php?msg=Registration successful! Admin will review your profile.");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | Yard Fund</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .register-card { max-width: 600px; margin: 50px auto; border-radius: 15px; border: none; }
        #student-fields { display: none; } /* Hidden by default, shown via JS */
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow-sm register-card">
        <div class="card-body p-5">
            <h3 class="text-center fw-bold mb-4">Create Your Account</h3>
            
            <?php if($msg): ?>
                <div class="alert alert-danger small"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Account Type</label>
                    <select name="role" id="roleSelect" class="form-select" onchange="toggleStudentFields()" required>
                        <option value="donor">Donor (Support Students)</option>
                        <option value="student">Student (Apply for Funding)</option>
                    </select>
                </div>

                <div id="student-fields" class="bg-light p-3 rounded mb-4">
                    <h6 class="fw-bold text-primary mb-3">Academic Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Current Major</label>
                            <input type="text" name="major" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Classification</label>
                            <select name="classification" class="form-select">
                                <option value="Freshman">Freshman</option>
                                <option value="Sophomore">Sophomore</option>
                                <option value="Junior">Junior</option>
                                <option value="Senior">Senior</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Current GPA</label>
                            <input type="number" step="0.01" name="gpa" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Tuition Needed ($)</label>
                            <input type="number" step="0.01" name="tuition" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Urgency Level</label>
                            <select name="urgency" class="form-select">
                                <option value="Low">Low (Next Semester)</option>
                                <option value="Medium">Medium (Upcoming Deadline)</option>
                                <option value="High">High (Immediate Assistance)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary fw-bold py-2">Complete Registration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleStudentFields() {
    var role = document.getElementById("roleSelect").value;
    var fields = document.getElementById("student-fields");
    fields.style.display = (role === "student") ? "block" : "none";
}
</script>

</body>
</html>