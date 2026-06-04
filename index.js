
let currentUser=null,
currentCategory='All',
currentProviderUser=null,
activeBookingPost=null,
activeChatPostId=null,
activeChatReceiverId=null;
const EMOJI={'Cleaning':'🧹','Plumbing':'🔧','Electrical':'⚡','Tutoring':'📚','Garden':'🌿','Beauty':'💅','Carpentry':'🪚','Painting':'🎨','Other':'🛠️'};

// Sync color pool array with the modern aesthetic
const COLORS = ['#2563eb', '#6366f1', '#7c3aed', '#00d2ff', '#4f46e5', '#3b82f6'];
// These are the Demo users.
let users=[
  {id:1,name:'Thabo Mokoena',email:'thabo@demo.com',password:'demo',role:'seller',created_at:'2024-01-10'},
  {id:2,name:'Sarah van Wyk',email:'sarah@demo.com',password:'demo',role:'seller',created_at:'2024-02-14'},
  {id:3,name:'Lungelo Dlamini',email:'lungelo@demo.com',password:'demo',role:'buyer',created_at:'2024-03-20'},
];
//the demo posts created by demo users.
let posts=[
  {id:1,user_id:1,title:'Deep Home Cleaning Service',description:'Professional deep cleaning for your home. We use eco-friendly products and our team is fully vetted. Available weekdays and weekends.',price:350,image:'',category:'Cleaning',location:'Johannesburg, Sandton',created_at:'2024-04-01'},
  {id:2,user_id:1,title:'Office Cleaning Package',description:'Regular office cleaning contracts available. Flexible scheduling to suit your business hours.',price:280,image:'',category:'Cleaning',location:'Johannesburg, Rosebank',created_at:'2024-04-05'},
  {id:3,user_id:2,title:'Maths & Science Tutoring',description:'Experienced Grade 10-12 tutor. Matric exam preparation specialist with 95% pass rate among students.',price:200,image:'',category:'Tutoring',location:'Pretoria, Centurion',created_at:'2024-04-08'},
  {id:4,user_id:2,title:'Emergency Plumbing Repairs',description:'24/7 emergency plumbing service. Burst pipes, blocked drains, geyser repairs. Licensed and insured.',price:500,image:'',category:'Plumbing',location:'Johannesburg, Midrand',created_at:'2024-04-10'},
  {id:5,user_id:1,title:'Garden Maintenance',description:'Complete garden maintenance including mowing, trimming, planting and cleanup. Monthly contracts available.',price:400,image:'',category:'Garden',location:'Johannesburg, Northcliff',created_at:'2024-04-12'},
  {id:6,user_id:2,title:'Electrical Installations',description:'Certified electrician. DB board upgrades, lighting installations, fault finding. COC provided.',price:650,image:'',category:'Electrical',location:'Johannesburg, Randburg',created_at:'2024-04-15'},
];
// the demo reviews left by demo users.
let reviews=[
  {id:1,user_id:3,post_id:1,rating:5,review:'Absolutely fantastic service! Very thorough and professional.',created_at:'2024-05-01'},
  {id:2,user_id:3,post_id:3,rating:4,review:'Great tutor, my son improved significantly in maths.',created_at:'2024-05-03'},
  {id:3,user_id:3,post_id:2,rating:5,review:'Reliable and affordable. Highly recommend!',created_at:'2024-05-05'},
];
// the demo bookings.
let bookings=[
  {id:1,user_id:3,post_id:1,booking_date:'2024-06-10',booking_time:'09:00',notes:'Please bring equipment',status:'confirmed',created_at:'2024-05-20'},
  {id:2,user_id:3,post_id:3,booking_date:'2024-06-12',booking_time:'14:00',notes:'',status:'pending',created_at:'2024-05-21'},
];
// demo messages.
let messages_db=[
  {id:1,sender_id:3,receiver_id:1,post_id:1,message:'Hi, are you available this Saturday?',is_read:0,created_at:'2024-05-22'},
  {id:2,sender_id:1,receiver_id:3,post_id:1,message:"Yes, I'm available! What time works for you?",is_read:1,created_at:'2024-05-22'},
];
let nid={user:4,post:7,review:4,booking:3,message:3};
// find and return based of user id and post id.
function uid(id){return users.find(u=>u.id===id)}
function pid(id){return posts.find(p=>p.id===id)}

