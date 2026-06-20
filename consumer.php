<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_listing_action'])) {
    $title = trim($_POST['title']);
    $cat = $_POST['category'];
    $cond = $_POST['condition'];
    $prov = $_POST['province'];
    $price = floatval($_POST['price']);
    $desc = trim($_POST['description']);
    $seller_name = $_SESSION['user_name'];
    $user_id = $_SESSION['user_id'];
    
    if (!empty($title) && !empty($cat) && !empty($cond) && !empty($prov) && $price > 0 && !empty($desc)) {
        $stmt = $conn->prepare("INSERT INTO listings (title, category, condition_status, province, price, description, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $cat, $cond, $prov, $price, $desc, $user_id]);
        header("Location: consumer.php");
        exit();
    }
}

$stmt = $conn->prepare("SELECT id, title, price, category AS cat, condition_status AS cond, province AS prov, description AS `desc`, user_id FROM listings ORDER BY id DESC");
$stmt->execute();
$realListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT item_title, price, purchase_date FROM orders WHERE user_id = ? ORDER BY purchase_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$myOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dbListings = $realListings;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SureTrade SA</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-consumer">
<nav class="nav">
  <div class="nav-logo" onclick="pg('home')">SureTrade SA</div>
  <div class="nav-links">
    <button class="nl" id="nl-home" onclick="pg('home')">Home</button>
    <button class="nl" id="nl-listings" onclick="pg('listings')">Browse</button>
    <button class="nl" id="nl-sell" onclick="pg('sell')">Sell Item</button>
    <button class="nl" id="nl-dashboard" onclick="pg('dashboard')">Dashboard</button>
  </div>
  <div class="nav-user">
    <span id="nav-username"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
    <button class="btn-sm-nav" onclick="logout()">Logout</button>
  </div>
  <button class="ham" onclick="toggleMob()">
    <span></span><span></span><span></span>
  </button>
</nav>

<div class="mob-menu" id="mob-menu">
  <button onclick="pg('home');closeMob()">Home</button>
  <button onclick="pg('listings');closeMob()">Browse Listings</button>
  <button onclick="pg('sell');closeMob()">Sell an Item</button>
  <button onclick="pg('dashboard');closeMob()">My Dashboard</button>
  <button onclick="logout()" style="color:#ffb6b6;border-top:1px solid rgba(255,255,255,.15)">Logout</button>
</div>

<div class="toast" id="toast"></div>

<div class="page on" id="p-home">
  <div class="hero">
    <h1>SureTrade SA</h1>
    <p>Buy and sell safely. Made for South Africa.</p>
    <div class="hero-search">
      <input id="hq" placeholder="Search listings..." onkeydown="if(event.key==='Enter')heroSearch()">
      <button onclick="heroSearch()">Search</button>
    </div>
    <div class="hero-cats">
      <button onclick="catSearch('Clothing')">Clothing</button>
      <button onclick="catSearch('Electronics')">Electronics</button>
      <button onclick="catSearch('Furniture')">Furniture</button>
      <button onclick="catSearch('Kitchen')">Kitchen</button>
      <button onclick="catSearch('Vehicles')">Vehicles</button>
      <button onclick="catSearch('Books')">Books</button>
      <button onclick="catSearch('Miscellaneous')">Miscellaneous</button>
    </div>
  </div>
  <div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
      <strong>Featured listings</strong>
      <button class="btn btn-o btn-s" onclick="pg('listings')">View all</button>
    </div>
    <div class="pgrid" id="home-grid"></div>
  </div>
</div>

