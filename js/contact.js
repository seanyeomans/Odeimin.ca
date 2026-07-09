(function () {
  var form = document.getElementById('contact-form');
  var msg = document.getElementById('message');
  var btn = document.getElementById('submit-btn');

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
