<?php
require_once 'include/config.php';

$conn = getConnection();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    
    $post_id = (int)$_POST['post_id'];
    $author_id = $_SESSION['user_id'];
    $content = $_POST['comment'];
    
    $stmt = $conn->prepare("INSERT INTO comments (post_id, author_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $author_id, $content);
    $stmt->execute();
    $stmt->close();
    
    header("Location: post.php?id=$post_id");
    exit;
}

// Get post
$post = null;
$comments = null;

if (isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];
    
    $sql = "SELECT p.*, u.username FROM posts p JOIN users u ON p.author_id = u.id WHERE p.id = $post_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $post = $result->fetch_assoc();
    }
    
    // Get comments for this post
    $sql = "SELECT p.*, u.username FROM comments p JOIN users u ON p.author_id = u.id WHERE p.post_id = $post_id ORDER BY created_at ASC";
    $comments = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $post ? htmlspecialchars($post['title']) : 'Post Not Found'; ?> - Simple Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'include/navbar.php'; ?>

    <div class="container mt-4">
        <?php if ($post): ?>
            <article>
                <h1 class="mb-2"><?php echo htmlspecialchars($post['title']); ?></h1>
                <h6 class="text-muted mb-4">
                    By <?php echo htmlspecialchars($post['username']); ?> 
                    on <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
                </h6>
                <p class="lead"><?php echo htmlspecialchars($post['content']); ?></p>
            </article>

            <hr>

            <h3 class="mb-3">Comments (<?php echo $comments ? $comments->num_rows : 0; ?>)</h3>
            
            <?php if ($comments && $comments->num_rows > 0): ?>
                <?php while($comment = $comments->fetch_assoc()): ?>
                    <div class="card mb-2">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-1 text-muted">
                                <?php
                                    echo htmlspecialchars($comment['username']);
                                ?>
                                <small class="text-muted ms-2"><?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?></small>
                            </h6>
                            <p class="card-text mt-2">
                                <?php
                                    echo $comment['content'];
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted">No comments yet. Be the first to comment!</p>
            <?php endif; ?>

            <!-- Comment Form -->
            <div class="card mt-4 mb-4">
                <div class="card-header">
                    <h5>Leave a Comment</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Posting as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></label>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Comment</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Post Comment</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            Please <a href="login.php">login</a> to leave a comment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div class="alert alert-warning">Post not found.</div>
            <a href="index.php" class="btn btn-primary">Back to Home</a>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php require_once 'include/footer.php'; ?>

    <?php $conn->close(); ?>
</body>
</html>
