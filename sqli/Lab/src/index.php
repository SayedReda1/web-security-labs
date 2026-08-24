<?php
require_once 'config.php';

$conn = getConnection();

// Get all posts
$sql = "SELECT p.*, u.username FROM posts p JOIN users u ON p.author_id = u.id ORDER BY p.created_at DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Blog - SQL Injection Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Simple Blog</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="search.php">Search</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Blog Posts</h1>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($post = $result->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            By <?php echo htmlspecialchars($post['username']); ?> 
                            on <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                        </h6>
                        <p class="card-text"><?php echo htmlspecialchars($post['content']); ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No posts found.</p>
        <?php endif; ?>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
