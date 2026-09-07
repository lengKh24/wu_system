function container() {
    return document.getElementById('pagination-container');
}

export function renderPagination(meta) {
    const el = container();
    if (!el) return;

    if (!meta || !meta.total) {
        el.innerHTML = '';
        return;
    }

    const { current_page: current, last_page: last, total, from, to } = meta;

    el.innerHTML = `
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-neutral-500 dark:text-neutral-400">
                កំពុងបង្ហាញ ${from ?? 0}–${to ?? 0} នៃ ${total} (Showing ${from ?? 0}-${to ?? 0} of ${total})
            </p>
            <div class="flex items-center gap-1.5">
                <button type="button" data-page="${current - 1}" ${current <= 1 ? 'disabled' : ''}
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-neutral-200 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-white/5 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    មុន (Prev)
                </button>
                <span class="px-2 text-xs font-medium text-neutral-500 dark:text-neutral-400">ទំព័រ ${current} / ${last}</span>
                <button type="button" data-page="${current + 1}" ${current >= last ? 'disabled' : ''}
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-neutral-200 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-white/5 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                    បន្ទាប់ (Next)
                </button>
            </div>
        </div>`;
}

export function bindPagination(onPageChange) {
    container()?.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-page]');
        if (!btn || btn.disabled) return;

        const page = Number(btn.dataset.page);
        if (page >= 1) onPageChange(page);
    });
}
