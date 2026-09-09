/**
 * Sidebar search — filters the nav list live as you type. The list is a
 * flat <ul> of three kinds of <li>: section headers (data-sidebar-section),
 * collapsible groups with a nested <ul> of links (data-sidebar-group), and
 * plain single links (everything else, always a bare <li><a>...). See
 * resources/views/partials/sidebar.blade.php.
 *
 * Matching is per-link text (bilingual Khmer/English labels like "ជំនាញ
 * (Major)" match on either half). A group hides/shows as a whole based on
 * whether ANY of its own links match, and its individual non-matching links
 * hide too so an opened group only shows what's relevant. A section header
 * hides once every item between it and the next header is hidden. Groups
 * force-opened by a search are restored to whatever they were before
 * (data-sidebar-was-open) once the search is cleared, rather than staying
 * open forever.
 */
export function initSidebarSearch() {
    const input = document.getElementById('sidebarSearchInput');
    const clearBtn = document.getElementById('sidebarSearchClear');
    const list = document.getElementById('sidebarNavList');
    const noResults = document.getElementById('sidebarNoResults');
    if (!input || !list) return;

    function filter(query) {
        const q = query.trim().toLowerCase();
        clearBtn?.classList.toggle('hidden', q === '');

        let anyVisible = false;
        let currentSection = null;
        let sectionHasVisible = false;

        const closeSection = () => {
            if (currentSection) currentSection.classList.toggle('hidden', !sectionHasVisible);
        };

        Array.from(list.children).forEach((li) => {
            if (li === noResults) return;

            if (li.hasAttribute('data-sidebar-section')) {
                closeSection();
                currentSection = li;
                sectionHasVisible = false;
                return;
            }

            let liVisible;

            if (li.hasAttribute('data-sidebar-group')) {
                const links = li.querySelectorAll('ul a');
                liVisible = q === '';
                links.forEach((a) => {
                    const linkLi = a.closest('li');
                    const match = q === '' || a.textContent.trim().toLowerCase().includes(q);
                    linkLi?.classList.toggle('hidden', !match);
                    if (match) liVisible = true;
                });
                li.classList.toggle('hidden', !liVisible);

                const alpineData = window.Alpine?.$data(li);
                if (alpineData) {
                    if (q !== '' && liVisible) {
                        if (li.dataset.sidebarWasOpen === undefined) {
                            li.dataset.sidebarWasOpen = alpineData.open ? '1' : '0';
                        }
                        alpineData.open = true;
                    } else if (q === '' && li.dataset.sidebarWasOpen !== undefined) {
                        alpineData.open = li.dataset.sidebarWasOpen === '1';
                        delete li.dataset.sidebarWasOpen;
                    }
                }
            } else {
                const a = li.querySelector('a');
                const text = (a ? a.textContent : li.textContent).trim().toLowerCase();
                liVisible = q === '' || text.includes(q);
                li.classList.toggle('hidden', !liVisible);
            }

            if (liVisible) {
                anyVisible = true;
                sectionHasVisible = true;
            }
        });
        closeSection();

        noResults?.classList.toggle('hidden', q === '' || anyVisible);
    }

    input.addEventListener('input', () => filter(input.value));
    clearBtn?.addEventListener('click', () => {
        input.value = '';
        input.focus();
        filter('');
    });
}
