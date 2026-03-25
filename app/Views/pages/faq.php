<style>
  .faq-wrap {
    max-width: 900px;
    margin: 0 auto;
    padding: 120px 20px 60px;
  }
  .faq-item {
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 12px;
    margin-bottom: 12px;
    background: rgba(9,14,25,0.72);
    overflow: hidden;
  }
  .faq-q {
    width: 100%;
    text-align: left;
    background: transparent;
    color: #f8fafc;
    border: 0;
    padding: 16px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
  }
  .faq-a {
    display: none;
    padding: 0 16px 16px;
    color: #cbd5e1;
    line-height: 1.7;
  }
  .faq-item.open .faq-a {
    display: block;
  }
</style>

<section class="faq-wrap">
  <p class="section-label">FAQ</p>
  <h1 class="section-title">Frequently Asked Questions</h1>

  <?php if (empty($items)): ?>
    <p class="hero-sub">No FAQ items available yet.</p>
  <?php else: ?>
    <?php foreach ($items as $item): ?>
      <div class="faq-item">
        <button class="faq-q" type="button"><?= htmlspecialchars($item['question']) ?></button>
        <div class="faq-a"><?= $item['answer'] ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<script>
  document.querySelectorAll('.faq-q').forEach(function (button) {
    button.addEventListener('click', function () {
      this.parentElement.classList.toggle('open');
    });
  });
</script>
