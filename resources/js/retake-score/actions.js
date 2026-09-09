import { CONFIG } from './config.js';
import { state, Toast } from './core.js';

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

/** Opens the Score modal for one registration, pre-filled if a score already exists. */
export function openScoreModal(dom, row) {
    state.scoringId = row.id;

    const student = row.student ?? {};
    if (dom.scoreContext) {
        dom.scoreContext.innerHTML = `${escapeHtml(student.name || student.code || '—')} — ${escapeHtml(row.subject || '—')}`;
    }
    if (dom.scoreValueInput) dom.scoreValueInput.value = row.score ?? '';
    if (dom.scoreRemarkInput) dom.scoreRemarkInput.value = '';

    window.RetakeScoreModal.toggle(true);
}

/** Submits the Score modal form for state.scoringId. */
export async function submitScoreForm(dom, ApiService, onDone) {
    const id = state.scoringId;
    if (!id) return;

    const score = dom.scoreValueInput?.value;
    if (score === '' || score === null || score === undefined) {
        Toast.fire({ icon: 'warning', title: 'សូមបញ្ចូលពិន្ទុ (Please enter a score)' });
        return;
    }

    const { error, data } = await ApiService.request(`${CONFIG.REGISTRATIONS_API}/${id}/score`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            score: Number(score),
            remark: dom.scoreRemarkInput?.value || null,
        }),
    });

    if (error) {
        const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
        Toast.fire({ icon: 'error', title: firstError || data?.message || 'មិនអាចរក្សាទុកបានទេ' });
        return;
    }

    Toast.fire({ icon: 'success', title: 'រក្សាទុកពិន្ទុជោគជ័យ!' });
    window.RetakeScoreModal.toggle(false);
    state.scoringId = null;
    onDone();
}
