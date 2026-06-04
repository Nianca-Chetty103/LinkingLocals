<?php
session_start();
include 'database.php';

// Add this to the very top of profile.php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Check if user is blocked
$currentUser = $_SESSION['user'];
$stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$stmt->execute([$currentUser['id']]);
$userRecord = $stmt->fetch(PDO::FETCH_ASSOC);

if ($userRecord) {
    $status = isset($userRecord['status']) ? strtolower(trim($userRecord['status'])) : '';
    if ($status === 'blocked') {
        session_destroy();
        header('Location: login.php?blocked=1');
        exit;
    }
}

$activeTab = $_GET['tab'] ?? 'info';

$usersStmt = $pdo->query("SELECT id, name, email, role, created_at FROM users");
$dbUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$postsStmt = $pdo->query("SELECT id, user_id, title, description, price, image, category, location, created_at FROM posts ORDER BY id DESC");
$dbPosts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

$reviewsStmt = $pdo->query("SELECT id, user_id, post_id, rating, review, created_at FROM reviews ORDER BY id DESC");
$dbReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

$bookingsStmt = $pdo->query("SELECT b.id, b.user_id, b.post_id, b.booking_date, b.booking_time, b.address, b.status, b.created_at, p.user_id AS provider_id FROM bookings b JOIN posts p ON b.post_id = p.id ORDER BY b.id DESC");
$dbBookings = $bookingsStmt->fetchAll(PDO::FETCH_ASSOC);

$messagesStmt = $pdo->query("SELECT id, sender_id, receiver_id, post_id, message, is_read, created_at FROM messages ORDER BY id ASC");
$dbMessages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>My Dashboard – LinkingLocals</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght=0,300;0,600;0,800;1,300&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="index.css">
<link rel="stylesheet" href="profile.css">
<script>
    const GLOBAL_AUTH_USER = <?php echo json_encode($currentUser); ?>;
    const INITIAL_DASH_TAB = "<?php echo htmlspecialchars($activeTab); ?>";
    
    window.dbUsers = <?php echo json_encode($dbUsers); ?>;
    window.dbPosts = <?php echo json_encode($dbPosts); ?>;
    window.dbReviews = <?php echo json_encode($dbReviews); ?>;
    window.dbBookings = <?php echo json_encode($dbBookings); ?>;
    window.dbMessages = <?php echo json_encode($dbMessages); ?>;
</script>
</head>
<body>
<div id="toast"></div>

<nav>
  <div class="nav-inner">
    <div class="logo" onclick="window.location.href='index.php'">Linking<span>Locals</span></div>
    <div class="nav-right">
       <div style="position:relative">
          <button class="avatar-btn show" id="nav-avatar" onclick="toggleDropdown()"><span id="avatar-letter">U</span></button>
          <div class="dropdown" id="nav-dropdown">
            <div class="dd-item" onclick="switchDTab('info')">👤 My Profile</div>
            <div class="dd-item" onclick="switchDTab('post')">✏️ Post a Service</div>
            <div class="dd-item" onclick="switchDTab('messages')">💬 Messages</div>
            <div class="dd-item" onclick="switchDTab('bookings')">📅 My Bookings</div>
            <?php 
              $userRole = isset($currentUser['role']) ? strtolower(trim((string)$currentUser['role'])) : '';
              if ($userRole === 'admin' || strpos($userRole, 'admin') !== false): 
            ?>
            <div class="dd-sep"></div>
            <div class="dd-item" onclick="window.location.href='admin.php'">⚙️ Admin Panel</div>
            <?php endif; ?>
            <div class="dd-sep"></div>
            <div class="dd-item danger" onclick="window.location.href='index.php?action=logout'">Sign Out</div>
          </div>
       </div>
    </div>
  </div>
</nav>

<div id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal" id="modal-chat" style="display:none">
    <button class="modal-close" onclick="closeModal()">✕</button>
    <h2 id="chat-with-name">Chat</h2>
    <p class="sub" id="chat-about"></p>
    <div class="chat-body" id="chat-body"></div>
    <div class="chat-input-row">
      <input type="text" id="chat-input" placeholder="Type a message…" onkeydown="if(event.key==='Enter')sendChat()"/>
      <button class="chat-send" onclick="sendChat()">Send</button>
    </div>
  </div>
</div>

