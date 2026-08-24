<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Simple Blog</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link active" href="index.php">Home</a>
            <a class="nav-link" href="search.php">Search</a>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['username'] == 'admin'): ?>
                <a class="nav-link" href="admin.php">Admin</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="nav-link" href="feedback.php">Feedback</a>
                <a class="nav-link" href="profile.php">Profile</a>
                <a class="nav-link" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="nav-link" href="login.php">Login</a>
                <a class="nav-link" href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>