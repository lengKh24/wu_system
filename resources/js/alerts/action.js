import { API_BASE, state, Toast, toggleLoader } from './core.js';
import { alertCard, emptySection } from './render.js';

// ---------- Modal open/close/reset ----------

function openModal(dom) {
    dom.modal?.classList.remove('invisible', 'opacity-0');
    dom.modal?.classList.add('flex');
    requestAnimationFrame(() => {
        dom.modalCard?.classList.remove('scale-90', 'opacity-0');
        dom.modalCard?.classList.add('scale-100', 'opacity-100');
    });
}

function closeModal(dom) {
    dom.modalCard?.classList.add('scale-90', 'opacity-0');
    dom.modalCard?.classList.remove('scale-100', 'opacity-100');
    setTimeout(() => {
        dom.modal?.classList.add('invisible');
        dom.modal?.classList.remove('flex');
        resetForm(dom);
    }, 300);
}

function resetForm(dom) {
    dom.form?.reset();
    state.isEditMode = false;
    state.editingId = null;
    dom.deleteBtn?.classList.add('hidden');
    if (dom.modalTitle) dom.modalTitle.textContent = 'បង្កើតការជូនដំណឹងថ្មី (New Alert)';
    syncRemindVisibility(dom);
    syncRepeatVisibility(dom);
}

function syncRemindVisibility(dom) {
    dom.remindIntervalWrap?.classList.toggle('hidden', !dom.remindEnabled?.checked);
}

function syncRepeatVisibility(dom) {
    const repeating = dom.repeatType && dom.repeatType.value !== 'none';
    dom.repeatIntervalWrap?.classList.toggle('hidden', !repeating);
    dom.repeatUntilWrap?.classList.toggle('hidden', !repeating);
}

/** Converts a stored minute count back into the friendliest value+unit pair for display. */
function setIntervalFields(dom, minutes) {
    if (!minutes) {
        dom.remindIntervalValue.value = '';
        dom.remindIntervalUnit.value = '1';
        return;
    }
    if (minutes % 1440 === 0) {
        dom.remindIntervalValue.value = minutes / 1440;
        dom.remindIntervalUnit.value = '1440';
    } else if (minutes % 60 === 0) {
        dom.remindIntervalValue.value = minutes / 60;
        dom.remindIntervalUnit.value = '60';
    } else {
        dom.remindIntervalValue.value = minutes;
        dom.remindIntervalUnit.value = '1';
    }
}

/** Computes the actual minutes to submit from the value+unit pair the user picked. */
function syncIntervalMinutes(dom) {
    const value = Number(dom.remindIntervalValue.value) || 0;
    const unit = Number(dom.remindIntervalUnit.value) || 1;
    dom.remindIntervalMinutes.value = dom.remindEnabled.checked && value > 0 ? value * unit : '';
}

