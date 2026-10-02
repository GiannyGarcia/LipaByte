/**
 * LipaByte Device Inquiries — Firestore CRUD (Week 5)
 * Loads only on inquiries.php. Does not touch MySQL.
 */
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.14.1/firebase-app.js';
import {
  getFirestore,
  collection,
  addDoc,
  getDocs,
  updateDoc,
  deleteDoc,
  doc,
  orderBy,
  query,
  serverTimestamp,
} from 'https://www.gstatic.com/firebasejs/10.14.1/firebase-firestore.js';

const PLACEHOLDER_MARKERS = [
  'PASTE_YOUR_API_KEY',
  'PASTE_YOUR_PROJECT_ID',
  'PASTE_YOUR_APP_ID',
];

const CATEGORIES = [
  'Borrow Help',
  'Listing Help',
  'Account Help',
  'Technical Issue',
  'Other',
];

const STATUSES = ['Open', 'In Progress', 'Resolved', 'Closed'];

const state = {
  items: [],
  sortDir: 'desc',
  filterStatus: '',
  filterCategory: '',
  editingId: null,
};

function $(id) {
  return document.getElementById(id);
}

function configIsReady(cfg) {
  if (!cfg || typeof cfg !== 'object') return false;
  const values = [
    cfg.apiKey,
    cfg.authDomain,
    cfg.projectId,
    cfg.storageBucket,
    cfg.messagingSenderId,
    cfg.appId,
  ];
  if (values.some((v) => !v || String(v).trim() === '')) return false;
  return !PLACEHOLDER_MARKERS.some((marker) =>
    values.some((v) => String(v).includes(marker) || String(v).includes('PASTE_'))
  );
}

function showAlert(message, type) {
  const el = $('inquiryAlert');
  if (!el) return;
  el.hidden = false;
  el.className = `alert alert-${type}`;
  el.textContent = message;
}

function clearAlert() {
  const el = $('inquiryAlert');
  if (!el) return;
  el.hidden = true;
  el.textContent = '';
}

function escapeHtml(value) {
  const div = document.createElement('div');
  div.textContent = value == null ? '' : String(value);
  return div.innerHTML;
}

