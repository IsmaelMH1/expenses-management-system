<?php
require "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST["username"] ?? "");
  $password = $_POST["password"] ?? "";

  $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user["password_hash"])) {
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["username"] = $user["username"];
    header("Location: index.php");
    exit;
  } else {
    $error = "Invalid username or password";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Fitness One Gym — Login</title>

  <style>
    :root{
      --yellow:#f7d000;
      --navy:#1c3879;
      --bg:#0b0f14;
      --card: rgba(20,25,32,.78);
      --line: rgba(255,255,255,.10);
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.65);
    }
    *{box-sizing:border-box;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial;}
    body{
  margin:0;
  min-height:100vh;
  display:grid;
  place-items:center;
  color:var(--text);
  overflow:hidden;
}

.bg{
  position:fixed;
  inset:0;
  background: url("images/gym-bg.jpg") center/cover no-repeat;
  filter: saturate(1.05) contrast(1.05);
}


.bg::after{
  content:"";
  position:absolute;
  inset:0;
  background: linear-gradient(120deg, rgba(0,0,0,.75), rgba(0,0,0,.60));
}


.bg::before{
  content:"";
  position:absolute;
  inset:0;
  background:
    radial-gradient(900px 600px at 20% 60%, rgba(255,255,255,.10), transparent 60%),
    radial-gradient(800px 500px at 80% 55%, rgba(255,255,255,.08), transparent 55%);
}


    
   
   
    .wrap{
      width:min(980px, 92vw);
      display:grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap:22px;
      align-items:center;
      position:relative;
      z-index:2;
    }

    .hero{
      padding:14px 6px;
      animation: floatIn .6s ease both;
    }
    .brand{
      display:flex;
      align-items:center;
      gap:12px;
      margin-bottom:10px;
    }
    .dot{
      width:44px;height:44px;border-radius:14px;
      background: var(--yellow);
      display:grid; place-items:center;
      color:#111;
      font-weight:900;
      box-shadow: 0 18px 50px rgba(247,208,0,.25);
    }
    .brand h1{
      margin:0;
      font-size:32px;
      letter-spacing:.3px;
      font-weight:900;
    }
    .brand p{
      margin:2px 0 0 0;
      color:var(--muted);
      font-weight:700;
    }
    .slogan{
      margin-top:12px;
      color:var(--muted);
      font-size:14px;
      line-height:1.6;
      max-width:520px;
    }

    .card{
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 18px;
      padding: 22px;
      box-shadow: 0 22px 80px rgba(0,0,0,.55);
      backdrop-filter: blur(10px);
      animation: floatIn .75s ease both;
    }
    .title{
      font-size:18px;
      font-weight:900;
      margin:0 0 14px 0;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }
    .pill{
      padding:7px 10px;
      border-radius:999px;
      background: rgba(247,208,0,.14);
      color: var(--yellow);
      font-weight:900;
      font-size:12px;
      border:1px solid rgba(247,208,0,.18);
    }
    label{
      display:block;
      color:var(--muted);
      font-weight:800;
      font-size:12px;
      margin:10px 0 6px;
    }
    input{
      width:100%;
      padding:12px 12px;
      border-radius:12px;
      border:1px solid rgba(255,255,255,.12);
      background: rgba(0,0,0,.25);
      color:var(--text);
      outline:none;
      font-weight:700;
      transition:.2s ease;
    }
    input:focus{
      border-color: rgba(247,208,0,.55);
      box-shadow: 0 0 0 4px rgba(247,208,0,.12);
    }

    .error{
      margin:10px 0 0 0;
      background: rgba(224,49,49,.16);
      border: 1px solid rgba(224,49,49,.25);
      color: rgba(255,255,255,.92);
      padding:10px 12px;
      border-radius:12px;
      font-weight:800;
      font-size:13px;
    }

    .btn{
      width:100%;
      margin-top:14px;
      padding:12px 14px;
      border:none;
      border-radius:12px;
      background: var(--yellow);
      color:#111;
      font-weight:1000;
      cursor:pointer;
      transition:.2s ease;
    }
    .btn:hover{
      transform: translateY(-1px);
      box-shadow: 0 14px 40px rgba(247,208,0,.22);
    }

    .foot{
      margin-top:12px;
      color: rgba(255,255,255,.55);
      font-size:12px;
      text-align:center;
      font-weight:700;
    }

    @keyframes floatIn{
      from{ opacity:0; transform: translateY(10px); }
      to{ opacity:1; transform: translateY(0); }
    }

    @media (max-width: 900px){
      .wrap{ grid-template-columns: 1fr; }
      .hero{ text-align:center; }
      .brand{ justify-content:center; }
      .slogan{ margin-inline:auto; }
    }
  </style>
</head>
<body>
  <div class="bg"></div>

  <div class="wrap">
    <div class="hero">
      <div class="brand">
        <div class="dot">F1G</div>
        <div>
          <h1>Fitness One Gym</h1>
          <p>Expenses System</p>
        </div>
      </div>
      <div class="slogan">
        Secure login to manage daily expenses, track monthly totals, and keep everything organized.
      </div>
    </div>

    <div class="card">
      <div class="title">
        Login
        <span class="pill">Admin Access</span>
      </div>

      <form method="post" autocomplete="off">
        <label>Username</label>
        <input name="username" required placeholder="Enter username" />

        <label>Password</label>
        <input name="password" type="password" required placeholder="Enter password" />

        <?php if($error): ?>
          <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <button class="btn" type="submit">LOGIN</button>
      </form>

      <div class="foot">Fitness One Gym • Developed By Ismael Mohammad Raouf Hijazi • Copyright © 2026</div>
    </div>
  </div>
  
</body>
</html>