function toLocalInputValue(value) {
    if (!value) return '';
    const d = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export function initAlertModal(dom) {
    window.AlertModal = {
        open: () => { resetForm(dom); openModal(dom); },
        close: () => closeModal(dom),
    };
    window.AppModal = window.AlertModal; // shared x-ui.modal header close (X) button convention

    dom.remindEnabled?.addEventListener('change', () => syncRemindVisibility(dom));
    dom.repeatType?.addEventListener('change', () => syncRepeatVisibility(dom));
}

export async function openEditModal(dom, id, currentList) {
    const alert = currentList.find((a) => String(a.id) === String(id));
    if (!alert) return;

    resetForm(dom);
    state.isEditMode = true;
    state.editingId = id;
    if (dom.modalTitle) dom.modalTitle.textContent = 'កែប្រែការជូនដំណឹង (Edit Alert)';

    dom.form.querySelector('[name="title"]').value = alert.title ?? '';
    dom.form.querySelector('[name="sub_title"]').value = alert.sub_title ?? '';
    dom.form.querySelector('[name="content"]').value = alert.content ?? '';
    dom.form.querySelector('[name="category"]').value = alert.category ?? '';
    dom.form.querySelector('[name="status"]').value = alert.status ?? 'pending';
    dom.form.querySelector('[name="note"]').value = alert.note ?? '';
    dom.form.querySelector('[name="start_date"]').value = toLocalInputValue(alert.start_date);
    dom.form.querySelector('[name="end_date"]').value = toLocalInputValue(alert.end_date);

    dom.remindEnabled.checked = !!alert.remind_enabled;
    setIntervalFields(dom, alert.remind_interval_minutes);

    dom.repeatType.value = alert.repeat_type ?? 'none';
    dom.form.querySelector('[name="repeat_interval"]').value = alert.repeat_interval ?? 1;
    dom.form.querySelector('[name="repeat_until"]').value = alert.repeat_until ?? '';

    syncRemindVisibility(dom);
    syncRepeatVisibility(dom);
    dom.deleteBtn?.classList.remove('hidden');
    openModal(dom);
}

export function bindFormSubmit(dom, reload) {
    dom.form?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const invalid = dom.form.querySelectorAll(':invalid');
        if (invalid.length > 0) {
            Toast.fire({ icon: 'warning', title: 'សូមបំពេញព័ត៌មានដែលចាំបាច់' });
            invalid[0].focus();
            return;
        }

        syncIntervalMinutes(dom);

        const formData = new FormData(dom.form);
        const payload = Object.fromEntries(formData.entries());
        payload.remind_enabled = dom.remindEnabled.checked;
        if (!payload.remind_interval_minutes) payload.remind_interval_minutes = null;
        if (!payload.repeat_until) payload.repeat_until = null;

        const url = state.isEditMode ? `${API_BASE}/${state.editingId}` : API_BASE;
        const method = state.isEditMode ? 'PUT' : 'POST';

        toggleLoader(dom, true);
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload),
        });
        toggleLoader(dom, false);

        if (res.ok) {
            closeModal(dom);
            Toast.fire({ icon: 'success', title: state.isEditMode ? 'ធ្វើបច្ចុប្បន្នភាពជោគជ័យ!' : 'បង្កើតជោគជ័យ!' });
            reload();
        } else {
            const body = await res.json().catch(() => ({}));
            const firstError = body.errors ? Object.values(body.errors)[0]?.[0] : null;
            Toast.fire({ icon: 'warning', title: firstError || body.message || 'មានបញ្ហាកើតឡើង' });
        }
    });

    dom.deleteBtn?.addEventListener('click', async () => {
        if (!state.editingId) return;

        const confirmation = await Swal.fire({
            title: 'តើអ្នកប្រាកដជាចង់លុបមែនទេ?',
            text: 'ការជូនដំណឹងនេះនឹងផ្លាស់ទៅធុងសំរាម (Moved to trash — recoverable).',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'បាទ/ចាស លុបវា!',
            cancelButtonText: 'បោះបង់',
        });
        if (!confirmation.isConfirmed) return;

        const res = await fetch(`${API_BASE}/${state.editingId}`, { method: 'DELETE', headers: { Accept: 'application/json' } });
        if (res.ok) {
            closeModal(dom);
            Toast.fire({ icon: 'success', title: 'លុបទិន្នន័យបានជោគជ័យ!' });
            reload();
        } else {
            Toast.fire({ icon: 'error', title: 'មិនអាចលុបទិន្នន័យនេះបានទេ' });
        }
    });
}

// ---------- Quick actions: complete / snooze (delegated on section containers) ----------

