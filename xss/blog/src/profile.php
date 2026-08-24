<?php
require_once 'include/config.php';

$user = null;

if (isset($_SESSION['user_id'])) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
    $conn->close();
} else {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Simple Blog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'include/navbar.php'; ?>

    <div class="container mt-4">
        <?php if ($user): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3>User Profile</h3>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <tr>
                                    <th>Username</th>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Member Since</th>
                                    <td><?php echo date('F j, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Bio</th>
                                    <td><?php echo htmlspecialchars($user['bio']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Profile Tabs</h5>
                            <ul class="nav nav-tabs card-header-tabs mt-2" id="profileTabs">
                                <li class="nav-item">
                                    <a class="nav-link" href="#about" onclick="showTab('about')">About</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#settings" onclick="showTab('settings')">Settings</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#notifications" onclick="showTab('notifications')">Notifications</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <!-- Content is loaded from the URL hash and inserted via innerHTML -->
                            <div id="tab-content">
                                <p class="text-muted">Select a tab above to view content.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                Please <a href="login.php">login</a> to view your profile.
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php require_once 'include/footer.php'; ?>

    <script>
        // Tab content data
        var tabData = {
            'about': '<h5>About Me</h5><p><?php echo $user ? addslashes($user["bio"]) : ""; ?></p><p>This user enjoys writing blog posts and sharing knowledge with the community.</p>',
            'settings': '<h5>Settings</h5><p>Account settings would appear here.</p><ul><li>Change Password</li><li>Email Notifications</li><li>Privacy Settings</li></ul>',
            'notifications': '<h5>Notifications</h5><p>You have no new notifications.</p>'
        };

        function showTab(tabName) {
            var contentDiv = document.getElementById('tab-content');
            
            if (tabData[tabName]) {
                contentDiv.innerHTML = tabData[tabName];
            } else {
                contentDiv.innerHTML = '<div class="alert alert-info">Loading content for: ' + tabName + '</div>';
            }

            // Update active tab styling
            var tabs = document.querySelectorAll('#profileTabs .nav-link');
            tabs.forEach(function(tab) {
                tab.classList.remove('active');
                if (tab.getAttribute('href') === '#' + tabName) {
                    tab.classList.add('active');
                }
            });
        }

        window.addEventListener('load', function() {
            var hash = window.location.hash;
            if (hash) {
                var tabName = decodeURIComponent(hash.substring(1));
                showTab(tabName);
            }
        });

        window.addEventListener('hashchange', function() {
            var hash = window.location.hash;
            if (hash) {
                var tabName = decodeURIComponent(hash.substring(1));
                showTab(tabName);
            }
        });
    </script>
</body>
</html>
