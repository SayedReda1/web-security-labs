<?php
require_once 'config.php';

$search_query = '';
$results = null;
$error = null;

try {
    if (isset($_GET['q'])) {
        $search_query = $_GET['q'];
        
        $conn = getConnection();
        
        $sql = "SELECT title, content, created_at
                FROM posts
                WHERE title LIKE '%$search_query%' OR content LIKE '%$search_query%';";
        
        $results = $conn->query($sql);
    }
}
catch(Exception $e) {
    $error = $e->getMessage();
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Simple Blog</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="search.php">Search</a>
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
        <h1 class="mb-4">Search Posts</h1>
        
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="q" placeholder="Search for posts..." 
                       value="<?php echo $search_query; ?>">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger">Error: <?= $error ?></div>
        <? endif ?>
        
        <?php if ($results !== null): ?>
            <h3 class="mb-3">Search Results</h3>
            
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while($post = $results->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $post['title'] ?? "None"; ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                on <?php echo date('F j, Y', strtotime($post['created_at'] ?? "None")); ?>
                            </h6>
                            <p class="card-text"><?php echo $post['content'] ?? "None"; ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No posts found matching your search.</div>
            <?php endif; ?>
            
            <?php if (isset($conn)) $conn->close(); ?>
        <?php endif; ?>
    </div>
</body>
</html>