export function bindQuickActions(dom, { onEdit, onChange }) {
    const containers = [dom.highSection, dom.mediumSection, dom.laterSection, dom.doneTableBody, dom.searchSection];

    containers.forEach((container) => {
        if (!container) return;

        container.addEventListener('click', async (e) => {
            const editBtn = e.target.closest('[data-action="edit"]');
            const deleteBtn = e.target.closest('[data-action="delete"]');
            const completeBtn = e.target.closest('[data-action="complete"]');
            const toggleSnooze = e.target.closest('[data-toggle-snooze]');
            const snoozeOption = e.target.closest('[data-snooze]');

            if (editBtn) {
                onEdit(editBtn.dataset.id);
                return;
            }

            if (deleteBtn) {
                const confirmation = await Swal.fire({
                    title: 'តើអ្នកប្រាកដជាចង់លុបមែនទេ?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'បាទ/ចាស លុបវា!',
                    cancelButtonText: 'បោះបង់',
                });
                if (!confirmation.isConfirmed) return;

                const res = await fetch(`${API_BASE}/${deleteBtn.dataset.id}`, { method: 'DELETE', headers: { Accept: 'application/json' } });
                if (res.ok) {
                    Toast.fire({ icon: 'success', title: 'លុបទិន្នន័យបានជោគជ័យ!' });
                    onChange();
                }
                return;
            }

            if (completeBtn) {
                const res = await fetch(`${API_BASE}/${completeBtn.dataset.id}/complete`, { method: 'POST', headers: { Accept: 'application/json' } });
                if (res.ok) {
                    const { data } = await res.json();
                    // A repeating alert doesn't move to Done — it re-arms to
                    // its next occurrence and stays pending. Say so, or a
                    // still-visible, still-overdue-looking card reads as a
                    // no-op even though the click worked correctly.
                    if (data?.status === 'pending') {
                        const nextDate = data.start_date
                            ? new Date(data.start_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
                            : '';
                        Toast.fire({ icon: 'success', title: `កំណត់ពេលថ្មី! (Advanced to ${nextDate})` });
                    } else {
                        Toast.fire({ icon: 'success', title: 'ធ្វើរួចរាល់! (Marked complete)' });
                    }
                    onChange();
                }
                return;
            }

            if (toggleSnooze) {
                const menu = toggleSnooze.closest('.snooze-wrap')?.querySelector('[data-snooze-menu]');
                document.querySelectorAll('[data-snooze-menu]').forEach((m) => { if (m !== menu) m.classList.add('hidden'); });
                menu?.classList.toggle('hidden');
                return;
            }

            if (snoozeOption) {
                const res = await fetch(`${API_BASE}/${snoozeOption.dataset.id}/snooze`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ minutes: Number(snoozeOption.dataset.snooze) }),
                });
                if (res.ok) {
                    Toast.fire({ icon: 'success', title: 'ផ្អាកការជូនដំណឹងបានជោគជ័យ! (Snoozed)' });
                    onChange();
                }
            }
        });
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.snooze-wrap')) {
            document.querySelectorAll('[data-snooze-menu]').forEach((m) => m.classList.add('hidden'));
        }
    });
}

// ---------- Data loading ----------

export async function loadDashboard(dom) {
    toggleLoader(dom, true);
    const res = await fetch(`${API_BASE}/dashboard`, { headers: { Accept: 'application/json' } });
    const json = await res.json();
    toggleLoader(dom, false);

    const pending = Array.isArray(json.data) ? json.data : [];

    const high = pending.filter((a) => a.days_until_start <= 1);
    const medium = pending.filter((a) => a.days_until_start > 1 && a.days_until_start <= 3);
    const later = pending.filter((a) => a.days_until_start > 3);

    dom.highSection.innerHTML = high.length ? high.map((a) => alertCard(a, 'high')).join('') : emptySection('គ្មានការជូនដំណឹងបន្ទាន់ទេ (Nothing urgent)');
    dom.mediumSection.innerHTML = medium.length ? medium.map((a) => alertCard(a, 'medium')).join('') : emptySection('គ្មានទេ (Nothing here)');
    dom.laterSection.innerHTML = later.length ? later.map((a) => alertCard(a, 'later')).join('') : emptySection('គ្មានទេ (Nothing here)');

    return pending;
}

export async function loadSearch(dom, keyword) {
    toggleLoader(dom, true);
    const res = await fetch(`${API_BASE}?search=${encodeURIComponent(keyword)}&per_page=100`, { headers: { Accept: 'application/json' } });
    const json = await res.json();
    toggleLoader(dom, false);

    const alerts = Array.isArray(json.data) ? json.data : [];
    dom.searchSection.innerHTML = alerts.length ? alerts.map((a) => alertCard(a, 'neutral')).join('') : emptySection('រកមិនឃើញទេ (No matches)');

    return alerts;
}
