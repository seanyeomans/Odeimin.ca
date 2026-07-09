(function () {
  var galleryEl = document.getElementById('gallery');
  var filtersEl = document.getElementById('filters');
  var emptyEl = document.getElementById('empty');
  var lightbox = document.getElementById('lightbox');
  var lbImg = document.getElementById('lightbox-img');
  var lbClose = document.getElementById('lightbox-close');

  function openLightbox(src, alt) {
    lbImg.src = src;
    lbImg.alt = alt;
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightbox.classList.remove('open');
    lbImg.src = '';
    document.body.style.overflow = '';
  }

  lbClose.addEventListener('click', closeLightbox);
  lightbox.addEventListener('click', function (e) {
    if (e.target === lightbox) closeLightbox();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLightbox();
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