function ini(name){return name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase()}
function col(id){return COLORS[id%COLORS.length]}
function stars(r){return '★'.repeat(r)+'☆'.repeat(5-r)}

function avgR(userId){ 
  const rs=reviews.filter(r=>posts.find(p=>p.id===r.post_id&&p.user_id===userId));
  
  if(!rs.length)return'—';
  return(rs.reduce((s,r)=>s+r.rating,0)/rs.length).toFixed(1)
}

function toast(msg){
  const t=document.getElementById('toast');
  t.textContent=msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3000)
}
//checks if user is logged in.
function requireAuth(fn){
  if(!currentUser){showModal('modal-signin');
    return}fn()
}
// this used to show windows when requested.
function showModal(id){
  document.querySelectorAll('.modal').forEach(m=>m.style.display='none');
  document.getElementById(id).style.display='block';
  document.getElementById('overlay').classList.add('show')
}

function closeModal(){
  document.getElementById('overlay').classList.remove('show')
}
document.getElementById('overlay').addEventListener('click',function(e){if(e.target===this)closeModal()});

// handles the user sign-in.
function doSignIn(){
  const em=document.getElementById('si-email').value.trim();
  const pw=document.getElementById('si-pass').value;
  const u=users.find(x=>x.email===em&&x.password===pw);

  if(!u){
    toast('Invalid email or password');
    return
  }
  currentUser=u;updateNav();closeModal();toast('Welcome back, '+u.name.split(' ')[0]+'!');
}
//handles user registration.
function doSignUp(){
  const name=document.getElementById('su-name').value.trim();
  const email=document.getElementById('su-email').value.trim();
  const pass=document.getElementById('su-pass').value;
  const role=document.getElementById('su-role').value;

  if(!name||!email||!pass){
    toast('Please fill all fields');
    return
  }

  if(users.find(u=>u.email===email)){
    toast('Email already registered');
    return
  }
  const u={id:nid.user++,name,email,password:pass,role,created_at:new Date().toISOString().slice(0,10)};
  users.push(u);currentUser=u;updateNav();closeModal();toast('Welcome to LinkingLocals, '+name.split(' ')[0]+'!');
}
//logs current user out.
function doSignOut(){
  currentUser=null;
  updateNav();
  showHome();
  toast('Signed out');
  document.getElementById('nav-dropdown').classList.remove('open')
}
// updates the navigation bar based on user login status.
function updateNav(){
  const loggedIn=!!currentUser;
  document.getElementById('nav-signin').style.display=loggedIn?'none':'';
  document.getElementById('nav-signup').style.display=loggedIn?'none':'';
  const av=document.getElementById('nav-avatar');
  if(loggedIn){
    av.classList.add('show');
    document.getElementById('avatar-letter').textContent=ini(currentUser.name)
  }
  else av.classList.remove('show');
}
function toggleDropdown(){document.getElementById('nav-dropdown').classList.toggle('open')}
document.addEventListener('click',function(e){if(!e.target.closest('.avatar-btn')&&!e.target.closest('.dropdown'))document.getElementById('nav-dropdown').classList.remove('open')});

