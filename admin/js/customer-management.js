  const suspendModal = document.getElementById('suspendModal');
  suspendModal.addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;

    const id = btn.getAttribute('data-id');
    const name = btn.getAttribute('data-name');
    const ret = btn.getAttribute('data-return');
    const csrf = btn.getAttribute('data-csrf');

    document.getElementById('suspend_id').value = id;
    document.getElementById('suspend_csrf').value = csrf;
    document.getElementById('suspend_return').value = ret;
    document.getElementById('suspend_name').textContent = name + ' (ID: ' + id + ')';
  });