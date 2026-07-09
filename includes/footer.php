</main>

<footer>
  <p>&copy; <span id="year"></span> Odeimin.ca</p>
</footer>

<script src="<?= htmlspecialchars(odeimin_asset('/js/site.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php foreach ($extraJs as $src): ?>
<script src="<?= htmlspecialchars(odeimin_asset($src), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</body>
</html>
