<?php
require_once 'include/config.php';

// Simple access control
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = getConnection();

// Fetch all feedbacks
$sql = "SELECT f.*, u.username as submitter 
        FROM feedbacks f 
        LEFT JOIN users u ON f.user_id = u.id 
        ORDER BY f.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Simple Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'include/navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Admin Dashboard - Customer Feedbacks</h1>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Feedbacks</h5>
                        <span class="badge bg-primary"><?php echo $result->num_rows; ?> Total</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 150px;">Date</th>
                                        <th style="width: 150px;">User</th>
                                        <th style="width: 120px;">Rating</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while($feedback = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="text-muted small">
                                                    <?php echo date('Y-m-d H:i', strtotime($feedback['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo $feedback['submitter'] ? htmlspecialchars($feedback['submitter']) : 'Guest'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-warning">
                                                        <?php for($i = 0; $i < 5; $i++): ?>
                                                            <i class="<?php echo $i < $feedback['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="p-2 bg-light rounded border">
                                                        <?php 
                                                            // INTENTIONALLY VULNERABLE TO BLIND XSS
                                                            // Feedback message is rendered without escaping
                                                            echo $feedback['message']; 
                                                        ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                No feedback received yet.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once 'include/footer.php'; ?>
</body>
</html>
<?php
$conn->close();
?>
