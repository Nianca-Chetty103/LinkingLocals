var currentUser = GLOBAL_AUTH_USER;
var EMOJI = {'Cleaning':'🧹','Plumbing':'🔧','Electrical':'⚡','Tutoring':'📚','Garden':'🌿','Beauty':'💅','Carpentry':'🪚','Painting':'🎨','Other':'🛠️'};
var COLORS = ['#e8622a','#2a7ae8','#c9973a','#7a2ae8','#2ae8a0','#e82a7a'];
var activeChatPostId = null; var activeChatReceiverId = null;
var editingPostId = null; // Track if we are currently editing an existing post

var users = window.dbUsers || [];
var posts = window.dbPosts || [];
var reviews = window.dbReviews || [];
var bookings = window.dbBookings || [];
var messages_db = window.dbMessages || [];

function ini(str) { return str ? str.trim().charAt(0).toUpperCase() : '?'; }

document.addEventListener('DOMContentLoaded', () => {
  if(!currentUser) return;
  initDashboard();
  if(typeof INITIAL_DASH_TAB !== 'undefined' && ['info','post','bookings','messages'].includes(INITIAL_DASH_TAB)) {
    switchDTab(INITIAL_DASH_TAB);
  } else {
    switchDTab('info');
  }
});

function initDashboard() {
  document.getElementById('avatar-letter').textContent = ini(currentUser.name);
  document.getElementById('dp-avatar').textContent = ini(currentUser.name);
  document.getElementById('dp-name').textContent = currentUser.name;
  document.getElementById('dp-email').textContent = currentUser.email;
  document.getElementById('dp-role').textContent = currentUser.role.toUpperCase();
  
  document.getElementById('edit-name-display').textContent = currentUser.name;
  document.getElementById('edit-email-display').textContent = currentUser.email;
  document.getElementById('edit-role-display').textContent = currentUser.role;
  document.getElementById('edit-joined').textContent = currentUser.created_at || 'Recent';

  const activeSvc = posts.filter(p => Number(p.user_id) === Number(currentUser.id)).length;
  const activeBkg = bookings.filter(b => Number(b.user_id) === Number(currentUser.id)).length;
  const myMsgCount = messages_db.filter(m => Number(m.sender_id) === Number(currentUser.id) || Number(m.receiver_id) === Number(currentUser.id)).length;
  
  const myPostIds = posts.filter(p => Number(p.user_id) === Number(currentUser.id)).map(p => Number(p.id));
  const myReviewCount = reviews.filter(r => myPostIds.includes(Number(r.post_id))).length;
  
  document.getElementById('stat-services').textContent = activeSvc;
  document.getElementById('stat-bookings').textContent = activeBkg;
  document.getElementById('stat-messages').textContent = myMsgCount;
  document.getElementById('stat-reviews').textContent = myReviewCount;
  
  renderMyServices();
  renderBookings();
  renderMessages();
}

function switchDTab(key) {
  ['info','post','bookings','messages'].forEach(k => {
    const el = document.getElementById(`dtab-${k}-content`);
    const btn = document.getElementById(`dtab-${k}`);
    if(el) el.style.display = 'none';
    if(btn) btn.classList.remove('active');
  });
  
  const targetEl = document.getElementById(`dtab-${key}-content`);
  const targetBtn = document.getElementById(`dtab-${key}`);
  if(targetEl) targetEl.style.display = 'block';
  if(targetBtn) targetBtn.classList.add('active');
}

function startEdit() {
  document.getElementById('edit-name-display').style.display = 'none';
  document.getElementById('edit-email-display').style.display = 'none';
  document.getElementById('edit-name-input').style.display = 'inline-block';
  document.getElementById('edit-email-input').style.display = 'inline-block';
  document.getElementById('edit-name-input').value = currentUser.name;
  document.getElementById('edit-email-input').value = currentUser.email;
  document.getElementById('edit-btns-view').style.display = 'none';
  document.getElementById('edit-btns-edit').style.display = 'flex';
}

