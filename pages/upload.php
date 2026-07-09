<div class="upload-form">
  <div id="message" class="message" style="display:none"></div>

  <form id="upload-form" enctype="multipart/form-data" data-authed="<?= $isAdminAuthed ? '1' : '0' ?>">
    <?php if ($isAdminAuthed): ?>
    <h2>Add a photo</h2>
    <div class="field">
      <label for="photo">Photos</label>
      <input type="file" id="photo" name="photo" accept="image/*" multiple required>
      <button type="button" class="btn secondary camera-btn" id="camera-btn">Take a photo</button>
      <input type="file" id="camera-input" accept="image/*" capture="environment" style="display:none">
    </div>
    <div class="field">
      <label for="category">Category</label>
      <select id="category" name="category" required>
        <?php foreach (odeimin_categories() as $slug => $label): ?>
        <option value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Availability</label>
      <div class="toggle-group">
        <label><input type="radio" name="status" value="available" checked><span>Available</span></label>
        <label><input type="radio" name="status" value="unavailable"><span>Unavailable</span></label>
      </div>
    </div>
    <div class="field">
      <button type="submit" class="btn" id="submit-btn">Upload</button>
    </div>
    <hr class="divider">
    <div class="auth-actions">
      <span class="session-indicator">Session active</span>
      <button type="button" class="btn secondary" id="signout-btn">Sign out</button>
    </div>
    <?php else: ?>
    <h2>Sign in</h2>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <div class="auth-actions">
      <button type="button" class="btn" id="signin-btn">Sign in</button>
    </div>
    <?php endif; ?>
  </form>

  <?php if ($isAdminAuthed): ?>
  <div id="webcam-modal" class="webcam-modal">
    <div class="webcam-panel">
      <video id="webcam-video" autoplay playsinline muted></video>
      <div class="webcam-actions">
        <button type="button" class="btn" id="webcam-snap">Take photo</button>
        <button type="button" class="btn secondary" id="webcam-cancel">Cancel</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="manage-grid" id="manage-grid" style="display:none">
    <h3>Your photos</h3>
    <p class="grid-hint">Tap &times; to delete &middot; tap the coloured label to toggle availability &middot; tap the category to reassign</p>
  </div>
</div>

<script type="application/json" id="category-data"><?= json_encode(odeimin_categories(), JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
