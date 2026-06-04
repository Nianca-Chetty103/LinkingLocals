<?php
session_start();
include 'database.php';

$error = '';

// Check if redirected for being blocked
if (isset($_GET['blocked']) && $_GET['blocked'] === '1') {
    $error = 'Your account has been blocked by the administrator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            
            // --- Check if user is blocked ---
            $status = isset($user['status']) ? strtolower(trim($user['status'])) : '';
            if ($status === 'blocked') {
                $error = 'Your account has been blocked by the administrator.';
            } else {
                session_regenerate_id(true);
                
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ];
                $_SESSION['role'] = strtolower(trim($user['role']));

                session_write_close();

                if ($_SESSION['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please fill out all credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sign In – LinkingLocals</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="login.css"/>
</head>
<body>
  <div class="modal">
    <h2>Welcome back</h2>
    <p class="sub">Sign in to your LinkingLocals account</p>
    
    <?php if(!empty($error)): ?>
        <div class="error-msg" style="color: red; font-size: 14px; margin-bottom: 15px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST"> 
      <div class="field">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required placeholder="name@example.com"/>
      </div>
      
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="••••••••"/>
      </div>
      
      <button type="submit" class="btn-primary">Sign In</button>
    </form>
    
    <div class="toggle-link" style="margin-top: 20px; font-size: 14px;">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
  </div>
</body>
</html>