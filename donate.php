<?php
include 'db_connection.php';

// 1. Get Student ID (who is receiving) and Donor ID (who is giving) from URL
$s_id = isset($_GET['s_id']) ? (int)$_GET['s_id'] : 0;
$d_id = isset($_GET['d_id']) ? (int)$_GET['d_id'] : 0;

// Fetch student details for the display card
$stmt = $conn->prepare("SELECT s_name, s_tuition, s_amount_received FROM approved_claims WHERE s_id = ?");
$stmt->bind_param("i", $s_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Fetch donor details to confirm who is paying
$d_stmt = $conn->prepare("SELECT d_name FROM donors WHERE d_id = ?");
$d_stmt->bind_param("i", $d_id);
$d_stmt->execute();
$donor = $d_stmt->get_result()->fetch_assoc();

if (!$student || !$donor) {
    header("Location: browse_students.php?id=$d_id&error=InvalidID");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = (float)$_POST['d_amount'];

    // Start a transaction to ensure all updates happen or none do
    $conn->begin_transaction();

    try {
        // A. Update Student's total received amount
        $updStudent = $conn->prepare("UPDATE approved_claims SET s_amount_received = s_amount_received + ? WHERE s_id = ?");
        $updStudent->bind_param("di", $amount, $s_id);
        $updStudent->execute();

        // B. UPDATED: Update Donor's total donations AND increment the donation count by 1
        $updDonor = $conn->prepare("UPDATE donors SET d_total_donations = d_total_donations + ?, d_donation_count = d_donation_count + 1 WHERE d_id = ?");
        $updDonor->bind_param("di", $amount, $d_id);
        $updDonor->execute();

        // C. Record the event in the Transactions Ledger
        $logTrans = $conn->prepare("INSERT INTO transactions (d_id, s_id, t_amount) VALUES (?, ?, ?)");
        $logTrans->bind_param("iid", $d_id, $s_id, $amount);
        $logTrans->execute();

        $conn->commit();
        header("Location: browse_students.php?id=$d_id&msg=Success! Thank you for your donation.");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Transaction failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donate | Yard Fund</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 15px; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h5 class="text-muted small fw-bold text-uppercase">You are supporting</h5>
                    <h3 class="fw-bold mb-4"><?php echo htmlspecialchars($student['s_name']); ?></h3>
                    
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Donation Amount ($)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="d_amount" step="0.01" min="1" class="form-control form-control-lg" placeholder="0.00" required autofocus>
                            </div>
                        </div>

                        <div class="alert alert-info small border-0">
                            Logged in as: <strong><?php echo htmlspecialchars($donor['d_name']); ?></strong>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg fw-bold">Confirm Donation</button>
                            <a href="browse_students.php?id=<?php echo $d_id; ?>" class="btn btn-link text-muted">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>