// Shows the home page / index.php
function showHome(){
  document.getElementById('home-page').style.display='block';
  document.getElementById('profile-page').classList.remove('show');
  document.getElementById('dashboard-page').classList.remove('show');
  renderPostsInGrid(posts, 'posts-grid', false);
  window.scrollTo(0,0);
}
// displays the profile page of the user based on the user id passed in.
function showProfilePage(userId){
  const user=uid(userId);if(!user)return;
  currentProviderUser=user;
  document.getElementById('home-page').style.display='none';
  document.getElementById('dashboard-page').classList.remove('show');
  document.getElementById('profile-page').classList.add('show');
  const av=document.getElementById('pp-avatar');
  av.textContent=ini(user.name);av.style.background=col(user.id);
  document.getElementById('pp-name').textContent=user.name;
  document.getElementById('pp-email').textContent=user.email;
  const up=posts.filter(p=>p.user_id===userId);
  const ur=reviews.filter(r=>up.find(p=>p.id===r.post_id));
  document.getElementById('pp-posts').textContent=up.length;
  document.getElementById('pp-reviews').textContent=ur.length;
  document.getElementById('pp-rating').textContent=avgR(userId);
  document.getElementById('pp-action-btns').style.display=(currentUser&&currentUser.id===userId)?'none':'flex';
  renderPostsInGrid(up,'pp-posts-grid',true);
  const rl=document.getElementById('pp-reviews-list');rl.innerHTML='';
  ur.forEach(r=>{const ru=uid(r.user_id);rl.innerHTML+=`<div class="review-item"><div class="review-top"><span class="review-author">${ru?ru.name:'Anonymous'}</span><span class="review-stars">${stars(r.rating)}</span></div><div class="review-text">${r.review}</div><div class="review-date">${r.created_at}</div></div>`});
  document.querySelectorAll('#profile-page .ptab').forEach((t,i)=>t.classList.toggle('active',i===0));
  document.getElementById('pp-services').style.display='block';
  document.getElementById('pp-reviews-tab').style.display='none';
  window.scrollTo(0,0);
}
function showDashboard(tab){
  if(!currentUser){showModal('modal-signin');return}
  document.getElementById('home-page').style.display='none';
  document.getElementById('profile-page').classList.remove('show');
  document.getElementById('dashboard-page').classList.add('show');
  document.getElementById('nav-dropdown').classList.remove('open');
  const av=document.getElementById('dp-avatar');
  av.textContent=ini(currentUser.name);av.style.background=col(currentUser.id);
  document.getElementById('dp-name').textContent=currentUser.name;
  document.getElementById('dp-email').textContent=currentUser.email;
  document.getElementById('dp-role').textContent=currentUser.role.toUpperCase();
  document.getElementById('edit-name-display').textContent=currentUser.name;
  document.getElementById('edit-email-display').textContent=currentUser.email;
  document.getElementById('edit-role-display').textContent=currentUser.role;
  document.getElementById('edit-joined').textContent=currentUser.created_at;
  const mp=posts.filter(p=>p.user_id===currentUser.id);
  const mb=bookings.filter(b=>b.user_id===currentUser.id);
  const mm=messages_db.filter(m=>m.sender_id===currentUser.id||m.receiver_id===currentUser.id);
  const mr=reviews.filter(r=>mp.find(p=>p.id===r.post_id));
  document.getElementById('stat-services').textContent=mp.length;
  document.getElementById('stat-bookings').textContent=mb.length;
  document.getElementById('stat-messages').textContent=mm.length;
  document.getElementById('stat-reviews').textContent=mr.length;
  switchDTab(tab||'info');window.scrollTo(0,0);
}
//allows the user to switch to different tabs on the dashboard.
function switchDTab(tab){
  ['info','post','bookings','messages'].forEach(t=>{
    document.getElementById('dtab-'+t+'-content').style.display=t===tab?'block':'none';
    document.getElementById('dtab-'+t).classList.toggle('active',t===tab);
  });
  if(tab==='post')renderMyPosts();
  if(tab==='bookings')renderBookings();
  if(tab==='messages')renderMessages();
}

