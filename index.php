<?php
session_start();
include 'database.php';

// Check if user is logged in
$currentUser = $_SESSION['user'] ?? null;

// Handle sign out action if triggered
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: home.php');
    exit;
}

// If user is logged in, verify they're not blocked
if ($currentUser) {
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
}

// Fetch all service listings along with owner details from the database
try {
    $postsStmt = $pdo->query("SELECT p.*, u.name as provider_name, u.email as provider_email FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.id DESC");
    $allPosts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allPosts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>LinkingLocals – South African C2C Marketplace</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="index.css">
<style>
  /* Calendar Slot Grid Engine Configuration */
  .slots-grid {
    display: grid; 
    grid-template-columns: repeat(4, 1fr); 
    gap: 8px; 
    margin-top: 10px;
  }
  .slot-btn {
    padding: 10px; 
    border-radius: 8px; 
    font-family: 'DM Sans', sans-serif; 
    font-size: 13px; 
    font-weight: 500; 
    cursor: pointer; 
    border: 1.5px solid var(--border); 
    background: #fff; 
    color: var(--ink);
    transition: all 0.2s ease;
  }
  .slot-btn:hover:not(:disabled) {
    border-color: var(--accent);
    color: var(--accent);
  }
  .slot-btn:disabled {
    background: #f3f4f6;
    color: #9ca3af;
    border-color: #e5e7eb;
    cursor: not-allowed;
  }

  /* Separate Modal Box Framework Overlays */
  .modal-overlay {
    position: fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(11, 9, 20, 0.6); backdrop-filter: blur(5px);
    display: none; align-items: center; justify-content: center; z-index: 1000;
    padding: 20px;
  }
  .custom-modal {
    background: var(--card); width: 100%; max-width: 500px; padding: 32px;
    border-radius: 20px; box-shadow: var(--shadow-lg);
    position: relative; max-height: 90vh; overflow-y: auto;
    animation: popIn 0.25s ease;
  }
  @keyframes popIn { 
    from { transform: scale(0.95); opacity: 0; } 
    to { transform: scale(1); opacity: 1; } 
  }
  .close-modal {
    position: absolute; top: 20px; right: 20px; background: none;
    border: none; font-size: 24px; cursor: pointer; color: var(--muted);
    line-height: 1;
  }

  /* Live Messaging Chat Window UI Elements */
  .chat-messages-window {
    height: 240px;
    overflow-y: auto;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: var(--cream);
    border: 1.5px solid var(--border);
    border-radius: 12px;
    margin-bottom: 12px;
  }
  .chat-msg-row {
    max-width: 80%;
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.5;
  }
  .chat-msg-row.incoming {
    background: var(--warm);
    color: var(--ink);
    align-self: flex-start;
    border-bottom-left-radius: 4px;
  }
  .chat-msg-row.outgoing {
    background: var(--accent);
    color: #fff;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
  }
  .chat-input-row {
    display: flex;
    gap: 8px;
  }
  .chat-input-row input {
    flex: 1;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 12px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    outline: none;
    background: var(--cream);
  }
  .chat-input-row input:focus {
    border-color: var(--accent);
  }
  .chat-send-btn {
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 0 20px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
  }
</style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <div class="logo" onclick="window.location.href='index.php'">Linking<span>Locals</span></div>
    <div class="nav-right" style="display: flex; align-items: center; gap: 12px;">
      <?php if ($currentUser): ?>
        <button class="btn-nav-accent" onclick="window.location.href='profile.php'">My Dashboard</button>
        <a href="index.php?action=logout" style="color:var(--ink); font-size:14px; font-weight:600; text-decoration:none; opacity:0.8;">Sign Out</a>
      <?php else: ?>
        <a href="login.php" style="color: var(--ink); text-decoration: none; font-size: 14px; font-weight: 600; padding: 0 8px;">Sign In</a>
        <button class="btn-nav-accent" onclick="window.location.href='register.php'">Create Free Account</button>
      <?php endif; ?>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-content">
      <h1>Support Local.<br><span>Grow Communities.</span></h1>
      <p>Connect directly with professional South African providers, local small farmers, and skilled trades-locals near you.</p>
      
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="marketplaceSearchInput" onkeyup="filterMarketplaceGridListings()" placeholder="What skill or service are you looking for today?..." />
      </div>
      
      <div class="hero-cats">
        <button class="category-pill active" onclick="filterBubbleCategory('all', this)">✨ All Items</button>
        <button class="category-pill" onclick="filterBubbleCategory('cleaning', this)">🧹 Cleaning</button>
        <button class="category-pill" onclick="filterBubbleCategory('plumbing', this)">🔧 Plumbing</button>
        <button class="category-pill" onclick="filterBubbleCategory('tutoring', this)">📚 Tutoring</button>
        <button class="category-pill" onclick="filterBubbleCategory('garden', this)">🌱 Garden</button>
      </div>
    </div>
    
    <div class="hero-image-pane">
      <div class="hero-graphic-bg"></div>
      <div class="floating-emoji cleaning">🧹</div>
      <div class="floating-emoji cleaning">💅</div>
      <div class="floating-emoji plumbing">🛠️</div>
      <div class="floating-emoji tutoring">📖</div>
      <div class="floating-emoji garden">🥬</div>
    </div>
  </div>
</section>

<main class="section">
  <div class="section-head">
    <h2>Available Marketplace Listings</h2>
  </div>
  
  <div class="posts-grid" id="servicesGridContainer">
    <?php if (empty($allPosts)): ?>
      <p style="color:var(--muted); grid-column:1/-1;">No active listings found in your area right now.</p>
    <?php else: ?>
      <?php foreach ($allPosts as $post): ?>
        <div class="post-card service-searchable-item" data-title="<?php echo htmlspecialchars(strtolower($post['title'])); ?>" data-category="<?php echo htmlspecialchars(strtolower($post['category'])); ?>" data-desc="<?php echo htmlspecialchars(strtolower($post['description'])); ?>">
          
          <div class="post-img">
            <span class="cat-badge"><?php echo htmlspecialchars($post['category']); ?></span>
            <?php if (!empty($post['image'])): ?>
              <img src="<?php echo htmlspecialchars($post['image']); ?>" style="width:100%; height:100%; object-fit:cover;">
            <?php else: ?>
              <?php 
                $catLower = strtolower($post['category']);
                if (strpos($catLower, 'clean') !== false) {
                    echo '<div style="width:100%; height:100%; background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); display:flex; align-items:center; justify-content:center; color:#0284c7; font-size:42px;">🧹</div>';
                } elseif (strpos($catLower, 'plumb') !== false) {
                    echo '<div style="width:100%; height:100%; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); display:flex; align-items:center; justify-content:center; color:#d97706; font-size:42px;">🔧</div>';
                } elseif (strpos($catLower, 'tutor') !== false || strpos($catLower, 'class') !== false || strpos($catLower, 'book') !== false) {
                    echo '<div style="width:100%; height:100%; background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); display:flex; align-items:center; justify-content:center; color:#9333ea; font-size:42px;">📚</div>';
                } elseif (strpos($catLower, 'garden') !== false || strpos($catLower, 'farm') !== false) {
                    echo '<div style="width:100%; height:100%; background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); display:flex; align-items:center; justify-content:center; color:#16a34a; font-size:42px;">🌱</div>';
                } else {
                    // Default custom dynamic fallback gradient
                    echo '<div style="width:100%; height:100%; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); display:flex; align-items:center; justify-content:center; color:#4f46e5; font-size:42px;">⚡</div>';
                }
              ?>
            <?php endif; ?>
          </div>
          
          <div class="post-body">
            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>
            
            <div class="post-meta">
              <span class="post-loc">📍 <?php echo htmlspecialchars($post['location']); ?></span>
              <span class="post-price">R <?php echo htmlspecialchars($post['price']); ?>/hr</span>
            </div>
            
            <p style="color:var(--muted); font-size:13px; line-height:1.5; height:58px; overflow:hidden; text-overflow:ellipsis; margin-bottom:12px;">
              <?php echo htmlspecialchars($post['description']); ?>
            </p>
            
            <div class="post-footer">
              <div class="post-author">
                <div class="pa-avatar"><?php echo strtoupper(substr($post['provider_name'], 0, 1)); ?></div>
                <div class="pa-name"><?php echo htmlspecialchars($post['provider_name']); ?></div>
              </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:6px; margin-top:14px;">
              <div style="display:flex; gap:6px;">
                <button class="btn-sm outline" style="flex:1;" onclick='openDetailsModal(<?php echo json_encode($post); ?>)'>View Details</button>
                
                <?php if ($currentUser): ?>
                  <button class="btn-sm fill" style="flex:1;" onclick='openChatModal(<?php echo json_encode($post); ?>)'>Chat Now</button>
                <?php else: ?>
                  <button class="btn-sm fill" style="flex:1; background:var(--muted);" onclick="window.location.href='login.php'">Chat Now</button>
                <?php endif; ?>
              </div>
              
              <?php if ($currentUser): ?>
                <button class="btn-primary" style="padding:10px; font-size:14px;" onclick="openBookingModal(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars(addslashes($post['title'])); ?>')">Book Appointment</button>
              <?php else: ?>
                <button class="btn-primary" style="padding:10px; font-size:14px; background:var(--muted); box-shadow:none;" onclick="window.location.href='login.php'">Sign In to Book</button>
              <?php endif; ?>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
      <p style="color:var(--muted); grid-column: 1/-1; display:none;" id="searchNoResultsFoundMessage">No services match your active search terms criteria.</p>
    <?php endif; ?>
  </div>
</main>

<footer>
  <div class="footer-inner">
     <div class="footer-bottom">
        <div>© 2026 LinkingLocals. Supporting local South African small businesses.</div>
     </div>
  </div>
</footer>


<div class="modal-overlay" id="detailsModalOverlay">
  <div class="custom-modal">
    <button class="close-modal" onclick="closeDetailsModal()">✕</button>
    <span id="detCategory" style="font-size:11px; font-weight:700; color:var(--accent); text-transform:uppercase; letter-spacing:0.5px;">CATEGORY</span>
    <h3 id="detTitle" style="font-family:'Fraunces', serif; font-size:26px; margin-top:4px; margin-bottom:12px;">Service Title</h3>
    
    <div style="display:flex; justify-content:space-between; margin-bottom:18px; font-size:15px; font-weight:700; background:var(--cream); padding:14px; border-radius:10px; border:1px solid var(--border);">
      <span id="detPrice" style="color:var(--accent);">Price: --</span>
      <span id="detLocation">📍 Location: --</span>
    </div>
    
    <h4 style="font-size:14px; font-weight:700; margin-bottom:6px;">Service Description</h4>
    <p id="detDescription" style="color:var(--muted); font-size:14.5px; line-height:1.6; margin-bottom:10px;">Full description rows go here...</p>
  </div>
</div>

<div class="modal-overlay" id="chatModalOverlay">
  <div class="custom-modal">
    <button class="close-modal" onclick="closeChatModal()">✕</button>
    <h3 style="font-family:'Fraunces', serif; font-size:22px; margin-bottom:2px;" id="chatModalHeaderTitle">Chat Window</h3>
    <p style="color:var(--muted); font-size:13px; margin-bottom:16px;" id="chatModalHeaderSub">Ask queries regarding availability directly.</p>
    
    <div class="chat-messages-window" id="chatMessagesBox">
       </div>
    
    <div class="chat-input-row">
      <input type="text" id="chatModalInputField" placeholder="Type a message to the provider..." onkeydown="if(event.key==='Enter')sendChatModalMessage()"/>
      <button type="button" class="chat-send-btn" onclick="sendChatModalMessage()">Send</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="bookingModalOverlay">
  <div class="custom-modal">
    <button class="close-modal" onclick="closeBookingModal()">✕</button>
    <h3 style="font-family:'Fraunces', serif; font-size:24px; margin-bottom:4px;" id="modalServiceTitle">Book Service</h3>
    <p style="color:var(--muted); font-size:14px; margin-bottom:20px;">Select a date to check real-time schedule openings.</p>
    
    <form id="marketplaceBookingForm" action="process_booking.php" method="POST">
      <input type="hidden" id="modalPostId" name="post_id" />

      <div class="field">
        <label>Choose Date</label>
        <input type="date" id="bookingDatePicker" name="booking_date" onchange="checkTimeAvailability()" required />
      </div>

      <div class="field">
        <label>Available Hourly Openings</label>
        <div class="slots-grid" id="slotsContainer">
          <p style="color:var(--muted); font-size:13px; grid-column:1/-1;">Please select a date from the calendar first.</p>
        </div>
        <input type="hidden" id="selectedBookingTime" name="booking_time" required />
      </div>

      <div class="field">
        <label>Service Location Address</label>
        <input type="text" name="address" placeholder="e.g. 142 Cascades Road, Little Falls" required />
      </div>

      <button type="submit" class="btn-primary" style="margin-top:10px;">Confirm Booking Request</button>
    </form>
  </div>
</div>


<script>
var serverBookedSchedule = {}; 
var trackingActiveChatPost = null;
var chatModalRefreshLoopTimer = null;
const BUSINESS_HOURS = ["08:00", "09:00", "10:00", "11:00", "12:00", "13:00", "14:00", "15:00", "16:00", "17:00"];
const CLIENT_LOGGED_IN_USER_ID = <?php echo json_encode($currentUser['id'] ?? null); ?>;

/* ==================== SEARCH BAR ENGINE ==================== */
function filterMarketplaceGridListings() {
  const query = document.getElementById('marketplaceSearchInput').value.toLowerCase().trim();
  const cards = document.querySelectorAll('.service-searchable-item');
  let matchCount = 0;

  cards.forEach(card => {
    const title = card.getAttribute('data-title');
    const category = card.getAttribute('data-category');
    const desc = card.getAttribute('data-desc');

    if (title.includes(query) || category.includes(query) || desc.includes(query)) {
      card.style.display = 'block';
      matchCount++;
    } else {
      card.style.display = 'none';
    }
  });

  const fallback = document.getElementById('searchNoResultsFoundMessage');
  fallback.style.display = (matchCount === 0 && cards.length > 0) ? 'block' : 'none';
}

/* ==================== CATEGORY BUBBLE SELECTION COUPLER ==================== */
function filterBubbleCategory(catValue, element) {
  // Toggle selection classes actively across peers
  document.querySelectorAll('.category-pill').forEach(pill => pill.classList.remove('active'));
  element.classList.add('active');

  const cards = document.querySelectorAll('.service-searchable-item');
  let matchCount = 0;

  cards.forEach(card => {
    const itemCat = card.getAttribute('data-category');
    if (catValue === 'all' || itemCat === catValue.toLowerCase().trim()) {
      card.style.display = 'block';
      matchCount++;
    } else {
      card.style.display = 'none';
    }
  });

  const fallback = document.getElementById('searchNoResultsFoundMessage');
  fallback.style.display = (matchCount === 0 && cards.length > 0) ? 'block' : 'none';
}

/* ==================== SEPARATE FEATURE 1: VIEW DETAILS ==================== */
function openDetailsModal(postObject) {
  document.getElementById('detCategory').textContent = postObject.category;
  document.getElementById('detTitle').textContent = postObject.title;
  document.getElementById('detPrice').textContent = `Price: R ${postObject.price}/hr`;
  document.getElementById('detLocation').textContent = `📍 Location: ${postObject.location}`;
  document.getElementById('detDescription').textContent = postObject.description;
  
  document.getElementById('detailsModalOverlay').style.display = 'flex';
}

function closeDetailsModal() {
  document.getElementById('detailsModalOverlay').style.display = 'none';
}

/* ==================== SEPARATE FEATURE 2: DISCRETE CHAT INBOX ==================== */
function openChatModal(postObject) {
  trackingActiveChatPost = postObject;
  document.getElementById('chatModalHeaderTitle').textContent = `Chat with ${postObject.provider_name}`;
  document.getElementById('chatModalHeaderSub').textContent = `Regarding: "${postObject.title}"`;
  
  document.getElementById('chatModalOverlay').style.display = 'flex';
  
  fetchChatModalLogsFromServer();
  chatModalRefreshLoopTimer = setInterval(fetchChatModalLogsFromServer, 3000);
}

function closeChatModal() {
  document.getElementById('chatModalOverlay').style.display = 'none';
  clearInterval(chatModalRefreshLoopTimer);
  trackingActiveChatPost = null;
}

function fetchChatModalLogsFromServer() {
  if (!trackingActiveChatPost || !CLIENT_LOGGED_IN_USER_ID) return;

  fetch(`fetch_messages_api.php?post_id=${trackingActiveChatPost.id}&provider_id=${trackingActiveChatPost.user_id}`)
    .then(res => res.json())
    .then(data => {
       if (data.success) {
          const chatWindow = document.getElementById('chatMessagesBox');
          let savedScrollHeight = chatWindow.scrollHeight;
          
          chatWindow.innerHTML = '';
          if (data.messages.length === 0) {
             chatWindow.innerHTML = `<p style="color:var(--muted); font-size:13px; text-align:center; margin:auto;">No message records found. Send an inquiry to start!</p>`;
             return;
          }
          
          data.messages.forEach(msg => {
             const div = document.createElement('div');
             const isOutgoing = (parseInt(msg.sender_id) === parseInt(CLIENT_LOGGED_IN_USER_ID));
             div.className = `chat-msg-row ${isOutgoing ? 'outgoing' : 'incoming'}`;
             div.textContent = msg.message;
             chatWindow.appendChild(div);
          });
          
          if (chatWindow.scrollHeight > savedScrollHeight) {
             chatWindow.scrollTop = chatWindow.scrollHeight;
          }
       }
    });
}

function sendChatModalMessage() {
  const input = document.getElementById('chatModalInputField');
  const txt = input.value.trim();
  if (!txt || !trackingActiveChatPost) return;

  const formData = new FormData();
  formData.append('receiver_id', trackingActiveChatPost.user_id);
  formData.append('post_id', trackingActiveChatPost.id);
  formData.append('message', txt);

  fetch('send_message_api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
       if (data.success) {
          input.value = '';
          fetchChatModalLogsFromServer();
       }
    });
}