<div id="dashboard-page" class="show">
  <button class="back-btn" onclick="window.location.href='index.php'">← Back to listings</button>
  
  <header class="profile-header">
    <div class="profile-avatar" id="dp-avatar">U</div>
    <div class="profile-info">
      <h2 id="dp-name">My Account</h2>
      <p id="dp-email" style="color:var(--muted);font-size:14px"></p>
      <p id="dp-role" style="color:var(--accent);font-size:13px;font-weight:600;margin-top:4px"></p>
    </div>
  </header>
  
  <nav class="profile-tabs">
    <button class="ptab" id="dtab-info" onclick="switchDTab('info')">My Info</button>
    <button class="ptab" id="dtab-post" onclick="switchDTab('post')">Post Service</button>
    <button class="ptab" id="dtab-bookings" onclick="switchDTab('bookings')">Bookings</button>
    <button class="ptab" id="dtab-messages" onclick="switchDTab('messages')">Messages</button>
    <?php 
      $userRole = isset($currentUser['role']) ? strtolower(trim((string)$currentUser['role'])) : '';
      if ($userRole === 'admin' || strpos($userRole, 'admin') !== false): 
    ?>
    <button class="ptab admin-tab" onclick="window.location.href='admin.php'">⚙️ Admin Panel</button>
    <?php endif; ?>
  </nav>

  <main id="dtab-info-content">
    <div class="dash-grid">
      <div class="dash-card">
        <h3>Personal Details</h3>
        <div class="info-row">
          <label>Full Name</label>
          <span id="edit-name-display"></span>
          <input type="text" id="edit-name-input" style="display:none; width: 220px; padding: 6px 10px; border: 1.5px solid var(--border); border-radius: 8px; font-family: 'DM Sans'; font-size: 14px;"/>
        </div>
        <div class="info-row">
          <label>Email</label>
          <span id="edit-email-display"></span>
          <input type="email" id="edit-email-input" style="display:none; width: 220px; padding: 6px 10px; border: 1.5px solid var(--border); border-radius: 8px; font-family: 'DM Sans'; font-size: 14px;"/>
        </div>
        <div class="info-row"><label>Role</label><span id="edit-role-display"></span></div>
        <div class="info-row"><label>Member Since</label><span id="edit-joined"></span></div>
        
        <div class="edit-btns" id="edit-btns-view"><button class="btn-edit save" onclick="startEdit()">Edit Profile</button></div>
        <div class="edit-btns" id="edit-btns-edit" style="display:none">
          <button class="btn-edit save" onclick="saveEdit()">Save Changes</button>
          <button class="btn-edit cancel" onclick="cancelEdit()">Cancel</button>
        </div>
      </div>
      
      <div class="dash-card">
        <h3>Account Stats</h3>
        <div class="info-row"><label>Active Services</label><span id="stat-services">0</span></div>
        <div class="info-row"><label>Total Bookings</label><span id="stat-bookings">0</span></div>
        <div class="info-row"><label>Messages</label><span id="stat-messages">0</span></div>
        <div class="info-row"><label>Reviews Received</label><span id="stat-reviews">0</span></div>
      </div>
    </div>
  </main>

  <main id="dtab-post-content" style="display:none">
    <div class="post-form">
      <h3>Post a New Service</h3>
      <form id="service-upload-form" onsubmit="event.preventDefault();" enctype="multipart/form-data">
        <div class="form-grid">
          <div class="field">
            <label>Service Title</label>
            <input type="text" id="ps-title" name="title" placeholder="e.g. Professional House Cleaning" required/>
          </div>
          <div class="field">
            <label>Category</label>
            <select id="ps-cat" name="category">
              <option>Cleaning</option>
              <option>Plumbing</option>
              <option>Electrical</option>
              <option>Tutoring</option>
              <option>Garden</option>
              <option>Beauty</option>
              <option>Carpentry</option>
              <option>Painting</option>
              <option>Other</option>
            </select>
          </div>
          <div class="field">
            <label>Price per hour (ZAR)</label>
            <input type="number" id="ps-price" name="price" placeholder="350" required/>
          </div>
          <div class="field">
            <label>Location</label>
            <input type="text" id="ps-location" name="location" placeholder="e.g. Johannesburg, Sandton" required/>
          </div>
        </div>
        
        <div class="field" style="margin-top:12px">
          <label>Description</label>
          <textarea id="ps-desc" name="description" placeholder="Describe your service in detail…" style="min-height:110px" required></textarea>
        </div>
        
        <div class="field" style="margin-top:12px">
          <label>Service Showcase Image Visual Cover</label>
          <input type="file" id="ps-image" name="service_image" accept="image/*" style="width:100%; border:1.5px dashed var(--border); border-radius:10px; padding:10px 14px; background:var(--warm); cursor:pointer;" />
        </div>
        
        <button type="button" class="btn-primary" style="max-width:200px;margin-top:16px" onclick="submitPost()">Publish Service</button>
      </form>
    </div>
    
    <h3 style="font-family:'Fraunces',serif; margin-top:40px; margin-bottom:16px;">Your Active Services</h3>
    <div class="posts-grid" id="my-posts-grid"></div>
  </main>

  <main id="dtab-bookings-content" style="display:none">
    <div class="bookings-list" id="bookings-list"></div>
  </main>

  <main id="dtab-messages-content" style="display:none">
    <div class="messages-list" id="messages-list"></div>
  </main>
</div>

<div id="hours-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); justify-content:center; align-items:center;">
  <div style="background:#fff; padding:20px; border-radius:12px; width:300px;">
    <h3>Enter Hours Worked</h3>

    <input id="hours-input" type="number" step="0.1" placeholder="e.g. 2.5"
      style="width:100%; padding:10px; margin-top:10px; border:1px solid #ccc; border-radius:8px;" />

    <button onclick="confirmHours()" style="margin-top:15px; width:100%; padding:10px; background:#e8622a; color:#fff; border:none;">
      Confirm
    </button>

    <button onclick="closeHoursModal()" style="margin-top:10px; width:100%; padding:10px;">
      Cancel
    </button>
  </div>
</div>

<script src="profile.js"></script>
<script>
function toggleDropdown() { 
  const d = document.getElementById('nav-dropdown');
  if(d) d.classList.toggle('open'); 
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('.nav-right')) {
    const d = document.getElementById('nav-dropdown');
    if(d) d.classList.remove('open');
  }
});
</script>
</body>
</html>