<x-ui.modal id="subjectModal" card-id="subjectModalCard" title-id="subjectModalTitle"
    title="បន្ថែមមុខវិជ្ជាថ្មី" form-id="subjectForm" close-fn="AppModal" max-width="max-w-lg">
    <div class="relative group">
        <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">
            មហាវិទ្យាល័យ (Faculty)
        </label>
        <select required name="faculty_id" id="facultySelect"
            class="w-full px-4 py-2.5 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white focus:ring-4 focus:ring-indigo-500/40 focus:border-indigo-500 focus:bg-white dark:focus:bg-neutral-900 transition-all duration-200 outline-none appearance-none cursor-pointer">
            <option value="" disabled selected hidden>-- ជ្រើសរើសមហាវិទ្យាល័យ --</option>
        </select>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="relative group">
            <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">កូដមុខវិជ្ជា (Code)</label>
            <input required type="text" name="code" autocomplete="off" placeholder="e.g., CS101"
                class="w-full px-4 py-2.5 text-sm font-mono bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white focus:ring-4 focus:ring-indigo-500/40 focus:border-indigo-500 focus:bg-white dark:focus:bg-neutral-900 transition-all duration-200 outline-none">
        </div>
        <div class="relative group">
            <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">ក្រេឌីត (Credit)</label>
            <input type="number" min="0" name="credit" autocomplete="off" placeholder="e.g., 3"
                class="w-full px-4 py-2.5 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white focus:ring-4 focus:ring-indigo-500/40 focus:border-indigo-500 focus:bg-white dark:focus:bg-neutral-900 transition-all duration-200 outline-none">
        </div>
    </div>

    <div class="relative group">
        <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">ឈ្មោះភាសាខ្មែរ (Khmer Name)</label>
        <input required type="text" name="name_kh" autocomplete="off" placeholder="e.g., សេចក្តីផ្តើមអំពីកម្មវិធីកុំព្យូទ័រ"
            class="w-full px-4 py-2.5 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white focus:ring-4 focus:ring-indigo-500/40 focus:border-indigo-500 focus:bg-white dark:focus:bg-neutral-900 transition-all duration-200 outline-none">
    </div>

    <div class="relative group">
        <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">ឈ្មោះភាសាអង់គ្លេស (English Name)</label>
        <input required type="text" name="name_en" autocomplete="off" placeholder="e.g., Introduction to Computer Programming"
            class="w-full px-4 py-2.5 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white focus:ring-4 focus:ring-indigo-500/40 focus:border-indigo-500 focus:bg-white dark:focus:bg-neutral-900 transition-all duration-200 outline-none">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="relative group">
            <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">កម្រិត (Level)</label>
            <select required name="level"
                class="w-full px-4 py-2.5 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white focus:ring-4 focus:ring-indigo-500/40 focus:border-indigo-500 focus:bg-white dark:focus:bg-neutral-900 transition-all duration-200 outline-none appearance-none cursor-pointer">
                <option value="associate">បរិញ្ញាបត្ររង (Associate)</option>
                <option value="bachelor">បរិញ្ញាបត្រ (Bachelor)</option>
                <option value="master">បរិញ្ញាបត្រជាន់ខ្ពស់ (Master)</option>
                <option value="phd">បណ្ឌិត (Doctor/PhD)</option>
            </select>
        </div>
        <div class="relative group">
            <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">ម៉ោងបង្រៀន (Lecturer Hour)</label>
            <input type="number" min="0" name="lecturer_hour" autocomplete="off" placeholder="e.g., 45"
                class="w-full px-4 py-2.5 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white focus:ring-4 focus:ring-indigo-500/40 focus:border-indigo-500 focus:bg-white dark:focus:bg-neutral-900 transition-all duration-200 outline-none">
        </div>
    </div>

    <div class="relative group">
        <label class="block text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider mb-1.5">សម្គាល់ (Remark)</label>
        <textarea name="remark" rows="2" placeholder="Enter optional notes here..."
            class="w-full px-4 py-2.5 text-sm bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-white/10 rounded-xl text-neutral-900 dark:text-white focus:ring-4 focus:ring-indigo-500/40 focus:border-indigo-500 focus:bg-white dark:focus:bg-neutral-900 transition-all duration-200 outline-none resize-none"></textarea>
    </div>

    <x-slot:footer>
        <button type="button" onclick="AppModal.toggle(false)"
            class="px-4 py-2 text-sm font-medium text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-white/5 rounded-xl transition-all duration-200">
            បោះបង់
        </button>
        <button type="submit"
            class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 shadow-md hover:shadow-indigo-500/20 active:scale-95 rounded-xl transition-all duration-200">
            រក្សាទុក
        </button>
    </x-slot:footer>
</x-ui.modal>
