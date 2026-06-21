(function () {
  const galleryEl  = document.getElementById('gallery');
  const emptyEl    = document.getElementById('empty');
  const lightbox   = document.getElementById('lightbox');
  const lbImg      = document.getElementById('lightbox-img');
  const lbClose    = document.getElementById('lightbox-close');

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
  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) closeLightbox();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
  });

  fetch('gallery.php')
    .then((r) => r.json())
    .then((images) => {
      if (!images.length) {
        emptyEl.style.display = 'block';
        return;
      }

      images.forEach((img) => {
        const item = document.createElement('div');
        item.className = 'gallery-item';

        const el = document.createElement('img');
        el.src     = img.src;
        el.alt     = img.name;
        el.loading = 'lazy';
        el.addEventListener('click', () => openLightbox(img.src, img.name));

        const badge = document.createElement('span');
        badge.className   = 'avail-label ' + img.subdir;
        badge.textContent = img.available ? 'Available' : 'Unavailable';

        item.appendChild(el);
        item.appendChild(badge);
        galleryEl.appendChild(item);
      });
    })
    .catch(() => {
      emptyEl.textContent = 'Could not load gallery.';
      emptyEl.style.display = 'block';
    });
})();