<div class="page" id="p-listings">
  <div class="sec-hdr">
    <h1>Browse Marketplace</h1>
    <p>Find secure items near you</p>
  </div>
  <div class="wrap">
    <div class="fbar">
      <input id="fq" placeholder="Keywords..." oninput="filterListings()">
      <select id="fcat" onchange="filterListings()">
        <option value="">All Categories</option>
        <option>Clothing</option><option>Electronics</option><option>Furniture</option>
        <option>Kitchen</option><option>Vehicles</option><option>Books</option><option>Miscellaneous</option>
      </select>
      <select id="fprov" onchange="filterListings()">
        <option value="">All Provinces</option>
        <option>Western Cape</option><option>Gauteng</option><option>KwaZulu-Natal</option>
        <option>Eastern Cape</option><option>Limpopo</option><option>Mpumalanga</option>
        <option>North West</option><option>Free State</option><option>Northern Cape</option>
      </select>
    </div>
    <div class="pgrid" id="listings-grid"></div>
  </div>
</div>

<div class="page" id="p-detail">
  <div class="wrap">
    <button class="btn btn-o btn-s" style="margin-bottom:14px" onclick="pg('listings')">&larr; Back to browse</button>
    <div class="two-col" id="d-content"></div>
    <div style="margin-top:16px" id="d-reviews"></div>
  </div>
</div>

<div class="page" id="p-sell">
  <div class="sec-hdr">
    <h1>Create a New Listing</h1>
    <p>Post your advertisement across South Africa</p>
  </div>
  <div class="wrap-sm">
    <form action="consumer.php" method="POST" class="card">
      <div class="fg"><label>Item Title *</label><input name="title" required placeholder="e.g., Samsung Galaxy S21"></div>
      <div class="frow">
        <div class="fg"><label>Category *</label>
          <select name="category" required>
            <option>Clothing</option><option>Electronics</option><option>Furniture</option>
            <option>Kitchen</option><option>Vehicles</option><option>Books</option><option>Miscellaneous</option>
          </select>
        </div>
        <div class="fg"><label>Condition *</label>
          <select name="condition" required>
            <option>New</option><option>Like new</option><option>Good</option><option>Fair</option>
          </select>
        </div>
      </div>
      <div class="frow">
        <div class="fg"><label>Price (ZAR) *</label><input type="number" step="0.01" name="price" required placeholder="550"></div>
        <div class="fg"><label>Province *</label>
          <select name="province" required>
            <option>Western Cape</option><option>Gauteng</option><option>KwaZulu-Natal</option>
            <option>Eastern Cape</option><option>Limpopo</option><option>Mpumalanga</option>
            <option>North West</option><option>Free State</option><option>Northern Cape</option>
          </select>
        </div>
      </div>
      <div class="fg"><label>Description *</label><textarea name="description" required placeholder="Provide details about the item condition, dimensions, collection criteria..."></textarea></div>
      <button type="submit" name="post_listing_action" class="btn btn-g btn-block">Publish Listing</button>
    </form>
  </div>
</div>

<div class="page" id="p-dashboard">
  <div class="s-hdr">
    <div class="s-hdr-in">
      <div class="s-av" id="ds-av">U</div>
      <div>
        <h2 id="ds-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
        <div class="s-stats">
          <div><span class="ss-n">Verified</span> <span class="ss-l">Status</span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="wrap">
    <div class="tabs">
      <button class="tab on" onclick="switchDashboardTab('ds-listings',this)">My Listings</button>
      <button class="tab" onclick="switchDashboardTab('ds-orders',this)">Purchase History</button>
    </div>
    <div class="card tc on" id="ds-listings" style="border-top:none; padding: 0;">
      <div id="my-listings-list"></div>
    </div>
    <div class="card tc" id="ds-orders" style="border-top:none">
      <div id="my-orders-list"></div>
    </div>
  </div>
</div>

<div class="modal" id="m-msg">
  <div class="modal-box">
    <div class="modal-title"><span>Send Message to Seller</span><button class="modal-x" onclick="closeM('m-msg')">&times;</button></div>
    <div class="fg"><label>Message</label><textarea id="msg-txt" placeholder="Is this item still available?"></textarea></div>
    <div class="modal-foot">
      <button class="btn btn-o btn-s" onclick="closeM('m-msg')">Cancel</button>
      <button class="btn btn-g btn-s" onclick="sendMsg()">Send Message</button>
    </div>
  </div>
</div>

