@extends('layouts.admin')
@section('title', 'Grade Distribution')
@section('pageDescription', 'Monitor grade distributions and academic performance across all courses.')
@section('content')
<div class="space-y-6">
    {{-- Header + Export --}}
    <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Grade Management</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900">Grade Distribution</h2>
                <p class="mt-1 text-sm text-slate-500">Academic performance overview across all enrolled courses.</p>
            </div>
            <div class="flex items-center gap-3">
                <form action="{{ route('admin.grades') }}" method="GET" class="flex flex-wrap items-center gap-2">

                    <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer px-2">
                        <input type="checkbox" name="history" value="1" @checked(request()->has('history')) onchange="this.form.submit()" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                        View History
                    </label>
                    <select name="academic_level" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-300 focus:bg-white" onchange="this.form.submit()">
                        <option value="">All Levels</option>
                        <option value="1st Year College" @selected($academicLevelFilter === '1st Year College')>1st Year</option>
                        <option value="2nd Year College" @selected($academicLevelFilter === '2nd Year College')>2nd Year</option>
                        <option value="3rd Year College" @selected($academicLevelFilter === '3rd Year College')>3rd Year</option>
                    </select>
                    <select name="course" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-300 focus:bg-white" onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        <option value="BSIT" @selected($courseFilter === 'BSIT')>BSIT</option>
                        <option value="BSHM" @selected($courseFilter === 'BSHM')>BSHM</option>
                    </select>

                    <select name="grading_period" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-300 focus:bg-white" onchange="this.form.submit()">
                        <option value="">All Periods</option>
                        <option value="PRELIM" @selected(($gradingPeriodFilter ?? '') === 'PRELIM')>PRELIM</option>
                        <option value="MIDTERM" @selected(($gradingPeriodFilter ?? '') === 'MIDTERM')>MIDTERM</option>
                        <option value="FINAL" @selected(($gradingPeriodFilter ?? '') === 'FINAL')>FINAL</option>
                    </select>
                    <select name="subject" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-300 focus:bg-white" onchange="this.form.submit()">
                        <option value="">All Subjects</option>
                        @foreach($subjectOptions as $option)
                            <option value="{{ $option['code'] }}" @selected($subjectFilter === $option['code'])>
                                {{ $option['name'] }} ({{ $option['code'] }})
                            </option>
                        @endforeach
                    </select>
                </form>
                <form action="{{ route('admin.grades.export') }}" method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="academic_level" value="{{ $academicLevelFilter }}">
                    <input type="hidden" name="course" value="{{ $courseFilter }}">
                    <input type="hidden" name="strand" value="{{ $strandFilter ?? '' }}">
                    <input type="hidden" name="subject" value="{{ $subjectFilter }}">
                    <input type="hidden" name="grading_period" value="{{ $gradingPeriodFilter ?? '' }}">
                    <select name="format" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 outline-none transition focus:border-slate-300 focus:bg-white">
                        <option value="csv">Excel / CSV</option>
                        <option value="pdf">Official PDF Record</option>
                    </select>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Generate
                    </button>
                </form>
            </div>
        </div>
    </section>

    {{-- Overall Stats --}}
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach($overview as $o)
            @php $cc=match($o['color'] ?? 'slate'){
                'emerald'=>['bg-emerald-50','border-emerald-200','text-emerald-700'],'sky'=>['bg-sky-50','border-sky-200','text-sky-700'],default=>['bg-white','border-slate-200','text-slate-900']
            }; @endphp
            <div class="rounded-3xl {{ $cc[0] }} border {{ $cc[1] }} shadow-sm p-6">
                <p class="text-xs uppercase tracking-widest font-semibold text-slate-500">{{ $o['label'] }}</p>
                <p class="mt-3 text-4xl font-black {{ $cc[2] }}">{{ $o['value'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-6">Per-Subject Grade Distribution</h3>
        @php
        $gpaColors=[
            '1.00'=>'bg-emerald-600','1.25'=>'bg-emerald-500','1.50'=>'bg-emerald-400',
            '1.75'=>'bg-green-400','2.00'=>'bg-sky-500','2.25'=>'bg-sky-400',
            '2.50'=>'bg-amber-400','2.75'=>'bg-amber-500','3.00'=>'bg-orange-400',
            'Dropped'=>'bg-rose-500',
        ];
        $gpaRanges=[
            '1.00'=>'99–100%','1.25'=>'96–98%','1.50'=>'93–95%',
            '1.75'=>'90–92%','2.00'=>'87–89%','2.25'=>'84–86%',
            '2.50'=>'81–83%','2.75'=>'78–80%','3.00'=>'75–77%',
            'Dropped'=>'Below 75%',
        ];
        @endphp

        <div class="space-y-6">
            @forelse($courses as $course)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                        <div class="flex items-start gap-4">
                            <div>
                                <p class="font-bold text-slate-900">{{ $course['name'] }}</p>
                                <p class="text-xs font-mono text-slate-400 mt-0.5">{{ $course['code'] }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs">
                            <div class="rounded-xl bg-white border border-slate-200 px-3 py-1.5 text-center">
                                <p class="font-black text-emerald-600 text-lg">{{ $course['avg'] }}%</p>
                                <p class="text-slate-400">Average</p>
                            </div>
                            <div class="rounded-xl bg-white border border-slate-200 px-3 py-1.5 text-center">
                                <p class="font-black text-sky-600 text-lg">{{ $course['passing'] }}%</p>
                                <p class="text-slate-400">Passing</p>
                            </div>
                            <div class="rounded-xl bg-white border border-slate-200 px-3 py-1.5 text-center">
                                <p class="font-black text-slate-900 text-lg">{{ $course['highest'] }}</p>
                                <p class="text-slate-400">Highest</p>
                            </div>
                            <div class="rounded-xl bg-white border border-slate-200 px-3 py-1.5 text-center">
                                <p class="font-black text-rose-500 text-lg">{{ $course['lowest'] }}</p>
                                <p class="text-slate-400">Lowest</p>
                            </div>
                        </div>
                    </div>

                    {{-- GPA Distribution Bar --}}
                    @php $total = array_sum($course['dist']); @endphp
                    <div class="space-y-2">
                        @foreach($course['dist'] as $gpa => $count)
                            @php $pct = $total > 0 ? round(($count/$total)*100) : 0; @endphp
                            <div class="flex items-center gap-3">
                                <span class="w-16 text-xs font-black text-slate-600 text-right" title="{{ $gpaRanges[$gpa] ?? '' }}">{{ $gpa }}</span>
                                <div class="flex-1 h-5 rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full rounded-full {{ $gpaColors[$gpa] ?? 'bg-slate-400' }} transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="flex items-center gap-1.5 w-20 text-xs text-right">
                                    <span class="font-bold text-slate-900">{{ $count }}</span>
                                    <span class="text-slate-400">({{ $pct }}%)</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-slate-500">No graded courses found.</div>
            @endforelse
        </div>
    </section>

    {{-- Consolidated Grades Table (All/Pending/Verified) --}}
</div>

    <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6 mt-6">
        <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
            Student Grade Management
            <span class="bg-slate-100 text-slate-700 text-xs px-2 py-0.5 rounded-full">{{ $allGrades->total() }} total</span>
        </h3>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wide">Student Name</th>
                        <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wide">Course / Strand</th>
                        <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wide">Level</th>
                        <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wide">Subject</th>
                        <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wide">Grade</th>
                        <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase tracking-wide text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($allGrades as $grade)
                    <tr class="hover:bg-slate-50 transition-colors">
                        @php $classroom = $classroomMap[$grade->subject_id] ?? null; @endphp
                        <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $grade->student->name ?? 'Unknown' }}</td>
                        <td class="px-5 py-3.5 text-slate-600 font-medium">
                            @if(str_contains($grade->student->academic_level ?? '', 'Senior High School'))
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded text-[10px] uppercase font-bold mr-1">Strand</span>
                                {{ $grade->student->strand ?? 'N/A' }}
                            @else
                                <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] uppercase font-bold mr-1">Course</span>
                                {{ $grade->student->course ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-slate-400">{{ $grade->student->academic_level ?? 'N/A' }}</td>
                        <td class="px-5 py-3.5 text-slate-700">{{ $classroom?->name ?? $grade->subject_id }} <span class="text-xs text-slate-400">({{ $grade->subject_id }})</span></td>
                        <td class="px-5 py-3.5"><span class="font-bold text-slate-900">{{ number_format((float) $grade->average, 2) }}%</span></td>
                        <td class="px-5 py-3.5 flex flex-wrap gap-1">
                            @if($grade->average !== null)
                                <span class="bg-emerald-100 text-emerald-700 text-[10px] uppercase font-bold px-1.5 py-0.5 rounded">Recorded</span>
                                @if($grade->is_overridden)
                                    <span class="bg-purple-100 text-purple-700 text-[10px] uppercase font-bold px-1.5 py-0.5 rounded" title="Manually overridden by Admin">Overridden</span>
                                @endif
                            @else
                                <span class="bg-amber-100 text-amber-700 text-[10px] uppercase font-bold px-1.5 py-0.5 rounded">Pending</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="inline-flex items-center gap-2">
                                <button type="button"
                                    onclick="openEditModal({{ $grade->id }}, {{ $grade->average ?? 0 }}, '{{ e($grade->student->name ?? 'Unknown') }}', '{{ e($classroom->name ?? $grade->subject_id) }}', '{{ e($grade->subject_id) }}')"
                                    class="text-xs font-semibold bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl hover:bg-slate-200 transition">Edit</button>
                                @if($grade->is_overridden)
                                <form action="{{ route('admin.grades.reset', $grade->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reset this grade to its original computed value? This will also allow the grade to be recalculated if the faculty updates component scores.')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-xs font-semibold bg-rose-50 text-rose-600 px-3 py-1.5 rounded-xl hover:bg-rose-100 transition">Reset</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-6 text-center text-slate-500">No grades found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $allGrades->links() }}
        </div>
    </section>
</div>

<!-- Step 1: Edit Grade Modal -->
<div id="editGradeModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-slate-900/50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-3xl shadow-xl w-full max-w-md p-6 animate-modal-in">
        <div class="flex items-center gap-3 mb-5">
            <div class="h-10 w-10 rounded-2xl bg-amber-100 text-amber-600 grid place-items-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Override Student Grade</h3>
                <p class="text-xs text-slate-500">Admin-only action · Logged in Audit Trail</p>
            </div>
        </div>

        {{-- Student context --}}
        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 mb-5">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-xs text-slate-400 font-medium">Student</p>
                    <p class="font-bold text-slate-900" id="modalStudentName">—</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-medium">Subject</p>
                    <p class="font-bold text-slate-900" id="modalSubjectName">—</p>
                    <p class="text-xs text-slate-400 font-mono" id="modalSubjectCode"></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-medium">Current Grade</p>
                    <p class="font-black text-lg text-slate-900" id="modalCurrentGrade">—</p>
                </div>
            </div>
        </div>

        <form id="editGradeForm" method="POST" onsubmit="return false;">
            @csrf
            @method('PATCH')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">New Grade <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" max="100" name="grade_percent" id="modalGradeInput" required
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 font-semibold focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 transition"
                           placeholder="Enter percentage (0–100)">
                    <p class="text-xs text-slate-400 mt-1">Enter the corrected grade as a percentage.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Reason for Override <span class="text-rose-500">*</span></label>
                    <textarea name="reason" id="modalReasonInput" required rows="3" minlength="5" maxlength="500"
                              class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-slate-900 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20 transition"
                              placeholder="e.g., Correction of computational error, faculty request, official adjustment..."></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <p id="reasonError" class="text-xs text-rose-500 font-medium hidden">A valid reason is required to override this grade.</p>
                        <p class="text-xs text-slate-400 ml-auto"><span id="reasonCharCount">0</span>/500</p>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 transition">Cancel</button>
                <button type="button" onclick="showConfirmation()" class="rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700 transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Review Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Step 2: Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 z-[60] flex items-center justify-center hidden bg-slate-900/60 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-3xl shadow-xl w-full max-w-md p-6 animate-modal-in">
        <div class="flex items-center gap-3 mb-5">
            <div class="h-10 w-10 rounded-2xl bg-amber-100 text-amber-600 grid place-items-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Confirm Grade Override</h3>
                <p class="text-xs text-slate-500">Please review the changes below before saving.</p>
            </div>
        </div>

        {{-- Summary --}}
        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 space-y-3">
            <div>
                <p class="text-xs text-slate-400 font-medium">Student</p>
                <p class="font-bold text-slate-900" id="confirmStudentName">—</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Subject</p>
                <p class="font-bold text-slate-900" id="confirmSubjectName">—</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <p class="text-xs text-slate-400 font-medium">Old Grade</p>
                    <p class="font-black text-xl text-slate-400 line-through" id="confirmOldGrade">—</p>
                </div>
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </div>
                <div class="flex-1 text-right">
                    <p class="text-xs text-slate-400 font-medium">New Grade</p>
                    <p class="font-black text-xl text-green-600" id="confirmNewGrade">—</p>
                </div>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">Reason</p>
                <p class="text-sm text-slate-700 mt-1 italic" id="confirmReason">—</p>
            </div>
        </div>

        <div class="mt-4 rounded-2xl bg-amber-50 border border-amber-200 px-4 py-3">
            <p class="text-xs text-amber-800 font-medium flex items-start gap-2">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                This action is irreversible. The override will be recorded in the Audit Trail and reflected across all grade reports and exports.
            </p>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" onclick="backToEdit()" class="px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-900 transition">← Back</button>
            <button type="button" onclick="submitOverride()" id="confirmSubmitBtn" class="rounded-xl bg-green-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-green-700 transition inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Confirm &amp; Save Override
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes modal-in { from { opacity: 0; transform: scale(0.95) translateY(8px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    .animate-modal-in { animation: modal-in 0.2s ease-out; }
</style>

<script>
(function() {
    // State
    let currentRecordId = null;
    let currentOldGrade = 0;
    let currentStudentName = '';
    let currentSubjectName = '';
    let currentSubjectCode = '';

    const editModal = document.getElementById('editGradeModal');
    const confirmModal = document.getElementById('confirmModal');
    const gradeInput = document.getElementById('modalGradeInput');
    const reasonInput = document.getElementById('modalReasonInput');
    const reasonError = document.getElementById('reasonError');
    const reasonCharCount = document.getElementById('reasonCharCount');
    const editForm = document.getElementById('editGradeForm');

    // Character counter for reason
    reasonInput.addEventListener('input', function() {
        reasonCharCount.textContent = this.value.length;
        if (this.value.trim().length >= 5) {
            reasonError.classList.add('hidden');
            this.classList.remove('border-rose-400', 'ring-rose-400/20');
            this.classList.add('border-slate-200');
        }
    });

    // Open the edit modal (Step 1)
    window.openEditModal = function(recordId, currentGrade, studentName, subjectName, subjectCode) {
        currentRecordId = recordId;
        currentOldGrade = currentGrade;
        currentStudentName = studentName;
        currentSubjectName = subjectName;
        currentSubjectCode = subjectCode;

        editForm.action = '/admin/grades/' + recordId + '/update';
        gradeInput.value = '';
        reasonInput.value = '';
        reasonCharCount.textContent = '0';
        reasonError.classList.add('hidden');
        reasonInput.classList.remove('border-rose-400', 'ring-rose-400/20');

        document.getElementById('modalStudentName').textContent = studentName;
        document.getElementById('modalSubjectName').textContent = subjectName;
        document.getElementById('modalSubjectCode').textContent = subjectCode;
        document.getElementById('modalCurrentGrade').textContent = Number(currentGrade).toFixed(2) + '%';

        editModal.classList.remove('hidden');
    };

    // Close the edit modal
    window.closeEditModal = function() {
        editModal.classList.add('hidden');
    };

    // Validate and show confirmation (Step 2)
    window.showConfirmation = function() {
        let valid = true;

        // Validate grade input
        const newGrade = parseFloat(gradeInput.value);
        if (isNaN(newGrade) || newGrade < 0 || newGrade > 100) {
            gradeInput.focus();
            gradeInput.classList.add('border-rose-400');
            valid = false;
        } else {
            gradeInput.classList.remove('border-rose-400');
        }

        // Validate reason — must be non-empty and at least 5 characters
        const reason = reasonInput.value.trim();
        if (reason.length < 5) {
            reasonError.classList.remove('hidden');
            reasonInput.classList.remove('border-slate-200');
            reasonInput.classList.add('border-rose-400', 'ring-rose-400/20');
            reasonInput.focus();
            valid = false;
        } else {
            reasonError.classList.add('hidden');
            reasonInput.classList.remove('border-rose-400', 'ring-rose-400/20');
        }

        if (!valid) return;

        // Populate confirmation modal
        document.getElementById('confirmStudentName').textContent = currentStudentName;
        document.getElementById('confirmSubjectName').textContent = currentSubjectName + ' (' + currentSubjectCode + ')';
        document.getElementById('confirmOldGrade').textContent = Number(currentOldGrade).toFixed(2) + '%';
        document.getElementById('confirmNewGrade').textContent = Number(newGrade).toFixed(2) + '%';
        document.getElementById('confirmReason').textContent = reason;

        // Hide edit, show confirm
        editModal.classList.add('hidden');
        confirmModal.classList.remove('hidden');
    };

    // Back to edit from confirmation
    window.backToEdit = function() {
        confirmModal.classList.add('hidden');
        editModal.classList.remove('hidden');
    };

    // Final submit
    window.submitOverride = function() {
        const btn = document.getElementById('confirmSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Saving...';

        // Submit the form
        editForm.onsubmit = null;
        editForm.submit();
    };

    // Close modals when clicking backdrop
    editModal.addEventListener('click', function(e) { if (e.target === editModal) closeEditModal(); });
    confirmModal.addEventListener('click', function(e) { if (e.target === confirmModal) { backToEdit(); } });

    // ESC key closes modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (!confirmModal.classList.contains('hidden')) { backToEdit(); }
            else if (!editModal.classList.contains('hidden')) { closeEditModal(); }
        }
    });
})();
</script>
@endsection