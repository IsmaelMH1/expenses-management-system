<?php
require "db.php";
require "auth.php";
require_login();

$pageTitle  = "Expenses Report";
$activeMenu = "monthly";

$month = $_GET["month"] ?? date("Y-m");
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date("Y-m");

$start = $month . "-01";
$end   = date("Y-m-t", strtotime($start));

function money($n){ return number_format((float)$n, 2); }

// totals per category
$stmt = $pdo->prepare("
  SELECT category, COALESCE(SUM(amount),0) AS total
  FROM expenses
  WHERE expense_date BETWEEN ? AND ?
  GROUP BY category
  ORDER BY total DESC
");
$stmt->execute([$start, $end]);
$rows = $stmt->fetchAll();

// month total
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$start, $end]);
$monthTotal = (float)($stmt->fetch()["total"] ?? 0);

ob_start();
?>

<div class="report-shell">
  <div class="report-head">
    <div class="report-titlebox">
      <div class="report-badge"><i class="fa fa-chart-bar"></i> Expenses Report</div>
      <div class="report-sub">Totals per category for selected month</div>
    </div>

    <div class="report-actions">
      <form method="get">
        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>">
        <button class="btn primary round" type="submit"><i class="fa fa-filter"></i> Filter</button>
      </form>

      <button class="btn primary round" id="expCsv"><i class="fa fa-file-csv"></i> Export CSV</button>
      <button class="btn ghost round" id="expPdf"><i class="fa fa-file-pdf"></i> Export PDF</button>
      <button class="btn ghost round" id="expXls"><i class="fa fa-file-excel"></i> Export Excel</button>
    </div>
  </div>

  <div class="inner-panel">
    <input class="filter" id="filter" placeholder="Filter by category..." />

    <div class="table-responsive" style="margin-top:14px;">
      <table class="table-clean" id="reportTable">
        <thead>
          <tr>
            <th style="width:70px;">#</th>
            <th>Category</th>
            <th class="text-right" style="width:180px;">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$rows): ?>
            <tr><td colspan="3" class="small">No expenses for this month.</td></tr>
          <?php else: ?>
            <?php foreach($rows as $i=>$r): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r["category"]) ?></td>
                <td class="text-right"><?= money($r["total"]) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="2">Month Total</th>
            <th class="text-right"><?= money($monthTotal) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<script>

document.getElementById('filter')?.addEventListener('input', (e)=>{
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('#reportTable tbody tr').forEach(tr=>{
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});


function download(content, filename, mime){
  const blob = new Blob([content], {type: mime || 'text/plain;charset=utf-8;'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = filename;
  a.click();
}

const month = "<?= htmlspecialchars($month) ?>";
const monthTotal = "<?= money($monthTotal) ?>";

const rows = <?= json_encode(array_map(function($r){
  return ["category"=>$r["category"], "total"=>(float)$r["total"]];
}, $rows), JSON_UNESCAPED_UNICODE); ?>;

document.getElementById('expCsv')?.addEventListener('click', ()=>{
  let csv = "Category,Total\n";
  rows.forEach(r=>{
    const cat = String(r.category).replaceAll('"','""');
    csv += `"${cat}",${Number(r.total).toFixed(2)}\n`;
  });
  csv += `\nMonth Total,${monthTotal}\n`;
  download(csv, `expenses-report-${month}.csv`, 'text/csv;charset=utf-8;');
});

document.getElementById('expXls')?.addEventListener('click', ()=>{
  let tsv = "Category\tTotal\n";
  rows.forEach(r=>{
    tsv += `${r.category}\t${Number(r.total).toFixed(2)}\n`;
  });
  tsv += `\nMonth Total\t${monthTotal}\n`;
  download(tsv, `expenses-report-${month}.xls`, 'application/vnd.ms-excel');
});

document.getElementById('expPdf')?.addEventListener('click', ()=>{
  const now = new Date().toLocaleString();
  const rowsHtml = rows.map((r,i)=>`
    <tr>
      <td>${i+1}</td>
      <td style="text-align:left">${escapeHtml(r.category)}</td>
      <td style="text-align:right">${Number(r.total).toFixed(2)}</td>
    </tr>
  `).join("");

  const html = `
    <div class="wrap">
      <h1>Expenses Report</h1>
      <div class="sub">Month: ${month} — Generated: ${now}</div>

      <table class="pro-table">
        <thead><tr><th style="width:45px">#</th><th>Category</th><th style="width:120px;text-align:right">Total</th></tr></thead>
        <tbody>${rowsHtml || `<tr><td colspan="3">No data</td></tr>`}</tbody>
        <tfoot><tr><th colspan="2">Month Total</th><th style="text-align:right">${monthTotal}</th></tr></tfoot>
      </table>

      <footer>Fitness One Gym — Expenses • ${now}</footer>
    </div>
  `;
  openPrintWindow(html);
});

function escapeHtml(s){
  return String(s ?? "").replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;");
}
function openPrintWindow(innerHtml){
  const docHtml = `
  <!doctype html><html><head><meta charset="utf-8"><title>Expenses Report</title>
  <style>
    @page{size:A4 portrait;margin:10mm;}
    body{font-family:"Segoe UI",Inter,Arial,sans-serif;color:#222;background:#fff;}
    .wrap{border:1.5px solid #f3d64c;border-radius:12px;padding:16px 18px;background:#fffef8;}
    h1{margin:0 0 6px 0;font-size:18px;color:#1c3879;}
    .sub{margin:0 0 12px 0;font-size:12px;color:#555;}
    table.pro-table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:12px;}
    th,td{border:1px solid #f3d64c;padding:6px 8px;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    th{background:#fff5c2;font-weight:800;color:#333;}
    tbody tr:nth-child(even) td{background:#fffef6;}
    tfoot th{background:#fff2a0;font-weight:900;}
    footer{margin-top:10px;text-align:center;font-size:11px;color:#555;}
    thead{display:table-header-group;} tfoot{display:table-footer-group;} tr{page-break-inside:avoid;}
  </style></head><body>${innerHtml}</body></html>`;
  const w = window.open('', '_blank');
  if(!w){ alert('Pop-up blocked. Allow pop-ups to print.'); return; }
  w.document.open(); w.document.write(docHtml); w.document.close();
  setTimeout(()=>{ w.focus(); w.print(); }, 600);
}
</script>

<?php
$content = ob_get_clean();
include "layout.php";
