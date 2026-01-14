<?php


if (!isset($pageTitle)) $pageTitle = "Fitness One Gym Expenses";
if (!isset($activeMenu)) $activeMenu = "dashboard";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= htmlspecialchars($pageTitle) ?></title>

  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link rel="stylesheet" href="styles.css"/>
</head>
<body>

<div class="app">
 
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="logo">F1G</div>
      <div>
       <div class="brand-title">
  <?= htmlspecialchars($_SESSION["username"] ?? "User") ?>
</div>

        <div class="brand-sub">Fitness One Gym • Expenses</div>
      </div>
    </div>

    <nav class="nav">
      <a class="nav-link <?= $activeMenu==='dashboard'?'active':'' ?>" href="index.php">
        <span><i class="fa fa-gauge"></i> Dashboard</span>
      </a>

      <a class="nav-link <?= $activeMenu==='add'?'active':'' ?>" href="add.php">
        <span><i class="fa fa-plus"></i> Add Expense</span>
      </a>

      <a class="nav-link <?= $activeMenu==='monthly'?'active':'' ?>" href="monthly.php">
        <span><i class="fa fa-chart-bar"></i> Monthly Report</span>
      </a>

      <hr class="nav-hr">
<a href="logout.php" class="nav-link sidebar-logout">
  <span><i class="fa fa-sign-out-alt"></i> Logout</span>
</a>


    </nav>
  </aside>

 
  <main class="main">
    <header class="header">
      <div class="header-left">
        <button class="hamburger" id="menuBtn" type="button" aria-label="Toggle menu">
          <i class="fa fa-bars"></i>
        </button>
        <div class="header-title"><?= htmlspecialchars($pageTitle) ?></div>
      </div>

      <div class="header-right">
        <div class="header-note">Fitness One Gym • © 2026</div>
      </div>
    </header>

    <div class="container">
      <?= $content ?? "" ?>
    </div>

    <footer class="footer">
      Fitness One Gym — Expenses • <span id="year"></span>
    </footer>
  </main>
</div>

<script src="script.js"></script>
<script>
 
  const btn = document.getElementById("menuBtn");
  const sb = document.getElementById("sidebar");
  btn?.addEventListener("click", ()=> sb.classList.toggle("open"));

 
  const y = document.getElementById("year");
  if(y) y.textContent = new Date().getFullYear();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</body>
</html>