function cancelEdit() {
  document.getElementById('edit-name-display').style.display = '';
  document.getElementById('edit-email-display').style.display = '';
  document.getElementById('edit-name-input').style.display = 'none';
  document.getElementById('edit-email-input').style.display = 'none';
  document.getElementById('edit-btns-view').style.display = '';
  document.getElementById('edit-btns-edit').style.display = 'none';
}

function saveEdit() {
  const name = document.getElementById('edit-name-input').value.trim();
  const email = document.getElementById('edit-email-input').value.trim();
  if(!name || !email) { toast('Name and email required'); return; }
  
  const fd = new FormData();
  fd.append('name', name);
  fd.append('email', email);
  
  fetch('profile_update.php', { method: 'POST', body: fd })
  .then(() => {
    currentUser.name = name; currentUser.email = email;
    initDashboard();
    cancelEdit();
    toast('Profile updated successfully!');
  }).catch(() => toast('Error updating profile'));
}

// HANDLES BOTH CREATING AND UPDATING POSTS DEPENDING ON EDITING STATE
function submitPost() {
  const title = document.getElementById('ps-title').value.trim();
  const category = document.getElementById('ps-cat').value;
  const price = document.getElementById('ps-price').value.trim();
  const location = document.getElementById('ps-location').value.trim();
  const description = document.getElementById('ps-desc').value.trim();
  const fileInput = document.getElementById('ps-image');

  if(!title || !price || !location || !description) {
    toast('Please fill in all fields.');
    return;
  }

  const formData = new FormData();
  formData.append('title', title);
  formData.append('category', category);
  formData.append('price', price);
  formData.append('location', location);
  formData.append('description', description);
  
  if (fileInput.files.length > 0) {
    formData.append('service_image', fileInput.files[0]);
  }

  // Determine whether to send request to update_post.php or process_post.php
  if (editingPostId !== null) {
    formData.append('post_id', editingPostId);
    
    fetch('update_post.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        toast('🎉 Listing updated successfully!');
        
        // Update local array dataset row dynamically
        const index = posts.findIndex(x => Number(x.id) === Number(editingPostId));
        if(index !== -1) {
           posts[index] = { ...posts[index], ...data.updated_post };
        }
        resetPostFormState();
        renderMyServices(); 
      } else {
        toast(data.error || 'Update failed');
      }
    })
    .catch(() => toast('Unable to save post modifications'));
  } else {
    // STANDARD UPLOAD NEW SERVICE FLOW
    fetch('process_post.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
       if(data.status === 'success') {
         toast('🎉 Service published successfully!');
         document.getElementById('service-upload-form').reset();
         setTimeout(() => { window.location.href = "profile.php?tab=post"; }, 1200);
       } else {
         toast(data.message || 'Submission failed');
       }
    })
    .catch(() => toast('System submission processing failed'));
  }
}


function preparePostEdit(postId) {
  const p = posts.find(x => Number(x.id) === Number(postId));
  if(!p) return;

  editingPostId = p.id;

  document.getElementById('ps-title').value = p.title;
  document.getElementById('ps-cat').value = p.category;
  document.getElementById('ps-price').value = p.price;
  document.getElementById('ps-location').value = p.location;
  document.getElementById('ps-desc').value = p.description;

  const formHeader = document.querySelector('.post-form h3');
  if(formHeader) formHeader.innerHTML = `✏️ Editing: <span style="color:var(--accent)">${p.title}</span>`;
  
  const submitBtn = document.querySelector('#service-upload-form button');
  if(submitBtn) {
    submitBtn.textContent = 'Save Post Changes';
    submitBtn.style.background = 'var(--ink)';
    
    if(!document.getElementById('btn-cancel-post-edit')) {
       const cancelBtn = document.createElement('button');
       cancelBtn.type = 'button';
       cancelBtn.id = 'btn-cancel-post-edit';
       cancelBtn.className = 'btn-edit cancel';
       cancelBtn.style = 'margin-top:16px; margin-left:10px; max-width:120px;';
       cancelBtn.textContent = 'Cancel';
       cancelBtn.onclick = resetPostFormState;
       submitBtn.parentNode.appendChild(cancelBtn);
    }
  }

  document.querySelector('.post-form').scrollIntoView({ behavior: 'smooth' });
}

