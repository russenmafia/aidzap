<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.6/quill.snow.css">

<div class="page-header">
  <h1 class="page-title">Edit: <?= htmlspecialchars($page['title']) ?></h1>
  <a href="/admin/legal" class="btn-ghost-sm">Go back</a>
</div>

<?php if (isset($_GET['saved'])): ?>
<div class="flash flash-success">Page saved.</div>
<?php endif; ?>

<div class="unit-card">
  <form method="POST" action="/admin/legal/<?= urlencode($page['slug']) ?>" id="legalForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <div class="form-group" style="margin-bottom:14px;">
      <label class="dt-sub" for="title" style="display:block;margin-bottom:8px;">Title</label>
      <input id="title" name="title" type="text" value="<?= htmlspecialchars($page['title']) ?>" style="width:100%;padding:10px;border:1px solid rgba(255,255,255,0.15);border-radius:10px;background:rgba(0,0,0,0.2);color:#fff;">
    </div>

    <div class="form-group" style="margin-bottom:14px;">
      <label class="dt-sub" style="display:block;margin-bottom:8px;">Content</label>
      <div id="editor" style="height:320px;background:#fff;color:#111;"><?= $page['content'] ?></div>
      <input type="hidden" name="content" id="contentField">
    </div>

    <button type="submit" class="btn-approve">Save</button>
  </form>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
  const legalQuill = new Quill('#editor', {
    theme: 'snow'
  });

  document.getElementById('legalForm').addEventListener('submit', function () {
    document.getElementById('contentField').value = legalQuill.root.innerHTML;
  });
</script>
