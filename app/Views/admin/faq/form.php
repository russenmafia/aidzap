<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.6/quill.snow.css">

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
    text-decoration:none;
    display:inline-block;
  }
  .btn-back {
    color:#cbd5e1;
    text-decoration:none;
    border:1px solid rgba(255,255,255,0.18);
    border-radius:10px;
    padding:9px 14px;
  }
</style>

<?php
  $isEdit = ($mode ?? 'add') === 'edit';
  $action = $isEdit ? '/admin/faq/' . (int)$item['id'] : '/admin/faq/add';
?>

<div class="page-header">
  <h1 class="page-title"><?= $isEdit ? 'Edit FAQ Item' : 'Add FAQ Item' ?></h1>
  <span class="metric-sub"><?= $isEdit ? 'Manage / Edit' : 'Manage / Add item' ?></span>
</div>

<div class="faq-tab-row">
  <a class="faq-tab" href="/admin/faq">Manage</a>
  <a class="faq-tab active" href="/admin/faq/add">Add item</a>
</div>

<?php if (isset($_GET['error'])): ?>
<div class="flash flash-error">Question and answer are required.</div>
<?php endif; ?>

<div class="unit-card">
  <form method="POST" action="<?= $action ?>" id="faqForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div style="margin-bottom:14px;">
      <label for="question" class="dt-sub" style="display:block;margin-bottom:8px;">Question</label>
      <input id="question" name="question" type="text" value="<?= htmlspecialchars($item['question'] ?? '') ?>" required style="width:100%;padding:10px;border:1px solid rgba(255,255,255,0.15);border-radius:10px;background:rgba(0,0,0,0.2);color:#fff;">
    </div>

    <div style="margin-bottom:14px;">
      <label class="dt-sub" style="display:block;margin-bottom:8px;">Answer</label>
      <div id="faqEditor" style="height:280px;background:#fff;color:#111;"><?= $item['answer'] ?? '' ?></div>
      <input type="hidden" name="answer" id="answerField">
    </div>

    <div style="margin-bottom:16px;max-width:180px;">
      <label for="sort_order" class="dt-sub" style="display:block;margin-bottom:8px;">Order</label>
      <input id="sort_order" name="sort_order" type="number" min="1" value="<?= (int)($item['sort_order'] ?? 1) ?>" style="width:100%;padding:10px;border:1px solid rgba(255,255,255,0.15);border-radius:10px;background:rgba(0,0,0,0.2);color:#fff;">
    </div>

    <div style="display:flex;gap:10px;align-items:center;">
      <button type="submit" class="btn-teal"><?= $isEdit ? 'Save' : 'Create' ?></button>
      <a href="/admin/faq" class="btn-back">Go back</a>
    </div>
  </form>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
  const faqQuill = new Quill('#faqEditor', {
    theme: 'snow'
  });

  document.getElementById('faqForm').addEventListener('submit', function () {
    document.getElementById('answerField').value = faqQuill.root.innerHTML;
  });
</script>