// RESETS FORM BACK TO STANDARD NEW PUBLISHING MODE
function resetPostFormState() {
  editingPostId = null;
  document.getElementById('service-upload-form').reset();
  
  const formHeader = document.querySelector('.post-form h3');
  if(formHeader) formHeader.textContent = 'Post a New Service';

  const submitBtn = document.querySelector('#service-upload-form button');
  if(submitBtn) {
     submitBtn.textContent = 'Publish Service';
     submitBtn.style.background = '';
  }

  const cancelBtn = document.getElementById('btn-cancel-post-edit');
  if(cancelBtn) cancelBtn.remove();
}

// ASYNCHRONOUS DELETION ACTION
function deletePost(postId) {
  if(!confirm('⚠️ Are you sure you want to completely delete this service listing? This cannot be undone.')) {
     return;
  }

  const formData = new FormData();
  formData.append('post_id', postId);

  fetch('delete_post.php', { method: 'POST', body: formData })
  .then(res => res.json())
  .then(data => {
     if(data.success) {
       toast('🗑️ Service listing successfully deleted.');
       posts = posts.filter(x => Number(x.id) !== Number(postId));
       initDashboard(); // Recalculate stats counters and redraw listings
     } else {
       toast(data.error || 'Failed to remove listing');
     }
  })
  .catch(() => toast('System network interaction error during deletion request'));
}

// RENDER ACTIVE USER SERVICES WITH EDIT/REMOVE BUTTON TRAPS
function renderMyServices() {
  const grid = document.getElementById('my-posts-grid');
  if(!grid) return;
  grid.innerHTML = '';

  const myPosts = posts.filter(p => Number(p.user_id) === Number(currentUser.id));

  if(myPosts.length === 0) {
    grid.innerHTML = `<p style="color:var(--muted);grid-column:1/-1;font-size:14px">You have not published any active listings under this profile.</p>`;
    return;
  }

  myPosts.forEach((p, idx) => {
    const card = document.createElement('div');
    card.className = 'post-card';
    card.id = `post-card-${p.id}`;
    
    const fallbackBgColor = COLORS[idx % COLORS.length] || '#e8622a';
    const categoryEmoji = EMOJI[p.category] || '🛠️';
    
    let mediaHeaderHtml = '';

    if (p.image && p.image.trim() !== '') {
      mediaHeaderHtml = `
        <div style="position:relative;height:140px;background:#e2ddd5;overflow:hidden">
          <img src="${p.image}" style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.innerHTML='<div style=\'height:100%;background:${fallbackBgColor};display:flex;align-items:center;justify-content:center;font-size:48px;\'>${categoryEmoji}</div>'">
          <span style="position:absolute;top:10px;left:10px;background:#fff;padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600">${categoryEmoji} ${p.category}</span>
        </div>
      `;
    } else {
      mediaHeaderHtml = `
        <div style="position:relative;height:140px;background:${fallbackBgColor};display:flex;align-items:center;justify-content:center;font-size:48px;user-select:none;">
          ${categoryEmoji}
          <span style="position:absolute;top:10px;left:10px;background:#fff;padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600;font-family:'DM Sans',sans-serif;">${categoryEmoji} ${p.category}</span>
        </div>
      `;
    }

    card.innerHTML = `
      ${mediaHeaderHtml}
      <div style="padding:16px">
        <h4 style="font-family:'Fraunces',serif;font-size:16px;margin-bottom:6px;color:var(--ink)">${p.title}</h4>
        <p style="color:var(--muted);font-size:13px;height:36px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.4">${p.description}</p>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:1px solid var(--border)">
          <span style="font-weight:600;font-size:14px;color:var(--ink)">R ${p.price}/hr</span>
          <span style="font-size:12px;color:var(--muted)">📍 ${p.location}</span>
        </div>
        
        <div style="display:flex; justify-content:space-between; margin-top: 14px; padding-top: 10px; border-top: 1px dashed var(--border)">
          <button class="btn-edit save" style="padding: 5px 12px; font-size: 12px; background:var(--ink); color:#fff;" onclick="preparePostEdit(${p.id})">✏️ Edit</button>
          <button class="btn-edit cancel" style="padding: 5px 12px; font-size: 12px; background:#c62828;" onclick="deletePost(${p.id})">🗑️ Remove</button>
        </div>
      </div>
    `;
    grid.appendChild(card);
  });
}

