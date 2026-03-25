<style>
  .faq-tab-row { display:flex; gap:10px; margin-bottom:14px; }
  .faq-tab {
    padding:9px 14px;
    border-radius:10px;
    border:1px solid rgba(255,255,255,0.14);
    color:#cbd5e1;
    text-decoration:none;
    font-size:13px;
    background:rgba(2,6,23,0.5);
  }
  .faq-tab.active {
    color:#fff;
    border-color:rgba(13,148,136,0.6);
    background:rgba(13,148,136,0.22);
  }
  .btn-teal {
    background:#0d9488;
    color:#fff;
    border:0;
    border-radius:10px;
    padding:9px 14px;
    cursor:pointer;
    font-weight:600;
  }
</style>

<div class="page-header">
  <h1 class="page-title">FAQ Items</h1>
  <span class="metric-sub"><?= count($items) ?> total</span>
</div>

<div class="faq-tab-row">
  <a class="faq-tab active" href="/admin/faq">Manage</a>
  <a class="faq-tab" href="/admin/faq/add">Add item</a>
</div>

<?php if (isset($_GET['saved'])): ?>
<div class="flash flash-success">FAQ item saved.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="flash flash-success">FAQ item deleted.</div>
<?php endif; ?>

<div class="data-table" style="margin-bottom:14px;">
  <div class="dt-header" style="grid-template-columns:48px 90px 1fr 100px;">
    <div><input type="checkbox" id="toggleAll"></div><div>Order</div><div>Question</div><div>Action</div>
  </div>

  <?php foreach ($items as $item): ?>
  <div class="dt-row" style="grid-template-columns:48px 90px 1fr 100px;">
    <div><input class="faq-check" type="checkbox" value="<?= (int)$item['id'] ?>"></div>
    <div class="dt-muted"><?= (int)$item['sort_order'] ?></div>
    <div class="dt-name"><?= htmlspecialchars($item['question']) ?></div>
    <div><a href="/admin/faq/<?= (int)$item['id'] ?>" class="action-btn">Edit</a></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="unit-card">
  <form method="POST" id="bulkActionForm" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div style="display:flex;gap:10px;align-items:center;">
      <select id="bulkAction" style="padding:9px;border-radius:10px;border:1px solid rgba(255,255,255,0.14);background:rgba(2,6,23,0.5);color:#fff;">
        <option value="">Select action</option>
        <option value="delete">Delete</option>
      </select>
      <button type="submit" class="btn-teal">Submit</button>
    </div>
    <p class="dt-sub" style="margin-top:10px;">Select one row and use Delete to remove it.</p>
  </form>
</div>

<script>
  const toggleAll = document.getElementById('toggleAll');
  const checks = Array.from(document.querySelectorAll('.faq-check'));

  toggleAll.addEventListener('change', function () {
    checks.forEach(function (checkbox) { checkbox.checked = toggleAll.checked; });
  });

  document.getElementById('bulkActionForm').addEventListener('submit', function (event) {
    event.preventDefault();

    const action = document.getElementById('bulkAction').value;
    const selected = checks.filter(function (checkbox) { return checkbox.checked; });

    if (action !== 'delete' || selected.length !== 1) {
      alert('Please choose Delete and select exactly one FAQ item.');
      return;
    }

    this.action = '/admin/faq/' + selected[0].value + '/delete';
    this.submit();
  });
</script>
