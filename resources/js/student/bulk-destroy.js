/**
 * Bulk hard-delete for the Student list — TEMPORARY, for clearing test data
 * during setup. Hide/remove studentDestroyAllBtn (student/index.blade.php)
 * once the project is done. Same typed-confirmation pattern as Subject's
 * and Lecturer's Destroy All (resources/js/subject/subject.js,
 * resources/js/lecturer/lecturer.js).
 */
import { getById, baseUri } from '../app';
import { Toast } from './core.js';

const BULK_DESTROY_URL = baseUri('students-bulk-destroy');

export function initStudentBulkDestroy(ApiService, reloadList) {
    const destroyAllBtn = getById('studentDestroyAllBtn');
    if (!destroyAllBtn) return;

    destroyAllBtn.addEventListener('click', async () => {
        const confirmation = await Swal.fire({
            title: 'លុបនិស្សិតទាំងអស់ជាអចិន្ត្រៃយ៍?',
            html: 'សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។ វាយ <b>DELETE</b> ដើម្បីបញ្ជាក់។<br><span class="text-xs text-neutral-400">This permanently deletes ALL students and cannot be undone. Type DELETE to confirm.</span>',
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'DELETE',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'លុបទាំងអស់ (Destroy All)',
            cancelButtonText: 'បោះបង់',
            preConfirm: (value) => {
                if (value !== 'DELETE') {
                    Swal.showValidationMessage('Type DELETE exactly to confirm.');
                    return false;
                }
                return true;
            },
        });

        if (!confirmation.isConfirmed) return;

        const { error, data } = await ApiService.request(BULK_DESTROY_URL, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ all: true }),
        });

        if (!error) {
            Toast.fire({ icon: 'success', title: data?.message || 'លុបទិន្នន័យទាំងអស់បានជោគជ័យ!' });
            reloadList();
        } else {
            Toast.fire({ icon: 'error', title: data?.message || 'មិនអាចលុបទិន្នន័យទាំងអស់បានទេ' });
        }
    });
}
