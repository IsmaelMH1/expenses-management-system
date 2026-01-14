<?php
require "db.php";
require "auth.php";
require_login();

$pageTitle  = "Dashboard";
$activeMenu = "dashboard";

function money($n){ return number_format((float)$n, 2); }

$today = date("Y-m-d");
$thisMonth = date("Y-m");
$monthStart = $thisMonth . "-01";
$monthEnd   = date("Y-m-t");

// KPIs
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE expense_date = ?");
$stmt->execute([$today]);
$todayTotal = (float)($stmt->fetch()["total"] ?? 0);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$monthTotal = (float)($stmt->fetch()["total"] ?? 0);

$stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$monthRecords = (int)($stmt->fetch()["cnt"] ?? 0);

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT category) AS cnt FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$monthStart, $monthEnd]);
$monthCategories = (int)($stmt->fetch()["cnt"] ?? 0);


$year = date("Y");
$monthlyTotals = array_fill(1, 12, 0);

$stmt = $pdo->prepare("
  SELECT MONTH(expense_date) AS m, SUM(amount) AS total
  FROM expenses
  WHERE YEAR(expense_date) = ?
  GROUP BY MONTH(expense_date)
");
$stmt->execute([$year]);
foreach ($stmt->fetchAll() as $r) {
  $monthlyTotals[(int)$r["m"]] = (float)$r["total"];
}

$stmt = $pdo->prepare("
  SELECT category, SUM(amount) AS total
  FROM expenses
  WHERE expense_date BETWEEN ? AND ?
  GROUP BY category
  ORDER BY total DESC
");
$stmt->execute([$monthStart, $monthEnd]);
$categoryRows = $stmt->fetchAll();

ob_start();
?>


<div class="kpis">
  <div class="kpi"><div class="title">This Month Total</div><div class="value"><?= money($monthTotal) ?></div></div>
  <div class="kpi"><div class="title">Today Total</div><div class="value"><?= money($todayTotal) ?></div></div>
  <div class="kpi"><div class="title">Records</div><div class="value"><?= $monthRecords ?></div></div>
  <div class="kpi"><div class="title">Categories</div><div class="value"><?= $monthCategories ?></div></div>
</div>


<div class="card" style="margin-top:12px;">
  <h3>Dashboard</h3>
  <div class="small" style="margin-top:6px;">
    Expenses overview and analytics
  </div>

  <div class="dashboard-charts">

    <div class="chart-card">
      <h4>Expenses by Month (<?= $year ?>)</h4>
      <canvas id="monthlyChart" height="110"></canvas>
    </div>

    <div class="chart-card">
      <h4>Categories (This Month)</h4>
      <canvas id="categoryChart" height="220"></canvas>
    </div>

  </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function () {

 
  if (typeof Chart === "undefined") {
    console.error("Chart.js not loaded!");
    document.getElementById("monthlyChart").parentElement.innerHTML =
      "<div style='padding:20px;color:#e03131;font-weight:800'>Chart.js not loaded. Check layout.php script include.</div>";
    document.getElementById("categoryChart").parentElement.innerHTML =
      "<div style='padding:20px;color:#e03131;font-weight:800'>Chart.js not loaded. Check layout.php script include.</div>";
    return;
  }

  
  const monthlyData = <?= json_encode(array_values($monthlyTotals)) ?>;

  new Chart(document.getElementById("monthlyChart"), {
    type: "bar",
    data: {
      labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
      datasets: [{
        data: monthlyData,
        backgroundColor: "#1c3879",
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true } }
    }
  });


  const catLabels = <?= json_encode(array_column($categoryRows,'category')) ?>;
  const catTotals = <?= json_encode(array_map(fn($r)=>(float)$r["total"], $categoryRows)) ?>;

  
  if (!catLabels.length) {
    document.getElementById("categoryChart").parentElement.innerHTML =
      "<div style='padding:20px;color:#6b7280;font-weight:800'>No expenses this month.</div>";
    return;
  }

  new Chart(document.getElementById("categoryChart"), {
    type: "doughnut",
    data: {
      labels: catLabels,
      datasets: [{
        data: catTotals,
        backgroundColor: [
          "#1c3879","#f59f00","#2b8a3e","#e03131",
          "#7048e8","#0ca678","#fab005","#12b886",
          "#228be6","#fa5252"
        ],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "68%",
      plugins: { legend: { position: "bottom" } }
    }
  });

});
</script>


<?php
$content = ob_get_clean();
include "layout.php";
