<?php
require_once 'include/config.php';

$search_query = '';
$results = null;

if (isset($_GET['q'])) {
    $search_query = $_GET['q'];
    
    $conn = getConnection();
    
    // Use parameterized query for SQL (this lab focuses on XSS, not SQLi)
    $stmt = $conn->prepare("SELECT p.id, p.title, p.content, p.created_at, u.username FROM posts p JOIN users u ON p.author_id = u.id WHERE p.title LIKE ? OR p.content LIKE ? ORDER BY p.created_at DESC");
    $like = "%$search_query%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - Simple Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'include/navbar.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">Search Posts</h1>
        
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="q" placeholder="Search for posts..." 
                       value="<?php echo $search_query; ?>">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <?php if (isset($_GET['q'])): ?>
            <h3 class="mb-3">Search results for: <?php echo $search_query; ?></h3>
            
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while($post = $results->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="post.php?id=<?php echo $post['id']; ?>" class="text-decoration-none">
                                    <?= $post['title']; ?>
                                </a>
                            </h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                By <?= $post['username'] ?> 
                                on <?= date('F j, Y', strtotime($post['created_at'])); ?>
                            </h6>
                            <p class="card-text"><?= substr($post['content'], 0, 200); ?>...</p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No posts found matching your search.</div>
            <?php endif; ?>
            
            <?php if (isset($conn)) $conn->close(); ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php require_once 'include/footer.php'; ?>
</body>
</html>