<div class="modal" id="m-checkout">
  <div class="modal-box">
    <div class="modal-title"><span>Secure Escrow Checkout</span><button class="modal-x" onclick="closeM('m-checkout')">&times;</button></div>
    <div class="fg"><label>Delivery Point Options</label>
      <select id="co-delivery">
        <option>Pudo Locker Collection (R60)</option>
        <option>Pep Paxi Counter Point (R59)</option>
        <option>Local In-Person Meetup (Free)</option>
      </select>
    </div>
    <div class="modal-foot">
      <button class="btn btn-o btn-s" onclick="closeM('m-checkout')">Cancel</button>
      <button class="btn btn-g btn-s" onclick="confirmOrder()">Pay securely</button>
    </div>
  </div>
</div>

<footer class="footer">
  <div class="footer-in">
    <div>
      <div class="fl">SureTrade SA</div>
      <div style="font-size:12px">South Africa's secure customer-to-customer ecosystem.</div>
    </div>
    <div class="fc">
      <h4>Navigation</h4>
      <a onclick="pg('listings')">Marketplace</a>
      <a onclick="pg('sell')">Sell</a>
    </div>
  </div>
  <div class="fbot">
    <span>&copy; 2026 SureTrade SA</span>
    <span>Proudly South African</span>
  </div>
</footer>

<script>
var CU = {
    name: "<?php echo $_SESSION['user_name']; ?>",
    email: "<?php echo $_SESSION['user_email']; ?>",
    role: "<?php echo $_SESSION['user_role']; ?>"
};

var LISTINGS = <?php echo json_encode($dbListings); ?>;

function pg(id) {
    document.querySelectorAll('.page').forEach(function(p){p.classList.remove('on');});
    document.querySelectorAll('.nl').forEach(function(n){n.classList.remove('on');});
    var target = document.getElementById('p-'+id);
    if(target) target.classList.add('on');
    
    var navBtn = document.getElementById('nl-'+id);
    if(navBtn) navBtn.classList.add('on');
    
    if(id === 'home') renderHome();
    if(id === 'listings') renderListings(LISTINGS);
    if(id === 'dashboard') renderDashboard();
}

function toggleMob(){document.getElementById('mob-menu').classList.toggle('open');}
function closeMob(){document.getElementById('mob-menu').classList.remove('open');}

function toast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.style.display = 'block';
    setTimeout(function(){t.style.display='none';},2500);
}

function renderHome() {
    var grid = document.getElementById('home-grid');
    if(!grid) return;
    var sub = LISTINGS.slice(0, 4);
    grid.innerHTML = sub.map(function(l){
        return '<div class="pcard" onclick="viewDetail(\''+l.id+'\')">' +
               '<div class="pcard-img">IMAGE</div>' +
               '<div class="pcard-body">' +
               '<div class="pcard-name">'+l.title+'</div>' +
               '<div class="pcard-price">R'+l.price+'</div>' +
               '<div class="pcard-meta">'+l.prov+' &bull; '+l.cat+'</div>' +
               '</div></div>';
    }).join('');
}

function renderListings(arr) {
    var grid = document.getElementById('listings-grid');
    if(!grid) return;
    grid.innerHTML = arr.map(function(l){
        return '<div class="pcard" onclick="viewDetail(\''+l.id+'\')">' +
               '<div class="pcard-img">IMAGE</div>' +
               '<div class="pcard-body">' +
               '<div class="pcard-name">'+l.title+'</div>' +
               '<div class="pcard-price">R'+l.price+'</div>' +
               '<div class="pcard-meta">'+l.prov+' &bull; '+l.cat+'</div>' +
               '</div></div>';
    }).join('');
}

function filterListings() {
    var q = document.getElementById('fq').value.toLowerCase().trim();
    var cat = document.getElementById('fcat').value;
    var prov = document.getElementById('fprov').value;
    var res = LISTINGS.filter(function(l){
        if(q && l.title.toLowerCase().indexOf(q) === -1 && l.desc.toLowerCase().indexOf(q) === -1) return false;
        if(cat && l.cat !== cat) return false;
        if(prov && l.prov !== prov) return false;
        return true;
    });
    renderListings(res);
}

