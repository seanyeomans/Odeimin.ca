(function () {
  var form = document.getElementById('contact-form');
  var msg = document.getElementById('message');
  var btn = document.getElementById('submit-btn');

  // Piece reference: arriving via the gallery's "Ask about this piece"
  // link (/contact?piece=images/...) shows a removable reference card and
  // includes the piece path in the email. Only our own gallery paths are
  // accepted; anything else is ignored.
  var pieceRef = document.getElementById('piece-ref');
  var pieceInput = document.getElementById('piece-input');
  var pieceThumb = document.getElementById('piece-thumb');
  var pieceName = document.getElementById('piece-name');
  var pieceRemove = document.getElementById('piece-remove');

  var piece = new URLSearchParams(window.location.search).get('piece') || '';
  var validPiece = /^images\/(available|unavailable)\/[^?#\\]*\.(jpe?g|png|gif|webp)$/i.test(piece);

  if (validPiece && pieceRef) {
    pieceInput.value = piece;
    pieceThumb.src = '/' + piece;

    // Filename without the upload-timestamp prefix or extension
    var base = decodeURIComponent(piece.split('/').pop());
    pieceName.textContent = base
      .replace(/^\d{8}_\d{6}_/, '')
      .replace(/\.[a-z0-9]+$/i, '')
      .replace(/_/g, ' ');

    pieceRef.style.display = '';

    pieceRemove.addEventListener('click', function () {
      pieceRef.style.display = 'none';
      pieceInput.value = '';
    });
  }

  function showMessage(text, type) {
    msg.textContent = text;
    msg.className = 'message ' + type;
    msg.style.display = 'block';
  }

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    btn.disabled = true;
    btn.textContent = 'Sending...';
    msg.style.display = 'none';

    var data = new FormData(form);
    try {
      var res = await fetch('/contact.php', { method: 'POST', body: data });
      var json = await res.json();
      if (json.ok) {
        showMessage(json.message, 'success');
        form.reset();
        form.style.display = 'none';
      } else {
        showMessage(json.message || 'Could not send message.', 'error');
        btn.disabled = false;
        btn.textContent = 'Send';
      }
    } catch (err) {
      showMessage('Network error - please try again.', 'error');
      btn.disabled = false;
      btn.textContent = 'Send';
    }
  });
})();