// Inside renderBookings() in profile.js
function renderBookings() {
  const list = document.getElementById('bookings-list');
  if (!list) return;
  list.innerHTML = '';

  const providerBookings = bookings.filter(b => Number(b.provider_id) === Number(currentUser.id));
  const clientBookings = bookings.filter(b => Number(b.user_id) === Number(currentUser.id));

  if (providerBookings.length > 0) {
    const pendingRequests = providerBookings.filter(b => b.status === 'pending');
    const acceptedAppointments = providerBookings.filter(b => b.status === 'approved');

    list.innerHTML += `<h4 style="font-family:'Fraunces',serif; margin-bottom: 16px; color: var(--ink);">📥 Incoming Client Requests</h4>`;
    if (pendingRequests.length === 0) {
      list.innerHTML += `<p style="color:var(--muted); font-size:14px; margin-bottom: 30px;">No new appointment requests have arrived yet.</p>`;
    } else {
      pendingRequests.forEach(b => {
        const p = posts.find(x => Number(x.id) === Number(b.post_id)) || {};
        const client = users.find(u => Number(u.id) === Number(b.user_id)) || { name: 'Client' };

        list.innerHTML += `
          <div class="booking-card" style="background:var(--card); padding:16px; border-radius:14px; margin-bottom:14px; border:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; gap:14px;">
            <div>
              <div style="font-size:12px; font-weight:700; color:var(--accent); margin-bottom:8px;">${p.title || 'Service Request'}</div>
              <div style="font-size:14px; font-weight:600; margin-bottom:4px;">Client: ${client.name}</div>
              <div style="font-size:13px; color:var(--muted);">📅 ${b.booking_date} @ ${b.booking_time}</div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              <button class="btn-edit save" style="padding:8px 14px; font-size:13px;" onclick="updateBookingStatus(${b.id}, 'approved')">✅ Accept</button>
              <button class="btn-edit cancel" style="padding:8px 14px; font-size:13px; background:#c62828;" onclick="updateBookingStatus(${b.id}, 'cancelled')">❌ Decline</button>
            </div>
          </div>
        `;
      });
    }

    list.innerHTML += `<hr style="border:0; border-top: 1px solid var(--border); margin: 28px 0 18px 0;">`;
    list.innerHTML += `<h4 style="font-family:'Fraunces',serif; margin-bottom: 16px; color: var(--ink);">📤 Accepted Client Appointments</h4>`;
    if (acceptedAppointments.length === 0) {
      list.innerHTML += `<p style="color:var(--muted); font-size:14px;">No accepted appointments are currently waiting for service completion.</p>`;
    } else {
      acceptedAppointments.forEach(b => {
        const p = posts.find(x => Number(x.id) === Number(b.post_id)) || {};
        const client = users.find(u => Number(u.id) === Number(b.user_id)) || { name: 'Client' };

        list.innerHTML += `
          <div class="booking-card" style="background:var(--card); padding:16px; border-radius:14px; margin-bottom:14px; border:1px solid var(--border);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:14px; margin-bottom:14px;">
              <div>
                <div style="font-size:12px; font-weight:700; color:var(--accent); margin-bottom:8px;">${p.title || 'Accepted Appointment'}</div>
                <div style="font-size:14px; font-weight:600; margin-bottom:4px;">Client: ${client.name}</div>
                <div style="font-size:13px; color:var(--muted);">📅 ${b.booking_date} @ ${b.booking_time}</div>
              </div>
            </div>
            <div style="background:#f8fafc; padding:12px; border-radius:10px; margin-bottom:12px;">
              <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">Hours Worked:</label>
              <div style="display:flex; gap:8px;">
                <input type="number" id="hours-${b.id}" placeholder="e.g. 1.5" min="0.5" step="0.5" style="flex:1; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                <button class="btn-edit save" style="padding:8px 14px; font-size:13px;" onclick="markDone(${b.id})">✅ Done</button>
              </div>
            </div>
          </div>
        `;
      });
    }
    return;
  }

  // Fallback for clients: show their bookings
  list.innerHTML += `<h4 style="font-family:'Fraunces',serif; margin-bottom: 16px; color: var(--ink);">📤 My Appointments Tracking</h4>`;
  if (clientBookings.length === 0) {
    list.innerHTML += `<p style="color:var(--muted); font-size:14px;">You have no appointments yet.</p>`;
  } else {
    clientBookings.forEach(b => {
      const p = posts.find(x => Number(x.id) === Number(b.post_id)) || {};
      const alreadyReviewed = reviews.find(r => Number(r.user_id) === Number(currentUser.id) && Number(r.post_id) === Number(b.post_id));
      const reviewBtn = b.status === 'completed'
        ? `<button class="btn-edit ${alreadyReviewed ? 'cancel' : 'save'}" style="padding:6px 12px; font-size:12px;" onclick="openReviewModal(${b.post_id}, ${p.user_id || 0})">${alreadyReviewed ? '✅ Reviewed' : '⭐ Review'}</button>`
        : '';
      const statusLabel = b.status === 'completed' ? `
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
          <a class="btn-edit save" style="padding:8px 14px; font-size:13px; text-decoration:none;" href="finalpay.php?booking_id=${b.id}">💳 Pay Now</a>
          ${reviewBtn}
        </div>
      ` : `<span class="status-badge ${b.status}" style="text-transform:uppercase; font-size:11px; font-weight:600; padding:6px 10px; border-radius:6px;">${b.status}</span>`;

      list.innerHTML += `
        <div class="booking-card" style="background:var(--card); padding:16px; border-radius:14px; margin-bottom:14px; border:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; gap:14px;">
          <div>
            <div style="font-size:14px; font-weight:600; margin-bottom:4px;">${p.title || 'Service Booking'}</div>
            <div style="font-size:13px; color:var(--muted);">📅 ${b.booking_date} @ ${b.booking_time}</div>
          </div>
          ${statusLabel}
        </div>
      `;
    });
  }
}

