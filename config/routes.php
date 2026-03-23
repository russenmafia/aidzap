<?php
$router->get('/', function() {
    http_response_code(200);
    echo '<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>aidzap.com</title>
<style>
  body { font-family: monospace; background: #0a0a0a; color: #00ff88;
         display: flex; align-items: center; justify-content: center;
         height: 100vh; margin: 0; }
  .box { text-align: center; }
  .logo { font-size: 2.5rem; font-weight: bold; letter-spacing: .2em; }
  .sub  { color: #666; margin-top: .5rem; font-size: .9rem; }
</style></head>
<body><div class="box">
  <div class="logo">AIDZAP</div>
  <div class="sub">Setup erfolgreich ✓ &nbsp;|&nbsp; PHP ' . PHP_VERSION . '</div>
</div></body></html>';
});
