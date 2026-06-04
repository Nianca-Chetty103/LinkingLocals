<?php
session_start();
include 'database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'buyer';
    $agreeTerms = isset($_POST['agree_terms']) ? true : false;

    if (!$agreeTerms) {
        $error = 'You must agree to the Terms and Conditions to register.';
    } elseif (!empty($name) && !empty($email) && !empty($password)) {
        // Enforce uniqueness validation constraints
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email addresses must be globally unique.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            
            if ($stmt->execute([$name, $email, $hashedPassword, $role])) {
                $_SESSION['user'] = [
                    'id' => $pdo->lastInsertId(),
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'created_at' => date('Y-m-d')
                ];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Execution constraint error during database registration process.';
            }
        }
    } else {
        $error = 'All fields are non-optional.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Join LinkingLocals</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="login.css"/>
</head>
<body>
  <div class="modal">
    <h2>Join LinkingLocals</h2>
    <p class="sub">Connect with local service providers</p>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="field"><label>Full Name</label><input type="text" name="name" placeholder="Jane Doe" required/></div>
        <div class="field"><label>Email</label><input type="email" name="email" placeholder="you@example.com" required/></div>
        <div class="field"><label>Password</label><input type="password" name="password" placeholder="Create a password" required/></div>
        <div class="field"><label>I want to…</label>
          <select name="role">
              <option value="buyer">Find &amp; book services</option>
              <option value="seller">Offer my services</option>
          </select>
        </div>
        <div class="checkbox-field">
            <input type="checkbox" id="agree_terms" name="agree_terms" required/>
            <label for="agree_terms"><span>I agree to the <a onclick="openTerms()">Terms and Conditions</a></span></label>
        </div>
        <button type="submit" class="btn-primary">Create Account</button>
    </form>
    <p class="toggle-link">Already a member? <a href="login.php">Sign in</a></p>
  </div>

  <!-- Terms and Conditions Modal -->
  <div id="terms-overlay" onclick="if(event.target === this) closeTerms()">
    <div id="terms-modal">
      <button class="terms-close" onclick="closeTerms()">✕</button>
      <h3>Terms and Conditions</h3>
      
      <p><strong>Last Updated: June 1, 2026</strong></p>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">1. Agreement to Terms</h4>
      <p>By creating an account and using LinkingLocals, you agree to comply with and be bound by these Terms and Conditions. If you do not agree to abide by the above, please do not use this service.</p>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">2. Use License</h4>
      <p>Permission is granted to temporarily download one copy of the materials (information or software) on LinkingLocals for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
      <ul>
        <li>Modifying or copying the materials</li>
        <li>Using the materials for any commercial purpose or for any public display</li>
        <li>Attempting to decompile or reverse engineer any software contained on LinkingLocals</li>
        <li>Removing any copyright or other proprietary notations from the materials</li>
        <li>Transferring the materials to another person or "mirroring" the materials on any other server</li>
      </ul>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">3. Disclaimer</h4>
      <p>The materials on LinkingLocals are provided on an 'as is' basis. LinkingLocals makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">4. Limitations</h4>
      <p>In no event shall LinkingLocals or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on LinkingLocals, even if LinkingLocals or an authorized representative has been notified orally or in writing of the possibility of such damage.</p>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">5. Accuracy of Materials</h4>
      <p>The materials appearing on LinkingLocals could include technical, typographical, or photographic errors. LinkingLocals does not warrant that any of the materials on our website are accurate, complete, or current. LinkingLocals may make changes to the materials contained on its website at any time without notice.</p>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">6. Links</h4>
      <p>LinkingLocals has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by LinkingLocals of the site. Use of any such linked website is at the user's own risk.</p>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">7. Modifications</h4>
      <p>LinkingLocals may revise these terms of service for its website at any time without notice. By using this website, you are agreeing to be bound by the then current version of these terms of service.</p>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">8. Governing Law</h4>
      <p>These terms and conditions are governed by and construed in accordance with the laws of South Africa, and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">9. User Conduct</h4>
      <p>Users agree to use LinkingLocals only for lawful purposes and in a way that does not infringe upon the rights of others or restrict their use and enjoyment of the website. Prohibited behavior includes:</p>
      <ul>
        <li>Harassing or causing distress or inconvenience to any person</li>
        <li>Obscene or offensive language or content</li>
        <li>Disrupting normal flow of dialogue within our website</li>
        <li>Any form of fraud or dishonesty</li>
      </ul>
      
      <h4 style="margin-top:20px;margin-bottom:10px;font-weight:600">10. Dispute Resolution</h4>
      <p>Any disputes arising from this agreement shall be resolved through negotiation and good faith discussion. If resolution cannot be reached, disputes shall be submitted to arbitration in accordance with applicable laws.</p>
      
      <p style="margin-top:20px;color:#7a7268;font-size:12px">By clicking "Create Account", you acknowledge that you have read and agree to be bound by these Terms and Conditions.</p>
    </div>
  </div>

  <script>
    function openTerms() {
      document.getElementById('terms-overlay').classList.add('show');
    }
    function closeTerms() {
      document.getElementById('terms-overlay').classList.remove('show');
    }
  </script>
</body>
</html>