function updateBookingStatus(bookingId, status) {
  const fd = new FormData();
  fd.append('booking_id', bookingId);
  fd.append('status', status);

  fetch('update_booking_status.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const idx = bookings.findIndex(b => Number(b.id) === Number(bookingId));
        if (idx !== -1) {
          bookings[idx].status = status;
        }
        if (status === 'completed') {
          if (data.payment_link) {
            window.location.href = data.payment_link;
            return;
          }
          window.location.href = `finalpay.php?booking_id=${bookingId}`;
        }
        renderBookings();
        toast(`Appointment ${status}`);
      } else {
        toast(data.error || 'Could not update appointment');
      }
    })
    .catch(() => toast('Network error while updating appointment'));
}

function markDone(bookingId) {
  const hoursInput = document.getElementById(`hours-${bookingId}`);
  if (!hoursInput) {
    toast('Hours input not found');
    return;
  }

  const hoursNum = parseFloat(hoursInput.value);
  if (isNaN(hoursNum) || hoursNum <= 0) {
    toast('Please enter a valid number of hours');
    return;
  }

  const fd = new FormData();
  fd.append('booking_id', bookingId);
  fd.append('status', 'completed');
  fd.append('hours', hoursNum);

  fetch('update_booking_status.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const idx = bookings.findIndex(b => Number(b.id) === Number(bookingId));
        if (idx !== -1) {
          bookings[idx].status = 'completed';
        }
        if (data.payment_link) {
          window.location.href = data.payment_link;
        } else {
          window.location.href = `finalpay.php?booking_id=${bookingId}`;
        }
      } else {
        toast(data.error || 'Could not complete appointment');
      }
    })
    .catch(() => toast('Network error while completing appointment'));
}