/* ==================== SEPARATE FEATURE 3: CALENDAR AVAILABILITY CHECKER ==================== */
function openBookingModal(postId, postTitle) {
  document.getElementById('modalPostId').value = postId;
  document.getElementById('modalServiceTitle').textContent = `Book: ${postTitle}`;
  
  const datePicker = document.getElementById('bookingDatePicker');
  datePicker.min = new Date().toISOString().split('T')[0];
  datePicker.value = ''; 
  
  document.getElementById('slotsContainer').innerHTML = '<p style="color:var(--muted); font-size:13px; grid-column:1/-1;">Please select a date from the calendar first.</p>';
  document.getElementById('selectedBookingTime').value = '';

  fetch(`get_booked_slots.php?post_id=${postId}`)
    .then(res => res.json())
    .then(data => {
      if (data.success) { serverBookedSchedule = data.schedule; } 
      else { serverBookedSchedule = {}; }
      document.getElementById('bookingModalOverlay').style.display = 'flex';
    })
    .catch(() => {
      serverBookedSchedule = {};
      document.getElementById('bookingModalOverlay').style.display = 'flex';
    });
}

function closeBookingModal() {
  document.getElementById('bookingModalOverlay').style.display = 'none';
  document.getElementById('marketplaceBookingForm').reset();
}