function switchPTab(btn,targetId){
  document.querySelectorAll('#profile-page .ptab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  ['pp-services','pp-reviews-tab'].forEach(id=>document.getElementById(id).style.display=id===targetId?'block':'none');
}

// Filters the posts according to what has been searched.
function filterPosts(q){
  const s=q.toLowerCase();
  const f=posts.filter(p=>p.title.toLowerCase().includes(s)||p.category.toLowerCase().includes(s)||p.location.toLowerCase().includes(s));
  const completeList = currentCategory==='All'?f:f.filter(p=>p.category===currentCategory);
  renderPostsInGrid(completeList, 'posts-grid', false);
}

// Filters the posts according to which category pill has been selected.
function filterCat(cat){
  currentCategory=cat;
  document.querySelectorAll('.cat-pill').forEach(p=>p.classList.toggle('active',p.textContent.includes(cat)||(cat==='All'&&p.textContent==='All')));
  const completeList = cat==='All'?posts:posts.filter(p=>p.category===cat);
  renderPostsInGrid(completeList, 'posts-grid', false);
}

// Ensures it displays the posts within the grid layout.
function renderPostsInGrid(list,gridId,noAuthorNav){
  const g=document.getElementById(gridId);if(!g)return;g.innerHTML='';
  if(!list || !list.length){g.innerHTML='<p style="color:var(--muted);grid-column:1/-1;padding:20px 0">No services found.</p>';return}
  list.forEach(post=>{
    const author=uid(post.user_id) || { id: post.user_id, name: 'Local Provider' };
    const pr=reviews.filter(r=>r.post_id===post.id);
    const avg=pr.length?(pr.reduce((s,r)=>s+r.rating,0)/pr.length).toFixed(1):'New';
    const emoji=EMOJI[post.category]||'🛠️';
    g.innerHTML+=`<div class="post-card" onclick="openPostDetail(${post.id})">
      <div class="post-img" style="background:linear-gradient(135deg,${col(post.user_id)}22,${col(post.user_id)}55)">
        <span>${emoji}</span><span class="cat-badge">${post.category}</span>
      </div>
      <div class="post-body">
        <div class="post-title">${post.title}</div>
        <div class="post-meta"><span class="post-loc">📍 ${post.location}</span><span class="post-price">R${post.price}/hr</span></div>
        <div class="post-footer">
          <div class="post-author" onclick="event.stopPropagation();showProfilePage(${author.id})">
            <div class="pa-avatar" style="background:${col(author.id)}">${ini(author.name)}</div>
            <span class="pa-name" style="color:var(--accent)">${author.name.split(' ')[0]}</span>
          </div>
          <span class="post-stars">★ ${avg}</span>
        </div>
        <div class="post-actions">
          <button class="btn-sm outline" onclick="event.stopPropagation();openPostDetail(${post.id})">Details</button>
          <button class="btn-sm fill" onclick="event.stopPropagation();requireAuth(()=>startBooking(${post.id}))">Book</button>
          <button class="btn-sm outline" onclick="event.stopPropagation();requireAuth(()=>startChat(${post.id}))">💬</button>
        </div>
      </div>
    </div>`;
  });
}

function openPostDetail(postId){
  const post=pid(postId);if(!post)return;
  const author=uid(post.user_id);
  const pr=reviews.filter(r=>r.post_id===postId);
  const avg=pr.length?(pr.reduce((s,r)=>s+r.rating,0)/pr.length).toFixed(1):'—';
  document.getElementById('postdetail-content').innerHTML=`
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;cursor:pointer;padding:10px;border-radius:10px;background:var(--warm)" onclick="closeModal();showProfilePage(${post.user_id})">
      <div class="pa-avatar" style="width:44px;height:44px;font-size:16px;background:${col(post.user_id)};flex-shrink:0">${author?ini(author.name):'?'}</div>
      <div><div style="font-weight:600">${author?author.name:'Unknown'}</div><div style="font-size:13px;color:var(--accent)">View full profile →</div></div>
    </div>
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--accent);margin-bottom:6px">${post.category}</div>
    <h2 style="font-family:'Fraunces',serif;font-size:22px;margin-bottom:10px">${post.title}</h2>
    <p style="color:var(--muted);font-size:15px;line-height:1.7;margin-bottom:18px">${post.description}</p>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">
      <div style="background:var(--warm);border-radius:10px;padding:12px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:4px">Price</div><div style="font-size:18px;font-weight:700;color:var(--accent)">R${post.price}/hr</div></div>
      <div style="background:var(--warm);border-radius:10px;padding:12px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:4px">Rating</div><div style="font-size:18px;font-weight:700">★ ${avg}</div></div>
      <div style="background:var(--warm);border-radius:10px;padding:12px;text-align:center"><div style="font-size:11px;color:var(--muted);margin-bottom:4px">Reviews</div><div style="font-size:18px;font-weight:700">${pr.length}</div></div>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn-primary" style="flex:2" onclick="closeModal();requireAuth(()=>startBooking(${post.id}))">Book Now</button>
      <button class="btn-sm outline" style="flex:1;padding:13px" onclick="closeModal();requireAuth(()=>startChat(${post.id}))">💬 Chat</button>
      <button class="btn-sm outline" style="padding:13px" onclick="closeModal();requireAuth(()=>{currentProviderUser=uid(${post.user_id});openReview()})">⭐</button>
    </div>`;
  showModal('modal-postdetail');
}

function startBooking(postId){const post=pid(postId);if(!post)return;activeBookingPost=post;document.getElementById('book-svc-name').textContent=post.title;showModal('modal-book')}
function openBookFromProfile(){if(!currentProviderUser)return;const p=posts.find(x=>x.user_id===currentProviderUser.id);if(p)startBooking(p.id)}
function doBook(){
  const date=document.getElementById('book-date').value;
  const time=document.getElementById('book-time').value;
  const notes=document.getElementById('book-notes').value;
  if(!date||!time){toast('Please select date and time');return}
  bookings.push({id:nid.booking++,user_id:currentUser.id,post_id:activeBookingPost.id,booking_date:date,booking_time:time,notes,status:'pending',created_at:new Date().toISOString().slice(0,10)});
  closeModal();toast('Booking submitted! The provider will confirm shortly.');
}

function startChat(postId){
  const post=pid(postId);if(!post)return;
  activeChatPostId=postId;activeChatReceiverId=post.user_id;
  const provider=uid(post.user_id);
  document.getElementById('chat-with-name').textContent='Chat with '+(provider?provider.name:'Provider');
  document.getElementById('chat-about').textContent='Re: '+post.title;
  renderChatBody();showModal('modal-chat');
}
function openChatWithProvider(){if(!currentProviderUser)return;const p=posts.find(x=>x.user_id===currentProviderUser.id);if(p)startChat(p.id)}
function renderChatBody(){
  const body=document.getElementById('chat-body');body.innerHTML='';
  const thread=messages_db.filter(m=>m.post_id===activeChatPostId&&
    ((m.sender_id===currentUser.id&&m.receiver_id===activeChatReceiverId)||(m.sender_id===activeChatReceiverId&&m.receiver_id===currentUser.id)));
  if(!thread.length)body.innerHTML='<div style="text-align:center;color:var(--muted);font-size:14px;margin:40px 0">Send a message to start the conversation!</div>';
  else thread.forEach(m=>{body.innerHTML+=`<div class="chat-msg ${m.sender_id===currentUser.id?'sent':'recv'}">${m.message}</div>`});
  body.scrollTop=body.scrollHeight;
}
function sendChat(){
  const inp=document.getElementById('chat-input');const msg=inp.value.trim();if(!msg)return;
  messages_db.push({id:nid.message++,sender_id:currentUser.id,receiver_id:activeChatReceiverId,post_id:activeChatPostId,message:msg,is_read:0,created_at:new Date().toISOString().slice(0,10)});
  inp.value='';renderChatBody();
}

function openReview(){
  if(!currentProviderUser)return;
  const u=uid(currentProviderUser.id||currentProviderUser);
  document.getElementById('review-provider-name').textContent=u?u.name:'this provider';
  showModal('modal-review');
}
function submitReview(){
  const rating=parseInt(document.getElementById('review-rating').value);
  const text=document.getElementById('review-text').value.trim();
  if(!text){toast('Please write a review');return}
  const pp=posts.find(p=>p.user_id===(currentProviderUser.id||currentProviderUser));
  reviews.push({id:nid.review++,user_id:currentUser.id,post_id:pp?pp.id:0,rating,review:text,created_at:new Date().toISOString().slice(0,10)});
  document.getElementById('review-text').value='';
  closeModal();toast('Review submitted! Thank you.');
}

function renderMyPosts(){const mp=posts.filter(p=>p.user_id===currentUser.id);renderPostsInGrid(mp,'my-posts-grid',true)}
function submitPost(){
  const title=document.getElementById('ps-title').value.trim();
  const cat=document.getElementById('ps-cat').value;
  const price=parseFloat(document.getElementById('ps-price').value);
  const location=document.getElementById('ps-location').value.trim();
  const desc=document.getElementById('ps-desc').value.trim();
  const image=document.getElementById('ps-image').value.trim();
  if(!title||!price||!location||!desc){toast('Please fill all required fields');return}
  posts.push({id:nid.post++,user_id:currentUser.id,title,description:desc,price,image,category:cat,location,created_at:new Date().toISOString().slice(0,10)});
  ['ps-title','ps-price','ps-location','ps-desc','ps-image'].forEach(id=>document.getElementById(id).value='');
  toast('Service published successfully!');renderMyPosts();
}

function renderBookings(){
  const bl=document.getElementById('bookings-list');bl.innerHTML='';
  const mb=bookings.filter(b=>b.user_id===currentUser.id);
  if(!mb.length){bl.innerHTML='<p style="color:var(--muted);padding:20px 0">No bookings yet.</p>';return}
  mb.forEach(b=>{
    const post=pid(b.post_id);
    bl.innerHTML+=`<div class="booking-item">
      <div class="booking-info">
        <div class="bi-title">${post?post.title:'Service'}</div>
        <div class="bi-meta">📅 ${b.booking_date} at ${b.booking_time}${b.notes?' · '+b.notes:''}</div>
      </div>
      <span class="status-badge ${b.status}">${b.status.charAt(0).toUpperCase()+b.status.slice(1)}</span>
    </div>`;
  });
}

function renderMessages(){
  const ml=document.getElementById('messages-list');ml.innerHTML='';
  const convMap={};
  messages_db.filter(m=>m.sender_id===currentUser.id||m.receiver_id===currentUser.id).forEach(m=>{
    const other=m.sender_id===currentUser.id?m.receiver_id:m.sender_id;
    if(!convMap[other]||convMap[other].id<m.id)convMap[other]=m;
  });
  const convs=Object.values(convMap);
  if(!convs.length){ml.innerHTML='<p style="color:var(--muted);padding:20px 0">No messages yet.</p>';return}
  convs.forEach(m=>{
    const otherId=m.sender_id===currentUser.id?m.receiver_id:m.sender_id;
    const other=uid(otherId);const unread=m.is_read===0&&m.receiver_id===currentUser.id;
    const otherName=other?other.name:'User';
    ml.innerHTML+=`<div class="msg-item" onclick="activeChatPostId=${m.post_id};activeChatReceiverId=${otherId};document.getElementById('chat-with-name').textContent='Chat with ${otherName.replace(/'/g,"\\'")}';document.getElementById('chat-about').textContent='';renderChatBody();showModal('modal-chat')">
      <div class="msg-avatar" style="background:${col(otherId)}">${other?ini(other.name):'?'}</div>
      <div class="msg-body">
        <div class="msg-name">${otherName}</div>
        <div class="msg-preview">${m.message.substring(0,60)}${m.message.length>60?'…':''}</div>
      </div>
      ${unread?'<span class="msg-unread">New</span>':''}
    </div>`;
  });
}

function startEdit(){
  ['edit-name-display','edit-email-display'].forEach(id=>document.getElementById(id).style.display='none');
  document.getElementById('edit-name-input').style.display='block';
  document.getElementById('edit-email-input').style.display='block';
  document.getElementById('edit-name-input').value=currentUser.name;
  document.getElementById('edit-email-input').value=currentUser.email;
  document.getElementById('edit-btns-view').style.display='none';
  document.getElementById('edit-btns-edit').style.display='flex';
}
function saveEdit(){
  const name=document.getElementById('edit-name-input').value.trim();
  const email=document.getElementById('edit-email-input').value.trim();
  if(!name||!email){toast('Name and email required');return}
  currentUser.name=name;currentUser.email=email;
  const u=users.find(x=>x.id===currentUser.id);if(u){u.name=name;u.email=email}
  cancelEdit();
  document.getElementById('dp-name').textContent=name;
  document.getElementById('dp-email').textContent=email;
  document.getElementById('edit-name-display').textContent=name;
  document.getElementById('edit-email-display').textContent=email;
  document.getElementById('avatar-letter').textContent=ini(name);
  document.getElementById('dp-avatar').textContent=ini(name);
  toast('Profile updated!');
}
function cancelEdit(){
  ['edit-name-display','edit-email-display'].forEach(id=>document.getElementById(id).style.display='');
  document.getElementById('edit-name-input').style.display='none';
  document.getElementById('edit-email-input').style.display='none';
  document.getElementById('edit-btns-view').style.display='';
  document.getElementById('edit-btns-edit').style.display='none';
}

showHome();
