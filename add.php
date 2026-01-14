<?php
require "db.php";
require "auth.php";
require_login();

$pageTitle  = "Record Expenses";
$activeMenu = "add";

function money($n){ return number_format((float)$n, 2); }

$msg = "";
$msgType = "";

/* ========= DELETE ========= */
if (isset($_GET["delete"])) {
  $id = (int)$_GET["delete"];
  $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
  $stmt->execute([$id]);
  header("Location: add.php");
  exit;
}

/* ========= UPDATE (EDIT SAVE) ========= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update") {
  $id       = (int)($_POST["id"] ?? 0);
  $date     = $_POST["expense_date"] ?? date("Y-m-d");
  $amount   = $_POST["amount"] ?? "";
  $category = trim($_POST["category"] ?? "");
  $note     = trim($_POST["note"] ?? "");

  if ($id > 0 && $date && is_numeric($amount) && $amount > 0 && $category !== "") {
    $stmt = $pdo->prepare("UPDATE expenses SET expense_date=?, amount=?, category=?, note=? WHERE id=?");
    $stmt->execute([$date, $amount, $category, $note ?: null, $id]);
    header("Location: add.php");
    exit;
  } else {
    $msg = "Please fill valid values before saving.";
    $msgType = "error";
  }
}

/* ========= ADD ========= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && (!isset($_POST["action"]) || $_POST["action"] === "add")) {
  $date     = $_POST["expense_date"] ?? date("Y-m-d");
  $amount   = $_POST["amount"] ?? "";
  $category = trim($_POST["category"] ?? "");
  $note     = trim($_POST["note"] ?? "");

  if ($date && is_numeric($amount) && $amount > 0 && $category !== "") {
    $stmt = $pdo->prepare("INSERT INTO expenses (expense_date, amount, category, note) VALUES (?, ?, ?, ?)");
    $stmt->execute([$date, $amount, $category, $note ?: null]);
    header("Location: add.php");
    exit;
  } else {
    $msg = "Please fill valid values before adding.";
    $msgType = "error";
  }
}

/* ========= LIST (latest first) ========= */
$stmt = $pdo->query("SELECT id, expense_date, category, amount, note FROM expenses ORDER BY expense_date DESC, id DESC");
$rows = $stmt->fetchAll();

/* ========= CSV EXPORT ========= */
if (isset($_GET["export"]) && $_GET["export"] === "csv") {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=fitness-one-expenses.csv');

  $out = fopen("php://output", "w");
  fputcsv($out, ["Date", "Category", "Amount", "Note"]);
  foreach ($rows as $r) {
    fputcsv($out, [$r["expense_date"], $r["category"], money($r["amount"]), $r["note"] ?? ""]);
  }
  fclose($out);
  exit;
}

ob_start();
?>

<?php if($msg): ?>
  <div class="alert <?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Header Card -->
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
    <div>
      <h3><i class="fa fa-receipt"></i> Record Expenses</h3>
      <div class="small">Add a daily expense record</div>
    </div>

    <a class="btn primary sm" href="add.php?export=csv">
      <i class="fa fa-file-csv"></i> Export CSV
    </a>
  </div>
</div>

<!-- Add Form -->
<div class="table-card" style="margin-bottom:14px;">
  <form method="post" autocomplete="off">
    <input type="hidden" name="action" value="add">
    <div class="form-row">
      <div>
        <input name="category" placeholder="Expense (Category) e.g. Staff, Rent..." required>
      </div>

      <div>
        <input name="amount" type="number" step="0.01" placeholder="Amount" required>
      </div>

      <div>
        <input name="note" placeholder="Note (optional)">
      </div>

      <div>
        <input name="expense_date" type="date" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div>
        <button class="btn primary sm" type="submit"><i class="fa fa-plus"></i> Add</button>
      </div>
    </div>
  </form>
</div>

<!-- Table -->
<div class="table-card">
  <input id="filter" class="filter" placeholder="Filter category..." />

  <div class="table-responsive" style="margin-top:14px;">
    <table class="table-clean" id="expTable">
      <thead>
        <tr>
          <th style="width:70px;">#</th>
          <th style="width:140px;">Date</th>
          <th>Category</th>
          <th class="text-right" style="width:150px;">Amount</th>
          <th>Note</th>
          <th style="width:160px;" class="text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="6" class="small">No expenses yet.</td></tr>
        <?php else: ?>
          <?php foreach($rows as $i => $r): ?>
            <tr
              data-id="<?= (int)$r["id"] ?>"
              data-date="<?= htmlspecialchars($r["expense_date"]) ?>"
              data-category="<?= htmlspecialchars($r["category"]) ?>"
              data-amount="<?= htmlspecialchars($r["amount"]) ?>"
              data-note="<?= htmlspecialchars($r["note"] ?? "") ?>"
            >
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($r["expense_date"]) ?></td>
              <td><?= htmlspecialchars($r["category"]) ?></td>
              <td class="text-right"><?= money($r["amount"]) ?></td>
              <td><?= htmlspecialchars($r["note"] ?? "-") ?></td>

              <td class="text-right" style="white-space:nowrap;">
                <button type="button" class="btn ghost sm js-edit">
                  <i class="fa fa-pen"></i> Edit
                </button>

                <a class="btn red sm" style="text-decoration:none"
                   href="add.php?delete=<?= (int)$r["id"] ?>"
                   onclick="return confirm('Delete this expense?')">
                  Delete
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== EDIT MODAL (same style like your photo) ===== -->
<div class="modal" id="editModal" style="display:none;">
  <div class="modal-content" style="max-width:760px;">
    <div class="modal-header">Edit Expense</div>

    <form method="post" id="editForm" autocomplete="off">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="e_id">

      <div class="modal-body">
        <div class="row" style="margin-top:6px">
          <div class="col">
            <label>Category</label>
            <input name="category" id="e_category" required>
          </div>
          <div class="col">
            <label>Amount</label>
            <input name="amount" id="e_amount" type="number" step="0.01" required>
          </div>
        </div>

        <div class="row" style="margin-top:12px">
          <div class="col">
            <label>Note</label>
            <input name="note" id="e_note" placeholder="optional">
          </div>
        </div>

        <div class="row" style="margin-top:12px">
          <div class="col">
            <label>Date</label>
            <input name="expense_date" id="e_date" type="date" required>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn ghost" id="e_cancel">Cancel</button>
        <button type="submit" class="btn primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
// live filter
document.getElementById('filter')?.addEventListener('input', (e)=>{
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('#expTable tbody tr').forEach(tr=>{
    tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// modal helpers
const modal = document.getElementById('editModal');
const e_id = document.getElementById('e_id');
const e_category = document.getElementById('e_category');
const e_amount = document.getElementById('e_amount');
const e_note = document.getElementById('e_note');
const e_date = document.getElementById('e_date');

function openModal(){
  modal.style.display = 'flex';
}
function closeModal(){
  modal.style.display = 'none';
}

// open edit modal with row data
document.querySelectorAll('.js-edit').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const tr = btn.closest('tr');
    e_id.value = tr.dataset.id;
    e_category.value = tr.dataset.category || '';
    e_amount.value = tr.dataset.amount || '';
    e_note.value = tr.dataset.note || '';
    e_date.value = tr.dataset.date || '';
    openModal();
  });
});

// close modal
document.getElementById('e_cancel').addEventListener('click', closeModal);

// click outside closes
modal.addEventListener('click', (e)=>{
  if(e.target === modal) closeModal();
});
</script>

<?php
$content = ob_get_clean();
include "layout.php";