function renderMessages() {
  const container = document.getElementById('messages-list');
  if(!container) return;
  container.innerHTML = '';

  const chatLogs = messages_db.filter(m => Number(m.sender_id) === Number(currentUser.id) || Number(m.receiver_id) === Number(currentUser.id));
  if(chatLogs.length === 0) {
    container.innerHTML = `<p style="color:var(--muted);font-size:14px">Your communications inbox channel is currently empty.</p>`;
    return;
  }

  // Group by other user, track last message & unread count
  const uniqueUsers = {};
  chatLogs.forEach(m => {
    const otherId = Number(m.sender_id) === Number(currentUser.id) ? Number(m.receiver_id) : Number(m.sender_id);
    if (!uniqueUsers[otherId]) {
      const u = users.find(x => Number(x.id) === otherId);
      uniqueUsers[otherId] = { user: u || { id: otherId, name: 'User #' + otherId }, lastMsg: m, unread: 0 };
    } else {
      // Keep latest message
      if(new Date(m.created_at) >= new Date(uniqueUsers[otherId].lastMsg.created_at)) {
        uniqueUsers[otherId].lastMsg = m;
      }
    }
    // Count unread messages sent TO current user
    if(Number(m.receiver_id) === Number(currentUser.id) && Number(m.is_read) === 0) {
      uniqueUsers[otherId].unread++;
    }
  });

  container.innerHTML += `<h4 style="font-family:'Fraunces',serif; margin-bottom:16px; color:var(--ink);">💬 Your Conversations</h4>`;

  Object.values(uniqueUsers).forEach(({ user: u, lastMsg, unread }) => {
    const isMine = Number(lastMsg.sender_id) === Number(currentUser.id);
    const preview = (isMine ? 'You: ' : '') + (lastMsg.message.length > 50 ? lastMsg.message.slice(0,50)+'…' : lastMsg.message);
    const timeStr = lastMsg.created_at ? lastMsg.created_at.slice(0,10) : '';
    const unreadBadge = unread > 0 ? `<span style="background:var(--accent);color:#fff;border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;">${unread}</span>` : '';

    container.innerHTML += `
      <div class="booking-card" style="background:var(--card); padding:16px; border-radius:14px; margin-bottom:12px; border:1px solid var(--border); cursor:pointer; transition:box-shadow .2s;" 
           onmouseenter="this.style.boxShadow='0 4px 16px rgba(0,0,0,.08)'" onmouseleave="this.style.boxShadow='none'"
           onclick="openChat(${u.id}, null)">
        <div style="display:flex; gap:12px; align-items:center;">
          <div style="width:44px; height:44px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; flex-shrink:0;">${ini(u.name)}</div>
          <div style="flex:1; min-width:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
              <div style="font-size:14px; font-weight:600; color:var(--ink);">${u.name}</div>
              <div style="display:flex; gap:8px; align-items:center;">
                <span style="font-size:11px; color:var(--muted);">${timeStr}</span>
                ${unreadBadge}
              </div>
            </div>
            <div style="font-size:13px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${preview}</div>
          </div>
        </div>
      </div>
    `;
  });
}

