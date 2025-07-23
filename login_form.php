<?php if (!empty($message)): ?>
    <!-- Success message display -->
    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <!-- Error message display -->
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Login form container -->
<div class="form-container">
    <h2>Login to Your Account</h2>
    <form method="post">
        <!-- Email input field -->
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        
        <!-- Password input field -->
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <!-- Submit button -->
        <div class="form-group">
            <input type="submit" value="Login" class="btn btn-primary">
        </div>
    </form>
    
    <!-- Link to registration -->
    <p class="form-footer">
        Don't have an account? <a href="auth.php?action=register">Register here</a>
    </p>
</div>

<style>
/* Form styling */
.form-container { max-width: 400px; margin: 2rem auto; padding: 2rem; border: 1px solid #ddd; border-radius: 8px; background: white; }
.form-container h2 { text-align: center; margin-bottom: 1.5rem; color: #333; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: #555; }
.form-group input[type="email"], .form-group input[type="password"] { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
.form-group input:focus { border-color: #007cba; outline: none; box-shadow: 0 0 5px rgba(0, 124, 186, 0.3); }
.btn { padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; text-align: center; width: 100%; }
.btn-primary { background: #007cba; color: white; }
.btn-primary:hover { background: #005a87; }
.form-footer { text-align: center; margin-top: 1rem; color: #666; }
.form-footer a { color: #007cba; text-decoration: none; }
.form-footer a:hover { text-decoration: underline; }
.message { padding: 0.75rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
.message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>