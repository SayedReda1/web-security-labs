<?php
require_once 'include/config.php';

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
    <title>Simple Blog - XSS Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    <!-- Navbar -->
    <?php require_once 'include/navbar.php'; ?>

    <!-- Main content -->
    <div class="container mt-4">
        <h1 class="mb-4">Blog Posts</h1>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($post = $result->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            By <?php echo htmlspecialchars($post['username']); ?> 
                            on <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                        </h6>
                        <p class="card-text"><?php echo htmlspecialchars(substr($post['content'], 0, 200)); ?>...</p>
                        <a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No posts found.</p>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php require_once 'include/footer.php'; ?>

    <?php $conn->close(); ?>
</body>
</html>
