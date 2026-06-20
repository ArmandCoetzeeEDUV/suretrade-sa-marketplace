<?php
session_start();
require_once 'db.php';

$message = "";
$active_tab = isset($_POST['register_action']) ? 'reg' : 'login';

if (isset($_POST['register_action'])) {
    $fn = trim($_POST['r_fn']);
    $ln = trim($_POST['r_ln']);
    $email = strtolower(trim($_POST['r_email']));
    $phone = trim($_POST['r_phone']);
    $province = $_POST['r_prov'];
    $pass = $_POST['r_pass'];
    $pass2 = $_POST['r_pass2'];
    
    if (empty($fn) || empty($ln) || empty($email) || empty($province) || empty($pass)) {
        $message = "<div class='err' style='display:block;'>Please fill in all required fields.</div>";
    } elseif ($pass !== $pass2) {
        $message = "<div class='err' style='display:block;'>Passwords do not match.</div>";
    } elseif (strlen($pass) < 8) {
        $message = "<div class='err' style='display:block;'>Password must be at least 8 characters.</div>";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $message = "<div class='err' style='display:block;'>An account with this email already exists.</div>";
        } else {
            $hashed_password = password_hash($pass, PASSWORD_BCRYPT);
            $insert = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, province, phone) VALUES (?, ?, ?, ?, ?, ?)");
            if ($insert->execute([$fn, $ln, $email, $hashed_password, $province, $phone])) {
                $message = "<div class='ok' style='display:block;'>Registration successful! You can now log in.</div>";
                $active_tab = "login"; 
            }
        }
    }
}

if (isset($_POST['login_action'])) {
    $active_tab = "login";
    $email = strtolower(trim($_POST['l_email']));
    $pass = $_POST['l_pass'];
    
    if (empty($email) || empty($pass)) {
        $message = "<div class='err' style='display:block;'>Please enter your email and password.</div>";
    } else {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            
            header("Location: " . ($user['role'] === 'admin' ? "admin.php" : "consumer.php"));
            exit();
        } else {
            $message = "<div class='err' style='display:block;'>Incorrect email or password.</div>";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SureTrade SA - Login</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="page-login">
<div class="box">
  <h1>SureTrade SA</h1>
  <div class="sub">Buy and sell safely across South Africa</div>
  
  <div class="tabs">
    <button class="tab <?php echo $active_tab === 'login' ? 'on' : ''; ?>" onclick="switchTab('login',this)">Login</button>
    <button class="tab <?php echo $active_tab === 'reg' ? 'on' : ''; ?>" onclick="switchTab('reg',this)">Register</button>
  </div>

  <form class="form <?php echo $active_tab === 'login' ? 'on' : ''; ?>" id="f-login" method="POST" action="login.php">
    <?php if ($active_tab === 'login') { echo $message; } ?>
    <div class="fg"><label>Email</label><input type="email" name="l_email" placeholder="you@example.com" required></div>
    <div class="fg"><label>Password</label><input type="password" name="l_pass" placeholder="Password" required></div>
    <button type="submit" name="login_action" class="btn btn-g">Login</button>
    <span class="lnk" onclick="switchTab('reg',document.querySelectorAll('.tab')[1])">No account? Register free</span>
  </form>

  <form class="form <?php echo $active_tab === 'reg' ? 'on' : ''; ?>" id="f-reg" method="POST" action="login.php">
    <?php if ($active_tab === 'reg') { echo $message; } ?>
    <div class="gr">
      <div class="fg"><label>First name *</label><input type="text" name="r_fn" placeholder="Naledi" required></div>
      <div class="fg"><label>Last name *</label><input type="text" name="r_ln" placeholder="Mokoena" required></div>
    </div>
    <div class="fg"><label>Email *</label><input type="email" name="r_email" placeholder="you@example.com" required></div>
    <div class="fg"><label>Phone</label><input type="tel" name="r_phone" placeholder="082 000 0000"></div>
    <div class="fg"><label>Province *</label>
      <select name="r_prov" required>
        <option value="">Select...</option>
        <option value="Western Cape">Western Cape</option>
        <option value="Gauteng">Gauteng</option>
        <option value="KwaZulu-Natal">KwaZulu-Natal</option>
        <option value="Eastern Cape">Eastern Cape</option>
        <option value="Limpopo">Limpopo</option>
        <option value="Mpumalanga">Mpumalanga</option>
        <option value="North West">North West</option>
        <option value="Free State">Free State</option>
        <option value="Northern Cape">Northern Cape</option>
      </select>
    </div>
    <div class="fg"><label>Password *</label><input type="password" name="r_pass" placeholder="Min 8 characters" required></div>
    <div class="fg"><label>Confirm password *</label><input type="password" name="r_pass2" placeholder="Confirm password" required></div>
    <div class="chk-row">
      <input type="checkbox" id="r-terms" required>
      <label for="r-terms">I agree to the SureTrade SA Terms and Privacy Policy</label>
    </div>
    <button type="submit" name="register_action" class="btn btn-y">Create account</button>
    <span class="lnk" onclick="switchTab('login',document.querySelectorAll('.tab')[0])">Already registered? Login</span>
  </form>
</div>
<div class="topbar"></div>

<script>
function switchTab(id,btn){
  document.querySelectorAll('.tab').forEach(function(t){t.classList.remove('on');});
  document.querySelectorAll('.form').forEach(function(f){f.classList.remove('on');});
  btn.classList.add('on');
  document.getElementById('f-'+id).classList.add('on');
}
</script>
</body>
</html>