function heroSearch() {
    var q = document.getElementById('hq').value.trim();
    pg('listings');
    document.getElementById('fq').value = q;
    filterListings();
}

function catSearch(cat) {
    pg('listings');
    document.getElementById('fcat').value = cat;
    filterListings();
}

function viewDetail(id) {
    var l = LISTINGS.find(function(x){return x.id == id;});
    if(!l) return;
    
    var isOwner = parseInt(l.user_id) === <?php echo $_SESSION['user_id']; ?>;
    
    pg('detail');
    document.getElementById('d-content').innerHTML = 
        '<div class="card" style="text-align:center;padding:40px 10px;background:#e8e8e8;font-weight:bold">PRODUCT PLACEHOLDER</div>' +
        '<div>' +
        '<h1>'+l.title+'</h1>' +
        '<div class="pcard-price" style="font-size:24px;margin:8px 0">R'+l.price+'</div>' +
        '<div class="card" style="margin-bottom:12px"><strong>Description:</strong><p style="margin-top:4px">'+l.desc+'</p></div>' +
        (isOwner ? '' : '<div class="frow" style="gap:8px;margin-bottom:10px"><button class="btn btn-g" style="flex:1" onclick="buyItem(\''+l.id+'\')">Buy Now</button></div>') +
        '<button class="btn btn-o" style="width:100%" onclick="openM(\'m-msg\')">Message Seller</button>' +
        '</div>';
}

function openM(id){
  document.getElementById(id).classList.add('open');
}
function closeM(id){
  document.getElementById(id).classList.remove('open');
}
function sendMsg(){
  closeM('m-msg');
  toast('Message transmitted securely to seller.');
}
function buyItem(id) {
    fetch('purchase.php?id=' + id)
        .then(response => response.text())
        .then(data => {
            toast('Purchase successful!');
            setTimeout(() => { location.reload(); }, 1000);
        });
}
function renderDashboard() {
    var list = document.getElementById('my-listings-list');
    if (list) {
        var myList = LISTINGS.filter(function(l) {
            return parseInt(l.user_id) === <?php echo $_SESSION['user_id']; ?>;
        });
        
        if (myList.length === 0) {
            list.innerHTML = '<div style="padding:16px;color:#666;text-align:center;">No personal listings discovered. Post an advertisement to display data here.</div>';
        } else {
            list.innerHTML = '<table class="tbl">' +
                '<thead><tr><th>Item Title</th><th>Category</th><th>Price</th><th>Province</th></tr></thead>' +
                '<tbody>' + myList.map(function(l) {
                    return '<tr><td><strong>' + l.title + '</strong></td><td>' + l.cat + '</td><td>R' + l.price + '</td><td>' + l.prov + '</td></tr>';
                }).join('') + '</tbody></table>';
        }
    }

    var orderList = document.getElementById('my-orders-list');
    if (orderList) {
        var orders = <?php echo json_encode($myOrders); ?>;
        
        if (orders.length === 0) {
            orderList.innerHTML = '<div style="padding:16px;color:#666;text-align:center;">No purchase history found.</div>';
        } else {
            orderList.innerHTML = '<table class="tbl">' +
                '<thead><tr><th>Item Title</th><th>Price</th><th>Date</th></tr></thead>' +
                '<tbody>' + orders.map(function(o) {
                    return '<tr><td><strong>' + o.item_title + '</strong></td><td>R' + o.price + '</td><td>' + o.purchase_date + '</td></tr>';
                }).join('') + '</tbody></table>';
        }
    }
}

function switchDashboardTab(id, btn) {
    document.querySelectorAll('.tab').forEach(function(t){t.classList.remove('on');});
    document.querySelectorAll('.tc').forEach(function(c){c.classList.remove('on');});
    btn.classList.add('on');
    document.getElementById(id).classList.add('on');
}

function logout() {
    window.location.href = 'login.php';
}

(function(){
    renderHome();
})();
</script>
</body>
</html>