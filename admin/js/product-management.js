function setQueryParam(key, val) {
  const url = new URL(window.location.href);
  if (val === null || val === undefined || val === '') url.searchParams.delete(key);
  else url.searchParams.set(key, val);
  window.location.href = url.toString();
}

function resetFilters() {
  const url = new URL(window.location.href);
  url.searchParams.delete('search');
  url.searchParams.delete('status');
  window.location.href = url.toString();
}

function wireCategoryToggles() {
  document.querySelectorAll('.toggle-children').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.target;
      const box = document.getElementById(targetId);
      if (!box) return;
      box.classList.toggle('d-none');
      const icon = btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-caret-right-fill');
        icon.classList.toggle('bi-caret-down-fill');
      }
    });
  });
}

function wireCategorySearch() {
  const input = document.getElementById('category-search');
  if (!input) return;

  input.addEventListener('input', () => {
    const q = input.value.trim().toLowerCase();
    document.querySelectorAll('.category-link').forEach(a => {
      const text = (a.textContent || '').toLowerCase();
      const row = a.closest('li');
      if (!row) return;
      row.style.display = (q === '' || text.includes(q)) ? '' : 'none';
    });
  });
}

async function postForm(url, formData) {
  const res = await fetch(url, { method: 'POST', body: formData });
  const data = await res.json().catch(() => null);
  if (!res.ok || !data || !data.ok) {
    throw new Error((data && data.error) ? data.error : 'Request failed');
  }
  return data;
}

async function deleteProduct(id) {
  if (!confirm('Delete this product?')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  await postForm('product-actions.php', fd);
  window.location.reload();
}

async function editProduct(id) {
  const fd = new FormData();
  fd.append('action', 'get');
  fd.append('id', id);

  const data = await postForm('product-actions.php', fd);
  const p = data.product;

  const form = document.getElementById('addProductForm');
  form.dataset.mode = 'update';

  form.querySelector('[name="id"]').value = p.id;
  form.querySelector('[name="name"]').value = p.name || '';
  form.querySelector('[name="spu"]').value = p.spu || '';
  form.querySelector('[name="category_id"]').value = p.category_id || '';
  form.querySelector('[name="base_price"]').value = p.base_price || '';
  form.querySelector('[name="status"]').value = p.status || 'draft';
  form.querySelector('[name="description"]').value = p.description || '';
  form.querySelector('[name="image_url"]').value = p.image_url || '';
  form.querySelector('[name="stock_quantity"]').value = p.stock_quantity || '0';
  form.querySelector('[name="image"]').value = '';
    // ===== Render variants into table =====
  const tbody = document.querySelector('#variantTable tbody');
  if (tbody) {
    tbody.innerHTML = '';

    if (data.variants && Array.isArray(data.variants) && data.variants.length > 0) {
      data.variants.forEach(v => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>
            <input type="hidden" name="variant_id[]" value="${v.id ?? ''}">
            <input type="text" name="variant_sku[]" class="form-control" value="${(v.sku_code ?? '').replace(/"/g,'&quot;')}" required>
          </td>
          <td>
            <input type="number" step="0.01" min="0" name="variant_price[]" class="form-control" value="${v.price ?? 0}" required>
          </td>
          <td>
            <input type="text" name="variant_image[]" class="form-control" value="${(v.image_url ?? '').replace(/"/g,'&quot;')}">
          </td>
          <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button>
          </td>
        `;
        tbody.appendChild(tr);
      });
    } else {
      // Không có variants -> để trống hoặc bạn có thể auto add 1 row
      // addVariant();
    }
  }

  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addProductModal'));
  modal.show();
}

function viewProduct(id) {
  alert('View product id=' + id);
}

function wireForms() {
  const addCategoryForm = document.getElementById('addCategoryForm');
  if (addCategoryForm) {
    addCategoryForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(addCategoryForm);
      await postForm('category-actions.php', fd);
      window.location.reload();
    });
  }

  const addProductForm = document.getElementById('addProductForm');
  if (addProductForm) {
    addProductForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const mode = addProductForm.dataset.mode || 'create';
      const fd = new FormData(addProductForm);
      fd.append('action', mode);
      
      // Debug log
      console.log('Submitting product with SPU:', fd.get('spu'));
      console.log('Category ID:', fd.get('category_id'));
      
      const data = await postForm('product-actions.php', fd);
      
      // Nếu là create và có category_id, reload về đúng category
      if (mode === 'create' && data.category_id) {
        window.location.href = '?category=' + data.category_id;
      } else {
        window.location.reload();
      }
    });
  }
}

function wireFilters() {
  const search = document.getElementById('product-search');
  const status = document.getElementById('status-filter');

  if (search) {
    search.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        setQueryParam('search', search.value.trim());
      }
    });
  }

  if (status) {
    status.addEventListener('change', () => {
      setQueryParam('status', status.value);
    });
  }

  const resetBtn = document.getElementById('btn-reset');
  if (resetBtn) resetBtn.addEventListener('click', resetFilters);
}

document.addEventListener('DOMContentLoaded', () => {
  wireCategoryToggles();
  wireCategorySearch();
  wireFilters();
  wireForms();
});

// expose global for inline onclick
window.deleteProduct = deleteProduct;
window.editProduct = editProduct;
window.viewProduct = viewProduct;
window.resetFilters = resetFilters;

function resetAddProductForm() {
  const form = document.getElementById('addProductForm');
  const modal = document.getElementById('productModalTitle');
  
  form.reset();
  form.dataset.mode = 'create';
  form.querySelector('[name="id"]').value = '';
  
  // Reset tất cả các input
  form.querySelectorAll('input[type="text"], input[type="number"], input[type="email"], textarea, select').forEach(field => {
    if (field.name !== 'stock_quantity' && field.name !== 'status') {
      field.value = '';
    }
  });
  
  // Set default values
  form.querySelector('[name="status"]').value = 'draft';
  form.querySelector('[name="stock_quantity"]').value = '0';
  
  if (modal) modal.innerText = 'Add Product';
  
  console.log('Form reset for ADD mode');
}

window.resetAddProductForm = resetAddProductForm;

function addVariant() {
    const tbody = document.querySelector('#variantTable tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <input type="hidden" name="variant_id[]" value="">
            <input type="text" name="variant_sku[]" class="form-control" required>
        </td>
        <td>
            <input type="number" step="0.01" name="variant_price[]" class="form-control" required>
        </td>
        <td>
            <input type="text" name="variant_image[]" class="form-control">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button>
        </td>
    `;
    tbody.appendChild(row);
}

function removeRow(btn) {
    btn.closest('tr').remove();
}