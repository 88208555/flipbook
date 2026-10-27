(function () {
 'use strict';
 ['create', 'rename', 'delete'].forEach(function (action) {
  const dialog = document.getElementById('fb-micro-' + action);
  if (!dialog) return;
  const form = dialog.querySelector('form');
  document.querySelectorAll('[data-fb-micro-' + action + ']').forEach(function (button) {
   button.addEventListener('click', function () {
    form.reset();
    form.querySelector('[type=submit]').disabled = false;
    if (action !== 'create') {
     form.elements.key.value = button.dataset.key;
     form.elements.version.value = button.dataset.version;
    }
    if (action === 'rename') form.elements.name.value = button.dataset.name;
    if (action === 'delete') dialog.querySelector('[data-fb-micro-name]').textContent = button.dataset.name;
    dialog.showModal();
    if (form.elements.name) form.elements.name.focus();
   });
  });
  dialog.querySelector('[data-fb-micro-cancel]').addEventListener('click', function () { dialog.close(); });
  form.addEventListener('submit', function () { form.querySelector('[type=submit]').disabled = true; });
 });
 document.querySelectorAll('[data-fb-micro-copy]').forEach(function (button) {
  button.addEventListener('click', async function () {
   try {
    if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(button.dataset.fbMicroCopy);
    else {
     const input = document.createElement('textarea');
     input.value = button.dataset.fbMicroCopy;
     input.style.cssText = 'position:fixed;left:-10000px;top:0';
     document.body.appendChild(input);input.select();
     const copied = document.execCommand('copy');input.remove();
     if (!copied) throw new Error('copy failed');
    }
    button.textContent = '已复制';
   } catch (error) { button.textContent = '复制失败'; }
  });
 });
})();
