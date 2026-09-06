{{--
    Reusable create/edit modal shell: backdrop, spring-pop card, fixed header
    (title + close X), scrollable form body, fixed footer.

    Usage:
        <x-ui.modal id="stateExamModal" title="Add Exam Room" form-id="stateExamForm">
            ... your <div>/<label>/<input> fields go here, same as before ...

            <x-slot:footer>
                <button type="button" onclick="AppModal.toggle(false)">Cancel</button>
                <button type="submit" form="stateExamForm">Save</button>
            </x-slot:footer>
        </x-ui.modal>

    Props map straight onto the element IDs your JS already toggles
    (openStateExamModal/closeStateExamModal in stateExam-action.js), so
    swapping the markup in doesn't require touching any JS.
--}}
@props([
    'id' => 'modal',
    'cardId' => 'modalCard',
    'titleId' => 'modalTitle',
    'title' => 'Modal',
    'formId' => null,
    'maxWidth' => 'max-w-lg',
    // Global JS object whose .toggle(false) closes this modal — override
    // when a page has more than one modal (each needs its own toggle fn),
    // defaults to the shared 'AppModal' every single-modal page already uses.
    'closeFn' => 'AppModal',
])

<div id="{{ $id }}"
     class="fixed inset-0 z-50 invisible opacity-0 bg-neutral-900/40 dark:bg-black/60 backdrop-blur-sm transition-all duration-300 items-start justify-center overflow-y-auto p-4 py-8">

    <div id="{{ $cardId }}"
         class="w-full {{ $maxWidth }} max-h-[85vh] my-auto flex flex-col bg-white dark:bg-neutral-900 rounded-2xl shadow-2xl border border-neutral-100 dark:border-white/5 transform scale-90 opacity-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] overflow-hidden">

        {{-- Header (fixed) --}}
        <div class="shrink-0 px-6 py-4 border-b border-neutral-100 dark:border-white/5 flex items-center justify-between bg-white dark:bg-neutral-900">
            <h3 id="{{ $titleId }}" class="text-lg font-bold text-neutral-900 dark:text-white">{{ $title }}</h3>
            <button type="button" onclick="{{ $closeFn }}.toggle(false)"
                    class="text-neutral-400 hover:text-neutral-600 dark:hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body (scrolls; header/footer stay put) --}}
        @if ($formId)
            <form id="{{ $formId }}"
                  class="flex-1 min-h-0 p-6 space-y-5 bg-white dark:bg-neutral-900 overflow-y-auto overflow-x-hidden custom-scrollbar">
                {{ $slot }}
            </form>
        @else
            <div class="flex-1 min-h-0 p-6 space-y-5 bg-white dark:bg-neutral-900 overflow-y-auto overflow-x-hidden custom-scrollbar">
                {{ $slot }}
            </div>
        @endif

        {{-- Footer (fixed) --}}
        @isset($footer)
            <div class="shrink-0 flex justify-end items-center gap-3 px-6 py-4 border-t border-neutral-100 dark:border-white/5 bg-white dark:bg-neutral-900">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
