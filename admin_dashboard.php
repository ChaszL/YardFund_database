<?php
include 'db_connection.php';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Fetch claim data
    $claimResult = $conn->query("SELECT * FROM pending_claims WHERE c_id = $id");
    $c = $claimResult->fetch_assoc();

    if ($c) {
        if ($_GET['action'] == 'approve') {
            $s_id = $c['s_id'];
            if (empty($s_id) || $s_id == 0) {
                // New Student: Create profile and link User account
                $stmt = $conn->prepare("INSERT INTO approved_claims (s_name, s_email, s_major, s_classification, s_gpa, s_tuition, s_urgency) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdds", $c['c_name'], $c['c_email'], $c['c_major'], $c['c_classification'], $c['c_gpa'], $c['c_tuition'], $c['c_urgency']);
                $stmt->execute();
                $new_s_id = $conn->insert_id;
                $conn->query("UPDATE users SET s_id = $new_s_id WHERE u_email = '{$c['c_email']}'");
            } else {
                // Update Student: Sync new data to live profile
                $stmt = $conn->prepare("UPDATE approved_claims SET s_major=?, s_classification=?, s_gpa=?, s_tuition=?, s_urgency=? WHERE s_id=?");
                $stmt->bind_param("ssddsi", $c['c_major'], $c['c_classification'], $c['c_gpa'], $c['c_tuition'], $c['c_urgency'], $s_id);
                $stmt->execute();
            }
            $msg = "Approved successfully.";
        } 
        else if ($_GET['action'] == 'deny') {
            // Migration to denied_claims (No reason column, full data archive)
            $stmt = $conn->prepare("INSERT INTO denied_claims (c_id, de_name, de_email, de_major, de_classification, de_gpa, de_tuition, de_urgency, s_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssddsi", $c['c_id'], $c['c_name'], $c['c_email'], $c['c_major'], $c['c_classification'], $c['c_gpa'], $c['c_tuition'], $c['c_urgency'], $c['s_id']);
            $stmt->execute();
            $msg = "Claim denied and archived.";
        }
        $conn->query("DELETE FROM pending_claims WHERE c_id = $id");
        header("Location: admin_dashboard.php?msg=" . urlencode($msg));
        exit();
    }
}
$pending = $conn->query("SELECT * FROM pending_claims ORDER BY submitted_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Panel | Yard Fund</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <span class="navbar-brand fw-bold">Yard Fund <span class="text-primary">Admin</span></span>
        <div class="navbar-nav ms-auto">
            <a href="index.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Pending Actions</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Student</th>
                        <th>Major</th>
                        <th>GPA</th>
                        <th>Goal</th>
                        <th>Class</th>
                        <th>Urgency</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $pending->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if(empty($row['s_id']) || $row['s_id'] == 0): ?>
                                <span class="badge bg-info text-dark">New Student</span>
                            <?php else: ?>
                                <span class="badge bg-secondary text-white">Profile Update</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['c_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($row['c_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['c_major']); ?></td>
                        <td><span class="fw-bold"><?php echo $row['c_gpa']; ?></span></td>
                        <td>$<?php echo number_format($row['c_tuition'], 2); ?></td>
                        <td><?php echo $row['c_classification']; ?></td>
                        <td>
                            <span class="badge <?php echo ($row['c_urgency'] == 'High') ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                <?php echo $row['c_urgency']; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="admin_dashboard.php?action=approve&id=<?php echo $row['c_id']; ?>" 
                                class="btn btn-success btn-sm px-3 fw-bold shadow-sm">
                                Approve
                                </a>
                                <a href="admin_dashboard.php?action=deny&id=<?php echo $row['c_id']; ?>" 
                                class="btn btn-danger btn-sm px-3 fw-bold shadow-sm">
                                Deny
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>