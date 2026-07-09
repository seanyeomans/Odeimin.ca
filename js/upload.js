(function () {
  var form = document.getElementById('upload-form');
  var msg = document.getElementById('message');
  var manageGrid = document.getElementById('manage-grid');

  // These exist only in one of the two server-rendered states
  // (see pages/upload.php): signed in gets the upload fields and a
  // sign-out button; signed out gets the password field and sign-in.
  var btn = document.getElementById('submit-btn');
  var signInBtn = document.getElementById('signin-btn');
  var signOutBtn = document.getElementById('signout-btn');
  var pwdField = document.getElementById('password');
  var photoInput = document.getElementById('photo');
  var cameraBtn = document.getElementById('camera-btn');
  var cameraInput = document.getElementById('camera-input');

  var isAuthed = form.dataset.authed === '1';

  // slug => label map rendered by the server from config.php
  var categories = {};
  var categoryData = document.getElementById('category-data');
  if (categoryData) {
    try { categories = JSON.parse(categoryData.textContent); } catch (err) {}
  }

  var openMenu = null;

  function closeCategoryMenu() {
    if (openMenu) {
      openMenu.remove();
      openMenu = null;
    }
  }

  document.addEventListener('click', function (e) {
    if (openMenu && !openMenu.contains(e.target)) {
      closeCategoryMenu();
    }
  });

  function imgSrcFor(img) {
    return 'images/' + img.subdir + '/'
      + (img.category ? encodeURIComponent(img.category) + '/' : '')
      + encodeURIComponent(img.filename);
  }

  function showMessage(text, type) {
    msg.textContent = text;
    msg.className = 'message ' + type;
    msg.style.display = 'block';
  }

  async function checkSession() {
    try {
      var res = await fetch('/admin_session.php', { method: 'GET' });
      var json = await res.json();

      if (Boolean(json.authenticated) !== isAuthed) {
        // Server session state has drifted from what was rendered (session
        // expired, or the page was served from a cache) — reload so PHP
        // re-renders the page to match reality.
        window.location.reload();
      }
    } catch (err) {
      // Network hiccup — keep showing the server-rendered state.
    }
  }

  async function signIn() {
    var password = pwdField ? pwdField.value : '';
    if (!password) {
      showMessage('Enter your password to sign in.', 'error');
      if (pwdField) pwdField.focus();
      return;
    }

    var data = new FormData();
    data.append('action', 'login');
    data.append('password', password);

    try {
      var res = await fetch('/admin_session.php', { method: 'POST', body: data });
      var json = await res.json();
      if (json.ok && json.authenticated) {
        // Reload so the server renders the upload fields for the
        // now-active session, rather than trying to inject them via JS.
        window.location.reload();
        return;
      }
      showMessage(json.message || 'Sign-in failed.', 'error');
    } catch (err) {
      showMessage('Network error - please try again.', 'error');
    }
  }

  async function signOut() {
    var data = new FormData();
    data.append('action', 'logout');

    try {
      await fetch('/admin_session.php', { method: 'POST', body: data });
    } finally {
      window.location.reload();
    }
  }

  // Uploads files one request at a time: the server has a per-request size
  // cap, so batching several phone photos into a single POST would fail,
  // and one bad file shouldn't sink the rest.
  async function uploadFiles(files) {
    var statusInput = form.querySelector('input[name="status"]:checked');
    var categorySelect = document.getElementById('category');

    btn.disabled = true;
    msg.style.display = 'none';

    var uploaded = 0;
    var failed = [];

    for (var i = 0; i < files.length; i++) {
      btn.textContent = files.length > 1
        ? 'Uploading ' + (i + 1) + ' of ' + files.length + '...'
        : 'Uploading...';

      var data = new FormData();
      data.append('photo', files[i]);
      data.append('status', statusInput ? statusInput.value : 'available');
      data.append('category', categorySelect ? categorySelect.value : '');

      try {
        var res = await fetch('/upload.php', { method: 'POST', body: data });
        var json = await res.json();
        if (json.ok) {
          uploaded++;
        } else {
          if (res.status === 401) {
            window.location.reload();
            return;
          }
          failed.push(files[i].name + ' (' + (json.message || 'upload failed') + ')');
        }
      } catch (err) {
        failed.push(files[i].name + ' (network error)');
      }
    }

    btn.disabled = false;
    btn.textContent = 'Upload';

    if (failed.length) {
      var text = uploaded + ' of ' + files.length + ' uploaded. Failed: ' + failed.join('; ');
      showMessage(text, 'error');
    } else {
      showMessage(uploaded === 1 ? 'Photo uploaded!' : uploaded + ' photos uploaded!', 'success');
    }

    if (uploaded) {
      photoInput.value = '';
      loadManageGrid();
    }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!isAuthed) {
      // Signed out, the form only holds the password field, so a submit
      // (Enter in the password box) is a sign-in attempt.
      signIn();
      return;
    }

    if (!photoInput.files.length) {
      showMessage('Choose at least one photo.', 'error');
      return;
    }

    uploadFiles(Array.from(photoInput.files));
  });

  // --- Camera capture -----------------------------------------------------
  // Phones get the native camera via the capture-enabled file input; desktop
  // browsers ignore the capture attribute, so there we open a getUserMedia
  // webcam modal instead (falling back to the file picker if that fails).

  var webcamModal = document.getElementById('webcam-modal');
  var webcamVideo = document.getElementById('webcam-video');
  var webcamSnap = document.getElementById('webcam-snap');
  var webcamCancel = document.getElementById('webcam-cancel');
  var webcamStream = null;

  function isLikelyMobile() {
    // iPadOS Safari reports itself as a Mac; the touch-points check catches it
    return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
      || (navigator.maxTouchPoints > 1 && /Mac/.test(navigator.userAgent));
  }

  function closeWebcam() {
    if (webcamStream) {
      webcamStream.getTracks().forEach(function (t) { t.stop(); });
      webcamStream = null;
    }
    webcamVideo.srcObject = null;
    webcamModal.classList.remove('open');
  }

  async function openWebcam() {
    try {
      webcamStream = await navigator.mediaDevices.getUserMedia({
        video: { width: { ideal: 1920 }, height: { ideal: 1080 } },
        audio: false,
      });
    } catch (err) {
      // No camera, or permission denied - fall back to the file picker
      cameraInput.click();
      return;
    }
    webcamVideo.srcObject = webcamStream;
    webcamModal.classList.add('open');
  }

  function snapWebcam() {
    if (!webcamStream || !webcamVideo.videoWidth) return;

    var canvas = document.createElement('canvas');
    canvas.width = webcamVideo.videoWidth;
    canvas.height = webcamVideo.videoHeight;
    canvas.getContext('2d').drawImage(webcamVideo, 0, 0);
    canvas.toBlob(function (blob) {
      closeWebcam();
      if (!blob) {
        showMessage('Could not capture a photo.', 'error');
        return;
      }
      var file = new File([blob], 'webcam.jpg', { type: 'image/jpeg' });
      uploadFiles([file]);
    }, 'image/jpeg', 0.92);
  }

  if (cameraBtn && cameraInput) {
    cameraBtn.addEventListener('click', function () {
      var canUseWebcam = webcamModal
        && navigator.mediaDevices
        && navigator.mediaDevices.getUserMedia
        && !isLikelyMobile();

      if (canUseWebcam) {
        openWebcam();
      } else {
        cameraInput.click();
      }
    });

    cameraInput.addEventListener('change', function () {
      if (cameraInput.files.length) {
        // A fresh capture uploads right away with the selected
        // category/availability - no extra tap needed.
        uploadFiles(Array.from(cameraInput.files)).then(function () {
          cameraInput.value = '';
        });
      }
    });
  }

  if (webcamModal) {
    webcamSnap.addEventListener('click', snapWebcam);
    webcamCancel.addEventListener('click', closeWebcam);
    webcamModal.addEventListener('click', function (e) {
      if (e.target === webcamModal) closeWebcam();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && webcamModal.classList.contains('open')) closeWebcam();
    });
  }

  function loadManageGrid() {
    fetch('/gallery.php')
      .then(function (r) { return r.json(); })
      .then(function (images) {
        manageGrid.querySelectorAll('.manage-item').forEach(function (el) { el.remove(); });

        if (!images.length) {
          manageGrid.style.display = 'none';
          return;
        }

        manageGrid.style.display = 'grid';

        images.forEach(function (img) {
          var item = document.createElement('div');
          item.className = 'manage-item';

          var thumb = document.createElement('img');
          thumb.src = img.src;
          thumb.alt = img.name;
          thumb.loading = 'lazy';

          var del = document.createElement('button');
          del.className = 'delete-btn';
          del.textContent = 'x';
          del.title = 'Delete ' + img.name;
          del.addEventListener('click', function () { deletePhoto(img, item); });

          var statusBtn = document.createElement('button');
          statusBtn.className = 'status-btn ' + img.subdir;
          statusBtn.textContent = img.available ? 'Available' : 'Unavailable';
          statusBtn.title = img.available
            ? 'Click to mark as Unavailable'
            : 'Click to mark as Available';
          statusBtn.addEventListener('click', function () { toggleStatus(img, statusBtn); });

          var catBtn = document.createElement('button');
          catBtn.className = 'category-btn' + (img.category ? '' : ' uncategorized');
          catBtn.textContent = img.categoryLabel;
          catBtn.title = 'Click to change category';
          catBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            openCategoryMenu(img, item, catBtn);
          });

          item.appendChild(thumb);
          item.appendChild(del);
          item.appendChild(statusBtn);
          item.appendChild(catBtn);
          manageGrid.appendChild(item);
        });
      });
  }

  async function deletePhoto(img, itemEl) {
    if (!confirm('Delete this photo?')) return;

    var data = new FormData();
    data.append('filename', img.filename);
    data.append('subdir', img.subdir);
    data.append('category', img.category || '');

    try {
      var res = await fetch('/delete.php', { method: 'POST', body: data });
      var json = await res.json();
      if (json.ok) {
        itemEl.remove();
        var remaining = manageGrid.querySelectorAll('.manage-item');
        if (!remaining.length) manageGrid.style.display = 'none';
      } else {
        if (res.status === 401) {
          window.location.reload();
          return;
        }
        showMessage(json.message || 'Delete failed.', 'error');
      }
    } catch (err) {
      showMessage('Network error - please try again.', 'error');
    }
  }

  async function toggleStatus(img, btnEl) {
    var newSubdir = img.subdir === 'available' ? 'unavailable' : 'available';

    var data = new FormData();
    data.append('filename', img.filename);
    data.append('from_status', img.subdir);
    data.append('to_status', newSubdir);
    data.append('from_category', img.category || '');
    data.append('to_category', img.category || '');

    try {
      var res = await fetch('/move.php', { method: 'POST', body: data });
      var json = await res.json();
      if (json.ok) {
        img.subdir = newSubdir;
        img.available = newSubdir === 'available';
        img.src = imgSrcFor(img);

        btnEl.className = 'status-btn ' + newSubdir;
        btnEl.textContent = img.available ? 'Available' : 'Unavailable';
        btnEl.title = img.available
          ? 'Click to mark as Unavailable'
          : 'Click to mark as Available';
      } else {
        if (res.status === 401) {
          window.location.reload();
          return;
        }
        showMessage(json.message || 'Could not update status.', 'error');
      }
    } catch (err) {
      showMessage('Network error - please try again.', 'error');
    }
  }

  function openCategoryMenu(img, itemEl, catBtn) {
    // Tapping the label again while its menu is open just closes it
    if (openMenu && openMenu.parentElement === itemEl) {
      closeCategoryMenu();
      return;
    }
    closeCategoryMenu();

    var menu = document.createElement('div');
    menu.className = 'category-menu';

    Object.keys(categories).forEach(function (slug) {
      if (slug === img.category) return;

      var option = document.createElement('button');
      option.type = 'button';
      option.className = 'category-option';
      option.textContent = categories[slug];
      option.addEventListener('click', function () {
        closeCategoryMenu();
        assignCategory(img, slug, catBtn);
      });
      menu.appendChild(option);
    });

    itemEl.appendChild(menu);
    openMenu = menu;
  }

  async function assignCategory(img, newCategory, catBtn) {
    var data = new FormData();
    data.append('filename', img.filename);
    data.append('from_status', img.subdir);
    data.append('to_status', img.subdir);
    data.append('from_category', img.category || '');
    data.append('to_category', newCategory);

    try {
      var res = await fetch('/move.php', { method: 'POST', body: data });
      var json = await res.json();
      if (json.ok) {
        img.category = newCategory;
        img.categoryLabel = categories[newCategory] || newCategory;
        img.src = imgSrcFor(img);

        catBtn.className = 'category-btn';
        catBtn.textContent = img.categoryLabel;
      } else {
        if (res.status === 401) {
          window.location.reload();
          return;
        }
        showMessage(json.message || 'Could not change category.', 'error');
      }
    } catch (err) {
      showMessage('Network error - please try again.', 'error');
    }
  }

  if (signInBtn) signInBtn.addEventListener('click', signIn);
  if (signOutBtn) signOutBtn.addEventListener('click', signOut);

  if (isAuthed) {
    loadManageGrid();
  }
  checkSession();
})();
