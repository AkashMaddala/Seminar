<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        /* Global styles for consistent layout */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; }
        
        /* Header and navigation */
        header { background: #333; color: white; padding: 1rem 0; }
        nav ul { list-style: none; display: flex; justify-content: center; }
        nav li { margin: 0 1rem; }
        nav a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; }
        nav a:hover { background: #555; }
        
        /* Main content area */
        main { min-height: calc(100vh - 120px); padding: 2rem; }
        
        /* Footer */
        footer { background: #333; color: white; text-align: center; padding: 1rem; }
    </style>
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="seminars.php">Seminars</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Logged in user navigation -->
                    <li><a href="actions.php?action=logout">Logout</a></li>
                <?php else: ?>
                    <!-- Guest user navigation -->
                    <li><a href="auth.php?action=login">Login</a></li>
                    <li><a href="auth.php?action=register">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    
    <main>
        <!-- Include page-specific content -->
        <?php include $content; ?>
    </main>
    
    <footer>
        <p>&copy; 2025 Seminar Booking System</p>
    </footer>
</body>
</html>