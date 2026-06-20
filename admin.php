<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_listing'])) {
    $stmt = $conn->prepare("DELETE FROM listings WHERE id = ?");
    $stmt->execute([intval($_POST['listing_id'])]);
    header("Location: admin.php?msg=deleted"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_listing'])) {
    $stmt = $conn->prepare("UPDATE listings SET title = ?, price = ?, category = ? WHERE id = ?");
    $stmt->execute([$_POST['title'], $_POST['price'], $_POST['category'], $_POST['listing_id']]);
    header("Location: admin.php?msg=updated"); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_user'])) {
    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, role = ? WHERE id = ?");
    $stmt->execute([$_POST['fname'], $_POST['lname'], $_POST['role'], $_POST['user_id']]);
    header("Location: admin.php?msg=updated"); exit();
}

$count_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$count_listings = $conn->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$db_listings = $conn->query("SELECT id, title, price, category FROM listings ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$db_users = $conn->query("SELECT id, first_name, last_name, email, role FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SureTrade SA - Admin Management</title>
<link rel="stylesheet" href="style.css">
<style>
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; }
.modal-box { background:white; margin:10% auto; padding:20px; width:300px; border-radius:8px; }
</style>
</head>
<body class="page-admin">
<div class="layout">
  <aside class="sb">
    <div class="sb-hdr">SureTrade SA Portal</div>
    <div class="sb-menu">
      <button class="sbi on" id="sbi-dash" onclick="switchPage('dash')">Overview</button>
      <button class="sbi" id="sbi-users" onclick="switchPage('users')">Manage Users</button>
      <button class="sbi" id="sbi-listings" onclick="switchPage('listings')">Manage Listings</button>
      <button class="sbi" onclick="window.location.href='consumer.php'">Return to Shop</button>
      <button class="sbi" style="color:#ff8888;" onclick="window.location.href='admin.php?action=logout'">Logout</button>
    </div>
  </aside>

  <main class="main">
    <div class="wrap">
      <section class="page on" id="p-dash">
        <h1>Dashboard</h1>
        <div class="mgrid"><div class="mcard"><?php echo $count_users; ?> Users</div><div class="mcard y"><?php echo $count_listings; ?> Listings</div></div>
      </section>

      <section class="page" id="p-users">
        <h1>Manage Users</h1>
        <table class="tbl">
          <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach($db_users as $u): ?>
            <tr><td>#<?php echo $u['id']; ?></td><td><?php echo $u['first_name'].' '.$u['last_name']; ?></td><td><?php echo strtoupper($u['role']); ?></td>
            <td><button class="btn-o" onclick="openModal('user', <?php echo $u['id']; ?>, '<?php echo addslashes($u['first_name']); ?>', '<?php echo addslashes($u['last_name']); ?>', '<?php echo $u['role']; ?>')">Edit</button></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <section class="page" id="p-listings">
        <h1>Manage Listings</h1>
        <table class="tbl">
          <thead><tr><th>Title</th><th>Price</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach($db_listings as $l): ?>
            <tr><td><?php echo $l['title']; ?></td><td>R<?php echo $l['price']; ?></td>
            <td><button class="btn-o" onclick="openModal('listing', <?php echo $l['id']; ?>, '<?php echo addslashes($l['title']); ?>', '<?php echo $l['price']; ?>', '<?php echo addslashes($l['category']); ?>')">Edit</button></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </div>
  </main>
</div>

<div id="m-edit" class="modal">
    <div class="modal-box">
        <h3>Edit</h3>
        <form method="POST" id="edit-form">
            <input type="hidden" name="" id="hidden-id">
            <div id="modal-fields"></div><br>
            <button type="submit" name="" id="submit-btn" class="btn-g">Save</button>
            <button type="button" onclick="document.getElementById('m-edit').style.display='none'">Cancel</button>
        </form>
    </div>
</div>

<script>
function switchPage(id) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('on'));
    document.getElementById('p-'+id).classList.add('on');
}
function openModal(type, id, v1, v2, v3) {
    document.getElementById('m-edit').style.display = 'block';
    const fields = document.getElementById('modal-fields');
    if(type === 'listing') {
        document.getElementById('hidden-id').name = 'listing_id';
        document.getElementById('hidden-id').value = id;
        document.getElementById('submit-btn').name = 'action_update_listing';
        fields.innerHTML = `Title: <input name="title" value="${v1}"><br>Price: <input name="price" value="${v2}"><br>Cat: <input name="category" value="${v3}">`;
    } else {
        document.getElementById('hidden-id').name = 'user_id';
        document.getElementById('hidden-id').value = id;
        document.getElementById('submit-btn').name = 'action_update_user';
        fields.innerHTML = `First: <input name="fname" value="${v1}"><br>Last: <input name="lname" value="${v2}"><br>Role: <select name="role"><option value="user">USER</option><option value="admin">ADMIN</option></select>`;
    }
}
</script>
</body>
</html>