// ── Chat Modal ──────────────────────────────────────────────────────────────
function openChat(otherUserId, postId) {
  activeChatReceiverId = Number(otherUserId);
  // Try to find a relevant post id from existing messages if not supplied
  if(postId) {
    activeChatPostId = Number(postId);
  } else {
    const existing = messages_db.find(m =>
      (Number(m.sender_id) === Number(currentUser.id) && Number(m.receiver_id) === activeChatReceiverId) ||
      (Number(m.sender_id) === activeChatReceiverId && Number(m.receiver_id) === Number(currentUser.id))
    );
    activeChatPostId = existing ? Number(existing.post_id) : null;
  }

  const otherUser = users.find(u => Number(u.id) === activeChatReceiverId) || { name: 'User #' + otherUserId };
  const post = activeChatPostId ? (posts.find(p => Number(p.id) === activeChatPostId) || null) : null;

  document.getElementById('chat-with-name').textContent = 'Chat with ' + otherUser.name;
  document.getElementById('chat-about').textContent = post ? 'Re: ' + post.title : '';

  renderChatBody();

  const overlay = document.getElementById('overlay');
  const chatModal = document.getElementById('modal-chat');
  document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
  chatModal.style.display = 'block';
  overlay.classList.add('show');

  // Mark messages as read
  messages_db.forEach(m => {
    if(Number(m.receiver_id) === Number(currentUser.id) && Number(m.sender_id) === activeChatReceiverId) {
      m.is_read = 1;
    }
  });
  renderMessages(); // Refresh unread badges
}

function renderChatBody() {
  const body = document.getElementById('chat-body');
  if(!body) return;
  body.innerHTML = '';

  const thread = messages_db.filter(m => {
    const sameUsers = (Number(m.sender_id) === Number(currentUser.id) && Number(m.receiver_id) === activeChatReceiverId) ||
                      (Number(m.sender_id) === activeChatReceiverId && Number(m.receiver_id) === Number(currentUser.id));
    if(activeChatPostId) return sameUsers && Number(m.post_id) === activeChatPostId;
    return sameUsers;
  });

  if(!thread.length) {
    body.innerHTML = '<div style="text-align:center;color:var(--muted);font-size:14px;margin:40px 0">Send a message to start the conversation!</div>';
  } else {
    thread.forEach(m => {
      const isMine = Number(m.sender_id) === Number(currentUser.id);
      body.innerHTML += `<div class="chat-msg ${isMine ? 'sent' : 'recv'}">${m.message}</div>`;
    });
  }
  body.scrollTop = body.scrollHeight;
}

function sendChat() {
  const inp = document.getElementById('chat-input');
  const msg = inp.value.trim();
  if(!msg || !activeChatReceiverId) return;

  const fd = new FormData();
  fd.append('receiver_id', activeChatReceiverId);
  fd.append('post_id', activeChatPostId || 0);
  fd.append('message', msg);

  fetch('send_message.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        messages_db.push({
          id: data.id || Date.now(),
          sender_id: Number(currentUser.id),
          receiver_id: activeChatReceiverId,
          post_id: activeChatPostId || 0,
          message: msg,
          is_read: 0,
          created_at: new Date().toISOString().slice(0,19).replace('T',' ')
        });
        inp.value = '';
        renderChatBody();
        renderMessages();
      } else {
        toast(data.error || 'Failed to send message');
      }
    })
    .catch(() => toast('Network error sending message'));
}

function closeModal() {
  document.getElementById('overlay').classList.remove('show');
  document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
}