function checkTimeAvailability() {
  const selectedDate = document.getElementById('bookingDatePicker').value;
  const container = document.getElementById('slotsContainer');
  if (!selectedDate) return;

  container.innerHTML = ''; 
  document.getElementById('selectedBookingTime').value = ''; 

  const takenTimes = serverBookedSchedule[selectedDate] || [];
  const isFullyBooked = BUSINESS_HOURS.every(time => takenTimes.includes(time));

  if (isFullyBooked) {
    container.innerHTML = `
      <div style="grid-column: 1/-1; background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 500; text-align: center;">
        🔒 Fully Booked. Please try choosing a different calendar day block.
      </div>`;
    return;
  }

  BUSINESS_HOURS.forEach(time => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'slot-btn';
    btn.textContent = time;

    if (takenTimes.includes(time)) {
      btn.disabled = true;
      btn.textContent = time + " ❌";
    } else {
      btn.onclick = function() {
        Array.from(container.children).forEach(b => {
          if (!b.disabled) {
            b.style.background = "#fff";
            b.style.color = "var(--ink)";
            b.style.borderColor = "var(--border)";
          }
        });
        btn.style.background = "var(--accent)";
        btn.style.color = "#fff";
        btn.style.borderColor = "var(--accent)";
        document.getElementById('selectedBookingTime').value = time;
      };
    }
    container.appendChild(btn);
  });
}
</script>
</body>
</html>