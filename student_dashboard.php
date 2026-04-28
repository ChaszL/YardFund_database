<?php
include 'db_connection.php';

// 1. Capture the ID
$s_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Initialize default values so the HTML doesn't crash
$s = null;
$percent = 0;
$trans_history = null;
$pending_reqs = null;
$denied_reqs = null;

// 3. Only run this block if we have a valid student ID
if ($s_id > 0) {
    // Fetch Live Profile
    $s_query = $conn->query("SELECT * FROM approved_claims WHERE s_id = $s_id");
    $s = $s_query->fetch_assoc();

    // Check if the student actually exists in the approved table
    if ($s) {
        // Handle Update Requests
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_update'])) {
            $stmt = $conn->prepare("INSERT INTO pending_claims (s_id, c_name, c_email, c_major, c_classification, c_gpa, c_tuition, c_urgency, c_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("issssdds", $s_id, $s['s_name'], $s['s_email'], $_POST['major'], $_POST['classification'], $_POST['gpa'], $_POST['tuition'], $_POST['urgency']);
            $stmt->execute();
            $msg = "Update request sent!";
        }

        // Fetch Transaction History
        $trans_sql = "SELECT t.t_amount, t.t_date, d.d_name 
                      FROM transactions t 
                      JOIN donors d ON t.d_id = d.d_id 
                      WHERE t.s_id = $s_id 
                      ORDER BY t.t_date DESC";
        $trans_history = $conn->query($trans_sql);

        // Fetch Status Histories
        $pending_reqs = $conn->query("SELECT * FROM pending_claims WHERE s_id = $s_id");
        $denied_reqs = $conn->query("SELECT * FROM denied_claims WHERE s_id = $s_id ORDER BY denied_at DESC");

        // Calculate progress percentage
        $percent = ($s['s_tuition'] > 0) ? ($s['s_amount_received'] / $s['s_tuition']) * 100 : 0;
    } else {
        $s_id = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | Yard Fund</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .card { border: none; border-radius: 15px; margin-bottom: 25px; }
        .table-section-title { font-size: 0.85rem; letter-spacing: 1px; color: #6c757d; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand fw-bold">Yard Fund <span class="text-primary">Student</span></span>
        <a href="index.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if ($s_id == 0): ?>
        <?php else: ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-white p-4">
                <div class="d-md-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="fw-bold text-dark mb-1">Welcome back, <?php echo htmlspecialchars($s['s_name']); ?>! 👋</h2>
                        <p class="text-muted mb-0">Here is what's happening with your funding today.</p>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <span class="text-secondary small">Student ID: #<?php echo $s_id; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="fw-bold mb-0">Fundraising Progress</h4>
                    <span class="badge bg-success px-3 py-2">$<?php echo number_format($s['s_amount_received'], 2); ?> / $<?php echo number_format($s['s_tuition'], 2); ?></span>
                </div>
                <div class="progress mt-3" style="height: 12px;">
                    <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: <?php echo $percent; ?>%"></div>
                </div>
            </div>

            <div class="card shadow-sm p-4">
                <h6 class="table-section-title fw-bold mb-3 text-uppercase">Detailed Request Logs</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th>Major</th>
                                <th>GPA</th>
                                <th>Goal</th>
                                <th>Class</th>
                                <th>Urgency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($p = $pending_reqs->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge badge-pending border border-warning">Pending</span></td>
                                    <td><?php echo htmlspecialchars($p['c_major']); ?></td>
                                    <td><?php echo $p['c_gpa']; ?></td>
                                    <td>$<?php echo number_format($p['c_tuition'], 2); ?></td>
                                    <td><?php echo $p['c_classification']; ?></td>
                                    <td><span class="small"><?php echo $p['c_urgency']; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php while($d = $denied_reqs->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-danger">Denied</span></td>
                                    <td><?php echo htmlspecialchars($d['de_major']); ?></td>
                                    <td><?php echo $d['de_gpa']; ?></td>
                                    <td>$<?php echo number_format($d['de_tuition'], 2); ?></td>
                                    <td><?php echo $d['de_classification']; ?></td>
                                    <td><span class="small"><?php echo $d['de_urgency']; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if($pending_reqs->num_rows == 0 && $denied_reqs->num_rows == 0): ?>
                                <tr><td colspan="6" class="text-center py-3 text-muted">No recent requests found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm p-4">
                <h6 class="table-section-title fw-bold mb-3 text-uppercase">Recent Contributions</h6>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Donor Name</th>
                                <th>Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($trans_history->num_rows > 0): while($t = $trans_history->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($t['d_name']); ?></td>
                                <td class="text-success fw-bold">+$<?php echo number_format($t['t_amount'], 2); ?></td>
                                <td class="text-muted small"><?php echo date('M d, Y', strtotime($t['t_date'])); ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted small">No donations recorded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm p-4 sticky-top" style="top: 20px;">
                <h6 class="fw-bold mb-3">Request Profile Update</h6>
                <form method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold">Major</label>
                        <input type="text" name="major" class="form-control" value="<?php echo htmlspecialchars($s['s_major']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">GPA</label>
                        <input type="number" step="0.01" name="gpa" class="form-control" value="<?php echo $s['s_gpa']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Tuition Goal</label>
                        <input type="number" step="0.01" name="tuition" class="form-control" value="<?php echo $s['s_tuition']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Classification</label>
                        <select name="classification" class="form-select">
                            <option value="Freshman" <?php if($s['s_classification']=='Freshman') echo 'selected'; ?>>Freshman</option>
                            <option value="Sophomore" <?php if($s['s_classification']=='Sophomore') echo 'selected'; ?>>Sophomore</option>
                            <option value="Junior" <?php if($s['s_classification']=='Junior') echo 'selected'; ?>>Junior</option>
                            <option value="Senior" <?php if($s['s_classification']=='Senior') echo 'selected'; ?>>Senior</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Urgency</label>
                        <select name="urgency" class="form-select">
                            <option value="Low" <?php if($s['s_urgency']=='Low') echo 'selected'; ?>>Low</option>
                            <option value="Medium" <?php if($s['s_urgency']=='Medium') echo 'selected'; ?>>Medium</option>
                            <option value="High" <?php if($s['s_urgency']=='High') echo 'selected'; ?>>High</option>
                        </select>
                    </div>
                    <button type="submit" name="submit_update" class="btn btn-primary w-100 fw-bold shadow-sm">Submit for Review</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>