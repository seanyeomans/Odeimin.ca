(function () {
  var galleryEl = document.getElementById('gallery');
  var filtersEl = document.getElementById('filters');
  var emptyEl = document.getElementById('empty');
  var lightbox = document.getElementById('lightbox');
  var lbImg = document.getElementById('lightbox-img');
  var lbClose = document.getElementById('lightbox-close');
  var lbContact = document.getElementById('lightbox-contact');

  // --- Lightbox zoom (desktop: wheel to zoom, drag to pan, double-click
  // to toggle; touch devices keep native pinch-zoom) -----------------------
  var zoom = { s: 1, tx: 0, ty: 0 };
  var drag = null;
  var dragMoved = false;

  function applyZoom(animate) {
    lbImg.classList.toggle('zoom-anim', Boolean(animate));
    lbImg.style.transform =
      'translate(' + zoom.tx + 'px,' + zoom.ty + 'px) scale(' + zoom.s + ')';
    lightbox.classList.toggle('zoomed', zoom.s > 1);
  }

  function resetZoom(animate) {
    zoom = { s: 1, tx: 0, ty: 0 };
    applyZoom(animate);
  }

  function clampPan() {
    var maxX = (lbImg.clientWidth * zoom.s) / 2;
    var maxY = (lbImg.clientHeight * zoom.s) / 2;
    zoom.tx = Math.max(-maxX, Math.min(maxX, zoom.tx));
    zoom.ty = Math.max(-maxY, Math.min(maxY, zoom.ty));
  }

  // Rescale around a screen point so whatever is under the cursor stays put
  function zoomTo(newScale, clientX, clientY, animate) {
    var ns = Math.max(1, Math.min(4, newScale));
    if (ns === zoom.s) return;

    var dx = clientX - window.innerWidth / 2;
    var dy = clientY - window.innerHeight / 2;
    zoom.tx = dx - (dx - zoom.tx) * (ns / zoom.s);
    zoom.ty = dy - (dy - zoom.ty) * (ns / zoom.s);
    zoom.s = ns;

    if (zoom.s === 1) {
      zoom.tx = 0;
      zoom.ty = 0;
    }
    clampPan();
    applyZoom(animate);
  }

  function openLightbox(src, alt) {
    lbImg.src = src;
    lbImg.alt = alt;
    lbContact.href = '/contact?piece=' + encodeURIComponent(src);
    resetZoom(false);
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightbox.classList.remove('open');
    lbImg.src = '';
    resetZoom(false);
    document.body.style.overflow = '';
  }

  lbClose.addEventListener('click', closeLightbox);
  lightbox.addEventListener('click', function (e) {
    // A drag that ends over the backdrop still fires a click on the
    // lightbox - don't treat that as a close
    if (e.target === lightbox && !dragMoved) closeLightbox();
    dragMoved = false;
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLightbox();
  });

  lightbox.addEventListener('wheel', function (e) {
    if (!lightbox.classList.contains('open')) return;
    e.preventDefault();
    var factor = e.deltaY < 0 ? 1.18 : 1 / 1.18;
    zoomTo(zoom.s * factor, e.clientX, e.clientY, false);
  }, { passive: false });

  lbImg.addEventListener('dblclick', function (e) {
    e.preventDefault();
    if (zoom.s > 1) {
      resetZoom(true);
    } else {
      zoomTo(2.5, e.clientX, e.clientY, true);
    }
  });

  lbImg.addEventListener('mousedown', function (e) {
    if (zoom.s <= 1) return;
    e.preventDefault();
    drag = { x: e.clientX, y: e.clientY, tx: zoom.tx, ty: zoom.ty };
    dragMoved = false;
    lightbox.classList.add('dragging');
  });

  document.addEventListener('mousemove', function (e) {
    if (!drag) return;
    var dx = e.clientX - drag.x;
    var dy = e.clientY - drag.y;
    if (Math.abs(dx) + Math.abs(dy) > 3) dragMoved = true;
    zoom.tx = drag.tx + dx;
    zoom.ty = drag.ty + dy;
    clampPan();
    applyZoom(false);
  });

  document.addEventListener('mouseup', function () {
    if (drag) {
      drag = null;
      lightbox.classList.remove('dragging');
    }
  });

  function buildFilters(images) {
    // slug => label, in the order they first appear (gallery is sorted
    // newest-first, which is fine for a small pill row)
    var seen = {};
    var order = [];
    images.forEach(function (img) {
      var slug = img.category || '';
      if (!(slug in seen)) {
        seen[slug] = img.categoryLabel;
        order.push(slug);
      }
    });

    // No point showing pills when everything is in one bucket
    if (order.length < 2) return;

    function setFilter(slug, btn) {
      filtersEl.querySelectorAll('.filter-btn').forEach(function (b) {
        b.classList.toggle('active', b === btn);
      });
      galleryEl.querySelectorAll('.gallery-item').forEach(function (item) {
        var show = slug === null || item.dataset.category === slug;
        item.style.display = show ? '' : 'none';
      });
    }

    var allBtn = document.createElement('button');
    allBtn.className = 'filter-btn active';
    allBtn.textContent = 'All';
    allBtn.addEventListener('click', function () { setFilter(null, allBtn); });
    filtersEl.appendChild(allBtn);

    order.forEach(function (slug) {
      var btn = document.createElement('button');
      btn.className = 'filter-btn';
      btn.textContent = seen[slug];
      btn.addEventListener('click', function () { setFilter(slug, btn); });
      filtersEl.appendChild(btn);
    });

    filtersEl.style.display = '';
  }

  fetch('/gallery.php')
    .then(function (r) { return r.json(); })
    .then(function (images) {
      if (!images.length) {
        emptyEl.style.display = 'block';
        return;
      }

      buildFilters(images);

      images.forEach(function (img) {
        var item = document.createElement('div');
        item.className = 'gallery-item';
        item.dataset.category = img.category || '';

        var el = document.createElement('img');
        el.src = img.src;
        el.alt = img.name;
        el.loading = 'lazy';
        el.addEventListener('click', function () { openLightbox(img.src, img.name); });

        var badge = document.createElement('span');
        badge.className = 'avail-label ' + img.subdir;
        badge.textContent = img.available ? 'Available' : 'Unavailable';

        item.appendChild(el);
        item.appendChild(badge);

        if (img.category) {
          var catBadge = document.createElement('span');
          catBadge.className = 'cat-label';
          catBadge.textContent = img.categoryLabel;
          item.appendChild(catBadge);
        }

        galleryEl.appendChild(item);
      });
    })
    .catch(function () {
      emptyEl.textContent = 'Could not load gallery.';
      emptyEl.style.display = 'block';
    });
})();