// ── Reviews ─────────────────────────────────────────────────────────────────
function openReviewModal(postId, providerId) {
  const post = posts.find(p => Number(p.id) === Number(postId));
  const provider = users.find(u => Number(u.id) === Number(providerId));

  // Check if already reviewed
  const alreadyReviewed = reviews.find(r => Number(r.user_id) === Number(currentUser.id) && Number(r.post_id) === Number(postId));

  const overlay = document.getElementById('overlay');
  const chatModal = document.getElementById('modal-chat');
  document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');

  // Build review modal dynamically
  let reviewModal = document.getElementById('modal-review-inline');
  if(!reviewModal) {
    reviewModal = document.createElement('div');
    reviewModal.className = 'modal';
    reviewModal.id = 'modal-review-inline';
    reviewModal.style.cssText = 'display:none; max-width:480px; width:90%;';
    document.getElementById('overlay').appendChild(reviewModal);
  }

  if(alreadyReviewed) {
    reviewModal.innerHTML = `
      <button class="modal-close" onclick="closeModal()">✕</button>
      <h2>Your Review</h2>
      <p class="sub" style="margin-bottom:16px;">For: <strong>${post ? post.title : 'Service'}</strong></p>
      <div style="display:flex; gap:4px; font-size:24px; margin-bottom:12px; color:#f59e0b;">${'★'.repeat(alreadyReviewed.rating)}${'☆'.repeat(5 - alreadyReviewed.rating)}</div>
      <p style="font-size:14px; color:var(--ink); background:var(--warm); padding:14px; border-radius:10px;">${alreadyReviewed.review}</p>
      <p style="font-size:12px; color:var(--muted); margin-top:8px;">Submitted on ${alreadyReviewed.created_at ? alreadyReviewed.created_at.slice(0,10) : ''}</p>
    `;
  } else {
    reviewModal.innerHTML = `
      <button class="modal-close" onclick="closeModal()">✕</button>
      <h2>Leave a Review</h2>
      <p class="sub" style="margin-bottom:16px;">For: <strong>${post ? post.title : 'Service'}</strong> by ${provider ? provider.name : 'Provider'}</p>
      <div style="margin-bottom:16px;">
        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:8px;">Rating</label>
        <div style="display:flex; gap:6px;" id="star-picker">
          ${[1,2,3,4,5].map(n => `<span data-val="${n}" onclick="pickStar(${n})" style="font-size:28px; cursor:pointer; color:#d1d5db; transition:color .15s;">★</span>`).join('')}
        </div>
        <input type="hidden" id="review-rating-val" value="0"/>
      </div>
      <div style="margin-bottom:16px;">
        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:8px;">Your Review</label>
        <textarea id="review-text-val" placeholder="Share your experience with this service…" style="width:100%; min-height:100px; padding:10px 14px; border:1.5px solid var(--border); border-radius:10px; font-family:'DM Sans'; font-size:14px; resize:vertical; box-sizing:border-box;"></textarea>
      </div>
      <button class="btn-primary" style="width:100%;" onclick="submitReviewInline(${postId}, ${providerId})">Submit Review</button>
    `;
  }

  reviewModal.style.display = 'block';
  overlay.classList.add('show');
}

function pickStar(n) {
  document.getElementById('review-rating-val').value = n;
  document.querySelectorAll('#star-picker span').forEach((s, i) => {
    s.style.color = i < n ? '#f59e0b' : '#d1d5db';
  });
}

function submitReviewInline(postId, providerId) {
  const rating = parseInt(document.getElementById('review-rating-val').value);
  const text = document.getElementById('review-text-val').value.trim();

  if(!rating || rating < 1) { toast('Please select a star rating'); return; }
  if(!text) { toast('Please write a review'); return; }

  const fd = new FormData();
  fd.append('post_id', postId);
  fd.append('rating', rating);
  fd.append('review', text);

  fetch('submit_review.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
      if(data.success) {
        reviews.push({
          id: data.id || Date.now(),
          user_id: Number(currentUser.id),
          post_id: Number(postId),
          rating,
          review: text,
          created_at: new Date().toISOString().slice(0,19).replace('T',' ')
        });
        // Update stat
        const reviewCount = reviews.filter(r => {
          const p = posts.find(x => Number(x.id) === Number(r.post_id));
          return p && Number(p.user_id) === Number(currentUser.id);
        }).length;
        const el = document.getElementById('stat-reviews');
        if(el) el.textContent = reviewCount;

        closeModal();
        toast('⭐ Review submitted! Thank you.');
        renderBookings(); // Refresh to update button state
      } else {
        toast(data.error || 'Failed to submit review');
      }
    })
    .catch(() => toast('Network error submitting review'));
}