<?php 
include 'db_connection.php'; 

// Get the Donor ID from the URL (passed from index.php login)
$d_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch donor details for the welcome message
$donor_name = "Donor"; // Default fallback
if ($d_id > 0) {
    $d_query = $conn->prepare("SELECT d_name FROM donors WHERE d_id = ?");
    $d_query->bind_param("i", $d_id);
    $d_query->execute();
    $d_result = $d_query->get_result();
    if ($donor = $d_result->fetch_assoc()) {
        $donor_name = $donor['d_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Yard Fund | Browse Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero { background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 60px 0; }
        .student-card { border: none; border-radius: 15px; transition: 0.3s; }
        .student-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .progress { height: 10px; border-radius: 5px; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand fw-bold">Yard Fund <span class="text-primary">Donor</span></span>
        <div class="navbar-nav ms-auto">
            <a href="index.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="hero text-center mb-5">
    <div class="container">
        <p class="text-uppercase fw-bold mb-2" style="letter-spacing: 1px; font-size: 1.75rem; opacity: 0.8;">
            Welcome back, <?php echo htmlspecialchars($donor_name); ?>!
        </p>
        
        <h1 class="display-4 fw-bold">Support a Student</h1>
        
        <p class="lead">Your contribution directly impacts these verified academic journeys.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php
        // Fetch all students who have been APPROVED by the admin
        $sql = "SELECT * FROM approved_claims";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                // Calculate progress percentage
                $percent = ($row['s_tuition'] > 0) ? ($row['s_amount_received'] / $row['s_tuition']) * 100 : 0;
        ?>
        <div class="col">
            <div class="card h-100 student-card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($row['s_name']); ?></h5>
                        <span class="badge bg-primary rounded-pill"><?php echo $row['s_classification']; ?></span>
                    </div>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($row['s_major']); ?></p>
                    
                    <div class="row mb-3 text-center">
                        <div class="col-6 border-end">
                            <div class="small text-muted">GPA</div>
                            <div class="fw-bold text-dark"><?php echo $row['s_gpa']; ?></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Urgency</div>
                            <div class="fw-bold text-danger"><?php echo htmlspecialchars($row['s_urgency']); ?></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between small mb-1">
                        <span>Raised: <strong>$<?php echo number_format($row['s_amount_received'], 2); ?></strong></span>
                        <span class="text-muted">Goal: $<?php echo number_format($row['s_tuition'], 2); ?></span>
                    </div>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-success" style="width: <?php echo $percent; ?>%"></div>
                    </div>

                    <div class="d-grid">
                        <a href="donate.php?s_id=<?php echo $row['s_id']; ?>&d_id=<?php echo $d_id; ?>" 
                           class="btn btn-primary fw-bold py-2">
                           Donate Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; else: ?>
            <div class="col-12 text-center py-5">
                <div class="alert alert-info">No verified student requests are currently active.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>