function formatWhen(value) {
  if (!value) return '—';
  try {
    const date = typeof value.toDate === 'function' ? value.toDate() : new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleString('en-PH', {
      dateStyle: 'medium',
      timeStyle: 'short',
    });
  } catch (err) {
    return '—';
  }
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function getVisibleItems() {
  let rows = state.items.slice();
  if (state.filterStatus) {
    rows = rows.filter((row) => row.status === state.filterStatus);
  }
  if (state.filterCategory) {
    rows = rows.filter((row) => row.category === state.filterCategory);
  }
  rows.sort((a, b) => {
    const aTime = a.createdAtMs || 0;
    const bTime = b.createdAtMs || 0;
    return state.sortDir === 'asc' ? aTime - bTime : bTime - aTime;
  });
  return rows;
}

function renderList() {
  const list = $('inquiryList');
  const empty = $('inquiryEmpty');
  const count = $('inquiryCount');
  if (!list) return;

  const rows = getVisibleItems();
  if (count) {
    count.textContent = `${rows.length} shown · ${state.items.length} total`;
  }

  if (!rows.length) {
    list.innerHTML = '';
    if (empty) empty.hidden = false;
    return;
  }
  if (empty) empty.hidden = true;

  list.innerHTML = rows
    .map((row) => {
      const statusOptions = STATUSES.map(
        (status) =>
          `<option value="${escapeHtml(status)}"${
            row.status === status ? ' selected' : ''
          }>${escapeHtml(status)}</option>`
      ).join('');

      return `
        <article class="inquiry-card card" data-id="${escapeHtml(row.id)}">
          <div class="inquiry-card-head">
            <div>
              <h3>${escapeHtml(row.subject)}</h3>
              <p class="text-muted inquiry-meta">
                ${escapeHtml(row.fullName)} · ${escapeHtml(row.email)} · ${escapeHtml(row.category)}
              </p>
            </div>
            <span class="inquiry-status inquiry-status-${escapeHtml(
              String(row.status || 'Open').toLowerCase().replace(/\s+/g, '-')
            )}">${escapeHtml(row.status)}</span>
          </div>
          <p class="inquiry-message">${escapeHtml(row.message)}</p>
          <p class="inquiry-dates text-muted">Created ${escapeHtml(
            formatWhen(row.createdAt)
          )} · Updated ${escapeHtml(formatWhen(row.updatedAt))}</p>
          <div class="inquiry-actions">
            <label class="inquiry-inline-label">
              Status
              <select class="form-select form-select-sm inquiry-status-select" data-id="${escapeHtml(
                row.id
              )}">${statusOptions}</select>
            </label>
            <button type="button" class="btn btn-sm btn-muted inquiry-edit-btn" data-id="${escapeHtml(
              row.id
            )}">Edit</button>
            <button type="button" class="btn btn-sm btn-danger inquiry-delete-btn" data-id="${escapeHtml(
              row.id
            )}">Delete</button>
          </div>
        </article>
      `;
    })
    .join('');
}

function fillEditForm(row) {
  state.editingId = row.id;
  $('editInquiryId').value = row.id;
  $('editSubject').value = row.subject || '';
  $('editMessage').value = row.message || '';
  $('editCategory').value = row.category || CATEGORIES[0];
  $('editStatus').value = row.status || 'Open';
  $('inquiryEditPanel').hidden = false;
  $('inquiryEditPanel').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function resetEditForm() {
  state.editingId = null;
  const panel = $('inquiryEditPanel');
  if (panel) panel.hidden = true;
  const form = $('inquiryEditForm');
  if (form) form.reset();
}

function resetCreateForm(root) {
  const form = $('inquiryCreateForm');
  if (!form) return;
  form.reset();
  const name = root?.dataset?.userName || '';
  const email = root?.dataset?.userEmail || '';
  if ($('fullName')) $('fullName').value = name;
  if ($('email')) $('email').value = email;
  if ($('status')) $('status').value = 'Open';
}

async function loadInquiries(db) {
  clearAlert();
  const q = query(collection(db, 'inquiries'), orderBy('createdAt', 'desc'));
  const snap = await getDocs(q);
  state.items = snap.docs.map((item) => {
    const data = item.data();
    const created = data.createdAt;
    let createdAtMs = 0;
    try {
      createdAtMs =
        created && typeof created.toMillis === 'function'
          ? created.toMillis()
          : created
            ? new Date(created).getTime()
            : 0;
    } catch (err) {
      createdAtMs = 0;
    }
    return {
      id: item.id,
      fullName: data.fullName || '',
      email: data.email || '',
      category: data.category || 'Other',
      subject: data.subject || '',
      message: data.message || '',
      status: data.status || 'Open',
      createdAt: data.createdAt || null,
      updatedAt: data.updatedAt || null,
      createdAtMs: Number.isFinite(createdAtMs) ? createdAtMs : 0,
      lipabyteUserId: data.lipabyteUserId || '',
      lipabyteUserName: data.lipabyteUserName || '',
    };
  });
  renderList();
}

function validateCreatePayload(payload) {
  const errors = [];
  if (!payload.fullName || payload.fullName.length < 2) {
    errors.push('Please enter your full name.');
  }
  if (!isValidEmail(payload.email)) {
    errors.push('Please enter a valid email address.');
  }
  if (!CATEGORIES.includes(payload.category)) {
    errors.push('Please choose a valid category.');
  }
  if (!payload.subject || payload.subject.length < 3) {
    errors.push('Subject must be at least 3 characters.');
  }
  if (!payload.message || payload.message.length < 10) {
    errors.push('Message must be at least 10 characters.');
  }
  if (!STATUSES.includes(payload.status)) {
    errors.push('Please choose a valid status.');
  }
  return errors;
}

async function main() {
  const root = $('inquiriesApp');
  if (!root) return;

  const setupNote = $('inquirySetupNote');
  const cfg = window.LIPABYTE_FIREBASE || {};

  if (!configIsReady(cfg)) {
    if (setupNote) setupNote.hidden = false;
    showAlert(
      'Inquiries are temporarily unavailable. Please try again later.',
      'warning'
    );
    return;
  }
  if (setupNote) setupNote.hidden = true;

  let app;
  let db;
  try {
    app = initializeApp(cfg);
    db = getFirestore(app);
  } catch (err) {
    showAlert('Could not start the inquiries service. Please try again later.', 'error');
    console.error(err);
    return;
  }

  resetCreateForm(root);

  try {
    await loadInquiries(db);
  } catch (err) {
    showAlert(
      'Could not load inquiries. Please refresh the page and try again.',
      'error'
    );
    console.error(err);
  }

  $('filterStatus')?.addEventListener('change', (event) => {
    state.filterStatus = event.target.value;
    renderList();
  });
  $('filterCategory')?.addEventListener('change', (event) => {
    state.filterCategory = event.target.value;
    renderList();
  });
  $('sortDir')?.addEventListener('change', (event) => {
    state.sortDir = event.target.value === 'asc' ? 'asc' : 'desc';
    renderList();
  });
  $('refreshInquiries')?.addEventListener('click', async () => {
    try {
      await loadInquiries(db);
      showAlert('Inquiry list refreshed.', 'success');
    } catch (err) {
      showAlert('Refresh failed. Please try again.', 'error');
      console.error(err);
    }
  });

  $('inquiryCreateForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearAlert();

    const payload = {
      fullName: ($('fullName')?.value || '').trim(),
      email: ($('email')?.value || '').trim(),
      category: $('category')?.value || '',
      subject: ($('subject')?.value || '').trim(),
      message: ($('message')?.value || '').trim(),
      status: $('status')?.value || 'Open',
      lipabyteUserId: root.dataset.userId || '',
      lipabyteUserName: root.dataset.userName || '',
    };

    const errors = validateCreatePayload(payload);
    if (errors.length) {
      showAlert(errors[0], 'error');
      return;
    }

    const submitBtn = event.target.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      await addDoc(collection(db, 'inquiries'), {
        ...payload,
        createdAt: serverTimestamp(),
        updatedAt: serverTimestamp(),
      });
      resetCreateForm(root);
      await loadInquiries(db);
      showAlert('Your inquiry was submitted successfully.', 'success');
    } catch (err) {
      showAlert('Could not submit your inquiry. Please try again.', 'error');
      console.error(err);
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });

  $('inquiryList')?.addEventListener('change', async (event) => {
    const select = event.target.closest('.inquiry-status-select');
    if (!select) return;
    const id = select.dataset.id;
    const status = select.value;
    if (!id || !STATUSES.includes(status)) return;

    try {
      await updateDoc(doc(db, 'inquiries', id), {
        status,
        updatedAt: serverTimestamp(),
      });
      await loadInquiries(db);
      showAlert('Status updated.', 'success');
    } catch (err) {
      showAlert('Could not update status. Please try again.', 'error');
      console.error(err);
      await loadInquiries(db);
    }
  });

  $('inquiryList')?.addEventListener('click', async (event) => {
    const editBtn = event.target.closest('.inquiry-edit-btn');
    const deleteBtn = event.target.closest('.inquiry-delete-btn');

    if (editBtn) {
      const row = state.items.find((item) => item.id === editBtn.dataset.id);
      if (row) fillEditForm(row);
      return;
    }

    if (deleteBtn) {
      const id = deleteBtn.dataset.id;
      if (!id) return;
      const ok = window.confirm('Delete this inquiry? This cannot be undone.');
      if (!ok) return;
      try {
        await deleteDoc(doc(db, 'inquiries', id));
        if (state.editingId === id) resetEditForm();
        await loadInquiries(db);
        showAlert('Inquiry deleted.', 'success');
      } catch (err) {
        showAlert('Could not delete this inquiry. Please try again.', 'error');
        console.error(err);
      }
    }
  });

  $('inquiryEditCancel')?.addEventListener('click', () => {
    resetEditForm();
  });

  $('inquiryEditForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const id = ($('editInquiryId')?.value || '').trim();
    if (!id) return;

    const payload = {
      subject: ($('editSubject')?.value || '').trim(),
      message: ($('editMessage')?.value || '').trim(),
      category: $('editCategory')?.value || '',
      status: $('editStatus')?.value || 'Open',
    };

    if (payload.subject.length < 3) {
      showAlert('Subject must be at least 3 characters.', 'error');
      return;
    }
    if (payload.message.length < 10) {
      showAlert('Message must be at least 10 characters.', 'error');
      return;
    }
    if (!CATEGORIES.includes(payload.category) || !STATUSES.includes(payload.status)) {
      showAlert('Invalid category or status.', 'error');
      return;
    }

    const submitBtn = event.target.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      await updateDoc(doc(db, 'inquiries', id), {
        ...payload,
        updatedAt: serverTimestamp(),
      });
      resetEditForm();
      await loadInquiries(db);
      showAlert('Inquiry updated.', 'success');
    } catch (err) {
      showAlert('Could not save your changes. Please try again.', 'error');
      console.error(err);
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  });
}

main();
