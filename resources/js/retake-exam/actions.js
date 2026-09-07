import { CONFIG } from './config.js';
import { Toast } from './core.js';

const OUTCOME_OPTIONS = {
    pending: 'រង់ចាំ (Pending)',
    passed: 'ជាប់ (Passed)',
    failed: 'ធ្លាក់ (Failed)',
    absent: 'អវត្តមាន (Absent)',
};

export async function handleSetOutcome(ApiService, id, currentOutcome, onDone) {
    const { value: outcome } = await Swal.fire({
        title: 'កំណត់លទ្ធផល (Set outcome)',
        input: 'select',
        inputOptions: OUTCOME_OPTIONS,
        inputValue: currentOutcome || 'pending',
        showCancelButton: true,
        confirmButtonText: 'រក្សាទុក (Save)',
        cancelButtonText: 'បោះបង់',
    });
    if (!outcome) return;

    const { error, data } = await ApiService.request(`${CONFIG.REGISTRATIONS_API}/${id}/outcome`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ outcome }),
    });

    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចរក្សាទុកបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'ធ្វើបច្ចុប្បន្នភាពជោគជ័យ!' });
    onDone();
}

export async function handleToggleSelection(ApiService, id, currentlySelected, onDone) {
    const nextValue = !currentlySelected;
    const confirmation = await Swal.fire({
        title: nextValue ? 'ជ្រើសរើសមុខវិជ្ជានេះ? (Select this subject?)' : 'ដកចេញពីការជ្រើសរើស? (Unselect this subject?)',
        text: 'ការផ្លាស់ប្តូរដោយដៃនេះមានផលប៉ះពាល់ដល់ការគិតលុយ (This manual override affects payment).',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'បាទ/ចាស (Yes)',
        cancelButtonText: 'បោះបង់',
    });
    if (!confirmation.isConfirmed) return;

    const { error, data } = await ApiService.request(`${CONFIG.REGISTRATIONS_API}/${id}/selection`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ is_selected: nextValue }),
    });

    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចផ្លាស់ប្តូរបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'ធ្វើបច្ចុប្បន្នភាពជោគជ័យ!' });
    onDone();
}

export async function handleDelete(ApiService, id, onDone) {
    const confirmation = await Swal.fire({
        title: 'តើអ្នកប្រាកដជាចង់លុបមែនទេ?',
        text: 'ទិន្នន័យនេះនឹងផ្លាស់ទៅធុងសំរាម (Moved to trash — can be restored).',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'បាទ/ចាស លុបវា!',
        cancelButtonText: 'បោះបង់',
    });
    if (!confirmation.isConfirmed) return;

    const { error, data } = await ApiService.request(`${CONFIG.REGISTRATIONS_API}/${id}`, { method: 'DELETE' });
    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចលុបបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'លុបទិន្នន័យបានជោគជ័យ!' });
    onDone();
}

export async function handleRestore(ApiService, id, onDone) {
    const { error, data } = await ApiService.request(`${CONFIG.REGISTRATIONS_API}/${id}/restore`, { method: 'PATCH' });
    if (error) {
        Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចស្ដារបានទេ' });
        return;
    }
    Toast.fire({ icon: 'success', title: 'ស្ដារទិន្នន័យបានជោគជ័យ!' });
    onDone();
}
