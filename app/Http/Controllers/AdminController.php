<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Models\Announcement;
use App\Models\AuditTrail;
use App\Models\Classroom;
use App\Models\DocumentRequest;
use App\Models\FacultyAttendanceRecord;
use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\Grade;
use App\Models\StudentModuleRecord;
use App\Models\User;
use App\Services\GradingService;
use App\Services\StudentBulkImportService;
use App\Services\SystemSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        // Dynamic dashboard metrics
        $totalUsers = User::count();
        $activeTeachers = User::where('role', 'faculty')->where('status', 'active')->count();
        $activeStudents = User::where('role', 'student')->where('status', 'active')->count();
        $pendingRequests = DocumentRequest::where('status', 'Pending')->count();

        $summary = [
            ['label' => 'Total Users', 'value' => (string) $totalUsers, 'url' => route('admin.users')],
            ['label' => 'Active Teachers', 'value' => (string) $activeTeachers, 'url' => route('admin.users', ['status' => 'active', 'role' => 'faculty'])],
            ['label' => 'Active Students', 'value' => (string) $activeStudents, 'url' => route('admin.users', ['status' => 'active', 'role' => 'student'])],
            ['label' => 'Pending Requests', 'value' => (string) $pendingRequests, 'url' => route('admin.documents', ['status' => 'Pending'])],
        ];

        $totalClassrooms = Classroom::count();
        $totalCourses = User::where('role', 'student')
            ->whereIn('course', ['BSIT', 'BSHM'])
            ->distinct('course')
            ->count('course');
        $totalAnnouncements = Announcement::count();

        $overview = [
            ['title' => 'Total Courses', 'value' => (string) $totalCourses],
            ['title' => 'Active Classrooms', 'value' => (string) $totalClassrooms],
            ['title' => 'Total Announcements', 'value' => (string) $totalAnnouncements],
        ];

        $recentActions = [
            ['title' => 'Total Registered Users', 'subtitle' => $totalUsers.' users in the system'],
            ['title' => 'Status', 'subtitle' => 'System is running smoothly'],
            ['title' => 'System Health', 'subtitle' => 'All systems operational'],
        ];

        $pendingUsersCount = User::where('status', 'pending')->count();

        // Live analytics for dashboard
        $levelStats = collect(['1st Year College', '2nd Year College', '3rd Year College'])
            ->map(fn ($level) => ['label' => $level, 'count' => User::where('role', 'student')->where('academic_level', $level)->count()])
            ->all();

        $strandStats = []; // Removed SHS strands

        $courseStats = User::where('role', 'student')
            ->whereNotNull('course')
            ->where('course', '!=', '')
            ->select('course as label', DB::raw('count(*) as count'))
            ->groupBy('course')
            ->get()
            ->all();

        return view('admin.dashboard', compact(
            'summary',
            'overview',
            'recentActions',
            'pendingUsersCount',
            'levelStats',
            'strandStats',
            'courseStats'
        ));
    }

    public function users(): View
    {
        $roleFilter = request('role', '');
        $statusFilter = request('status', '');
        $search = request('search', '');

        $query = User::query()
            ->when($roleFilter, fn ($q) => $q->where('role', $roleFilter))
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter))
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }));

        $filtered = $query->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'enrollment_type' => $user->enrollment_type,
                'receipt_proof' => $user->receipt_proof,
                'student_id_proof' => $user->student_id_proof,
                'joined' => $user->created_at ? $user->created_at->format('M j, Y') : 'N/A',
            ];
        })->all();

        $stats = [
            'total' => User::count(),
            'students' => User::where('role', 'student')->count(),
            'faculty' => User::where('role', 'faculty')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'pending' => User::where('status', 'pending')->count(),
        ];

        return view('admin.users', compact('filtered', 'stats', 'roleFilter', 'statusFilter', 'search'));
    }

    public function toggleUserStatus(Request $request, User $user): RedirectResponse
    {
        $status = $request->input('status');

        if (! $status) {
            $status = ($user->status === 'active') ? 'inactive' : 'active';
        }

        $user->update(['status' => $status]);

        AuditTrail::log('Update', 'Users', 'Admin updated status of '.$user->name.' to '.$status);

        return back()->with('status', "User {$user->name} has been updated to {$status}.");
    }

    public function downloadStudentTemplate(): StreamedResponse
    {
        $filename = 'student-import-template-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            $headers = ['Student Number', 'Full Name', 'Email', 'Academic Level', 'Course'];
            fputcsv($output, $headers);

            $examples = [
                ['STU-001', 'Juan Dela Cruz', 'juan.delacruz@school.edu', '1st Year College', 'BSIT'],
                ['STU-002', 'Maria Santos', 'maria.santos@school.edu', '2nd Year College', 'BSHM'],
            ];
            foreach ($examples as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadAdminTemplate(): StreamedResponse
    {
        $filename = 'admin-import-template-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            $headers = ['Admin unique number', 'Full Name', 'Email', 'Department'];
            fputcsv($output, $headers);

            $examples = [
                ['ADM-001', 'System Admin One', 'admin.one@school.edu', 'IT Department'],
                ['ADM-002', 'Registrar Admin', 'registrar@school.edu', 'Registrar'],
            ];
            foreach ($examples as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadFacultyTemplate(): StreamedResponse
    {
        $filename = 'faculty-import-template-'.now()->format('Ymd').'.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            $headers = ['Faculty Unique Number', 'Full Name', 'Email', 'Department'];
            fputcsv($output, $headers);

            $examples = [
                ['FAC-001', 'Dr. Maria Fernandez', 'maria.fernandez@school.edu', 'CAS'],
                ['FAC-002', 'Prof. Juan Santos', 'juan.santos@school.edu', 'CBA'],
            ];
            foreach ($examples as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importUsers(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $service = new StudentBulkImportService;
        $result = $service->import($request->file('csv_file'));

        $message = "Import complete: {$result['success']} created, {$result['failed']} failed, {$result['duplicates']} duplicates.";

        if (! empty($result['errors'])) {
            $errorSummary = implode("\n", array_slice($result['errors'], 0, 10));
            if (count($result['errors']) > 10) {
                $errorSummary .= "\n... and ".(count($result['errors']) - 10).' more errors.';
            }

            return back()
                ->with('status', $message)
                ->withErrors(['csv_errors' => $errorSummary]);
        }

        AuditTrail::log('Create', 'Users', 'Admin imported users via CSV: '.$result['success'].' successful');

        return back()->with('status', $message);
    }

    public function showUser(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    public function editUser(Request $request, User $user): View|RedirectResponse
    {
        if ($request->method() === 'GET') {
            return view('admin.users.edit', compact('user'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:student,faculty,admin',
            'academic_level' => 'nullable|string',
            'course' => 'nullable|string|max:255',
            'strand' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return redirect()->route('admin.users')->with('status', "User {$user->name} updated successfully.");
    }

    public function deleteUser(User $user): RedirectResponse
    {
        $name = $user->name;
        $user->delete();

        AuditTrail::log('Delete', 'Users', 'Admin permanently deleted user: '.$name);

        return redirect()->route('admin.users')->with('status', "User {$name} has been permanently deleted.");
    }

    public function settings(): View
    {
        $settings = new SystemSettingsService;

        $schoolSettings = [
            'school_name' => 'INFOTECH COLLEGE OF ARTS AND SCIENCES - MARCOS HIGHWAY',
            'academic_year' => $settings->get('academic_year', '2024–2025'),
            'semester' => $settings->get('current_semester', 'Second Semester'),
            'grading_period' => $settings->get('grading_period', 'PRELIM'),
            // Enrollment window removed; students join classrooms by subject code any time
            'exam_start' => $settings->get('final_exam_start', '2025-03-17'),
            'timezone' => $settings->get('timezone', 'Asia/Manila'),
            'default_passing_grade' => (int) $settings->get('passing_grade', 75),
            'grading_scale' => $settings->get('grading_scale', 'gpa'),
            'grade_equivalency' => $settings->get('grade_equivalency', [
                ['range' => '99-100', 'gpa' => '1.00'],
                ['range' => '96-98', 'gpa' => '1.25'],
                ['range' => '93-95', 'gpa' => '1.50'],
                ['range' => '90-92', 'gpa' => '1.75'],
                ['range' => '87-89', 'gpa' => '2.00'],
                ['range' => '84-86', 'gpa' => '2.25'],
                ['range' => '81-83', 'gpa' => '2.50'],
                ['range' => '78-80', 'gpa' => '2.75'],
                ['range' => '75-77', 'gpa' => '3.00'],
                ['range' => '0-50', 'gpa' => 'Dropped'],
            ]),
            'theme_admin_color' => $settings->get('theme_admin_color', '#16a34a'),
            'theme_faculty_color' => $settings->get('theme_faculty_color', '#f59e0b'),
            'theme_student_color' => $settings->get('theme_student_color', '#7c3aed'),
        ];

        return view('admin.settings', compact('schoolSettings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_year' => 'nullable|string|max:50',
            'current_semester' => 'nullable|string|max:50',
            'grading_period' => 'nullable|string|in:PRELIM,MIDTERM,FINAL',
            'final_exam_start' => 'nullable|date',
            'timezone' => 'nullable|string|max:100',
            'grading_scale' => 'nullable|string|max:30',
            'theme_admin_color' => 'nullable|string|max:30',
            'theme_faculty_color' => 'nullable|string|max:30',
            'theme_student_color' => 'nullable|string|max:30',
        ]);

        // Force the passing grade to the institutional constant — never accept user input
        $data['passing_grade'] = GradingService::PASSING_GRADE;

        $settings = new SystemSettingsService;
        foreach ($data as $k => $v) {
            if ($v === null) {
                continue;
            }
            // Save mapping differences
            $key = $k === 'current_semester' ? 'current_semester' : $k;
            $settings->set($key, $v);
        }

        return back()->with('status', 'Settings updated. Global term and appearance updated.');
    }

    public function attendance(Request $request): View
    {
        $filters = $this->resolveAttendanceFilters($request);
        $activeFilters = collect($filters)
            ->filter(function (string $value): bool {
                return $value !== '';
            })
            ->all();

        $baseQuery = $this->queryAttendanceRecords($filters, $request->has('history'));

        $totalRecords = (clone $baseQuery)->count();
        $presentRecords = (clone $baseQuery)->where('status', 'Present')->count();
        $absentRecords = (clone $baseQuery)->where('status', 'Absent')->count();
        $lateRecords = (clone $baseQuery)->where('status', 'Late')->count();
        $attendanceRate = $totalRecords > 0
            ? (string) round(($presentRecords / $totalRecords) * 100).'%'
            : '0%';

        $summary = [
            ['label' => 'Total Records', 'value' => (string) $totalRecords],
            ['label' => 'Present', 'value' => (string) $presentRecords],
            ['label' => 'Absent', 'value' => (string) $absentRecords],
            ['label' => 'Late', 'value' => (string) $lateRecords],
            ['label' => 'Attendance Rate', 'value' => $attendanceRate],
        ];

        $records = (clone $baseQuery)
            ->with(['faculty:id,name', 'studentUser:id,course,academic_level'])
            ->orderByDesc('attendance_date')
            ->orderBy('student_name')
            ->paginate(12)
            ->withQueryString();

        $courseOptions = ['BSIT', 'BSHM'];

        $subjectOptions = \App\Models\Classroom::query()
            ->whereNotNull('code')
            ->select('code')
            ->distinct()
            ->orderBy('code')
            ->pluck('code')
            ->all();

        $facultyOptions = User::query()
            ->where('role', 'faculty')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();

        // Dynamic academic level options from actual student data
        $academicLevelOptions = User::query()
            ->where('role', 'student')
            ->whereNotNull('academic_level')
            ->where('academic_level', '!=', '')
            ->select('academic_level')
            ->distinct()
            ->orderBy('academic_level')
            ->pluck('academic_level')
            ->all();

        return view('admin.attendance', compact('summary', 'records', 'filters', 'activeFilters', 'courseOptions', 'facultyOptions', 'subjectOptions', 'academicLevelOptions'));
    }

    public function exportAttendance(Request $request)
    {
        $filters = $this->resolveAttendanceFilters($request);

        $baseQuery = $this->queryAttendanceRecords($filters)
            ->with(['faculty:id,name', 'studentUser:id,course,strand'])
            ->orderByDesc('attendance_date')
            ->orderBy('student_name');

        $records = $baseQuery->get()->map(function ($r) {
            return [
                'student_name' => $r->student_name,
                'student_course' => $r->course_strand
                    ?? $r->studentUser?->course
                    ?? $r->studentUser?->strand
                    ?? '',
                'student_academic_level' => $r->academic_level ?? $r->studentUser?->academic_level ?? '',
                'faculty' => $r->faculty?->name ?? '',
                'subject' => $r->subject_code ?? '',
                'attendance_date' => $r->attendance_date?->format('Y-m-d') ?? '',
                'status' => $r->status,
                'notes' => $r->notes ?? '',
            ];
        });

        $format = $request->query('format', 'csv');
        $filenameBase = 'attendance-'.now()->format('Ymd-His');

        if ($format === 'xlsx') {
            return Excel::download(new AttendanceExport(collect($records)), $filenameBase.'.xlsx');
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.exports.attendance', ['records' => $records])->download($filenameBase.'.pdf');
        }

        $filename = $filenameBase.'.csv';

        return response()->streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Student Name', 'Course/Strand', 'Academic Level', 'Faculty', 'Subject', 'Date', 'Status', 'Notes']);
            foreach ($records as $row) {
                fputcsv($out, array_values((array) $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function grades(Request $request): View
    {
        $statusFilter = $request->query('status', '');
        $subjectFilter = $request->query('subject', '');
        $academicLevelFilter = $request->query('academic_level', '');
        $courseFilter = $request->query('course', '');
        $strandFilter = $request->query('strand', '');
        $settings = new SystemSettingsService;
        $gradingPeriodFilter = $request->has('grading_period')
            ? (string) $request->query('grading_period', '')
            : (string) $settings->get('grading_period', 'PRELIM');

        $classrooms = Classroom::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['name', 'code']);

        $classroomMap = $classrooms->keyBy('code');

        $gradesQuery = Grade::query()
            ->with(['student:id,name,academic_level,course,strand'])
            ->where(fn ($query) => $query
                ->where('academic_year', $settings->get('academic_year', '2024–2025'))
                ->orWhereNull('academic_year'))
            ->where(fn ($query) => $query
                ->where('semester', $settings->get('current_semester', 'Second Semester'))
                ->orWhereNull('semester'))
            ->when($statusFilter !== '', function ($q) use ($statusFilter) {
                if ($statusFilter === 'Pending') {
                    $q->whereNull('average');
                } elseif ($statusFilter === 'Verified') {
                    $q->whereNotNull('average');
                }
            })
            ->when($subjectFilter !== '', function ($query) use ($subjectFilter) {
                return $query->where('subject_id', $subjectFilter);
            })
            ->when($academicLevelFilter !== '' || $courseFilter !== '' || $strandFilter !== '', function ($query) use ($academicLevelFilter, $courseFilter, $strandFilter) {
                $query->whereHas('student', function ($q) use ($academicLevelFilter, $courseFilter, $strandFilter) {
                    if ($academicLevelFilter !== '') {
                        $q->where('academic_level', $academicLevelFilter);
                    }
                    if ($courseFilter !== '') {
                        $q->where('course', $courseFilter);
                    }
                    if ($strandFilter !== '') {
                        $q->where('strand', $strandFilter);
                    }
                });
            })
            ->when($gradingPeriodFilter !== '', function ($query) use ($gradingPeriodFilter) {
                $query->where('grading_period', $gradingPeriodFilter);
            });

        $coursesData = (clone $gradesQuery)
            ->get()
            ->groupBy('subject_id');

        $courses = [];
        foreach ($coursesData as $code => $records) {
            $avg = $records->avg('average');
            $highest = $records->max('average');
            $lowest = $records->min('average');
            $passing = $records->count() > 0 ? ($records->where('average', '>=', 75)->count() / $records->count() * 100) : 0;

            $grading = new GradingService;
            $dist = [];
            foreach (GradingService::gradeEquivalencyTable() as $row) {
                $dist[$row['gpa']] = 0;
            }
            $dist['Dropped'] = 0;

            foreach ($records as $record) {
                $gpa = $grading->toGpa((float) $record->average);
                if ($gpa !== null && isset($dist[$gpa])) {
                    $dist[$gpa]++;
                } else {
                    $dist['Dropped']++;
                }
            }

            $classroom = $classroomMap->get($code);

            $courses[] = [
                'name' => $classroom?->name ?? $code,
                'code' => $code,
                'avg' => round($avg),
                'highest' => round($highest),
                'lowest' => round($lowest),
                'passing' => round($passing),
                'dist' => $dist,
            ];
        }

        // All grades for the consolidated admin table (supports status filtering)
        $allGrades = (clone $gradesQuery)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $subjectOptions = $classrooms
            ->map(fn ($r) => ['code' => $r->code, 'name' => $r->name])
            ->all();

        // Compute overview metrics from the filtered grades set
        $allFiltered = (clone $gradesQuery)->get();
        $overallAverage = $allFiltered->count() ? round($allFiltered->avg('average'), 1) : 0;
        $passingRate = $allFiltered->count() ? round(($allFiltered->where('average', '>=', 75)->count() / $allFiltered->count()) * 100) : 0;
        $studentsGraded = $allFiltered->pluck('student_id')->unique()->count();

        $overview = [
            ['label' => 'Overall Average', 'value' => $overallAverage.'%', 'color' => 'emerald'],
            ['label' => 'Passing Rate', 'value' => $passingRate.'%', 'color' => 'sky'],
            ['label' => 'Students Graded', 'value' => (string) $studentsGraded, 'color' => 'slate'],
        ];

        return view('admin.grades', compact('courses', 'allGrades', 'subjectFilter', 'subjectOptions', 'academicLevelFilter', 'courseFilter', 'strandFilter', 'gradingPeriodFilter', 'overview', 'statusFilter', 'classroomMap'));
    }

    public function verifyGrade(Grade $grade): RedirectResponse
    {
        if ($grade->average === null) {
            return back()->withErrors(['grade' => 'Grade is not yet recorded.']);
        }

        return back()->with('status', 'Grade recorded for '.($grade->student->name ?? 'Student'));
    }

    public function exportGrades(Request $request)
    {
        $subjectFilter = $request->query('subject');
        $academicLevelFilter = $request->query('academic_level');
        $courseFilter = $request->query('course');
        $strandFilter = $request->query('strand');
        $gradingPeriodFilter = (string) $request->query('grading_period', '');
        $format = $request->query('format', 'csv');
        $settings = new SystemSettingsService;
        $semester = $settings->get('current_semester', 'Second Semester');
        $gradingPeriod = $request->has('grading_period') && $gradingPeriodFilter === ''
            ? 'All Periods'
            : ($gradingPeriodFilter ?: $settings->get('grading_period', 'PRELIM'));

        $query = Grade::query()
            ->with(['student:id,name,email,academic_level,course,strand'])
            ->where(fn ($query) => $query
                ->where('academic_year', $settings->get('academic_year', '2024–2025'))
                ->orWhereNull('academic_year'))
            ->where(fn ($query) => $query
                ->where('semester', $settings->get('current_semester', 'Second Semester'))
                ->orWhereNull('semester'))
            ->when($subjectFilter, function ($query, $subjectFilter) {
                return $query->where('subject_id', $subjectFilter);
            })
            ->when($academicLevelFilter !== '' || $courseFilter !== '' || $strandFilter !== '', function ($query) use ($academicLevelFilter, $courseFilter, $strandFilter) {
                $query->whereHas('student', function ($q) use ($academicLevelFilter, $courseFilter, $strandFilter) {
                    if ($academicLevelFilter !== null && $academicLevelFilter !== '') {
                        $q->where('academic_level', $academicLevelFilter);
                    }
                    if ($courseFilter !== null && $courseFilter !== '') {
                        $q->where('course', $courseFilter);
                    }
                    if ($strandFilter !== null && $strandFilter !== '') {
                        $q->where('strand', $strandFilter);
                    }
                });
            })
            ->when($gradingPeriodFilter !== null && $gradingPeriodFilter !== '', function ($query) use ($gradingPeriodFilter) {
                $query->where('grading_period', $gradingPeriodFilter);
            })
            ->orderBy('subject_id')
            ->orderBy('student_id');

        $records = $query->get();

        $classroomMap = Classroom::query()
            ->with('faculty:id,name')
            ->select('name', 'code', 'faculty_user_id')
            ->get()
            ->keyBy('code');

        if ($format === 'pdf') {
            $scope = $subjectFilter ?: 'All Subjects';
            if ($academicLevelFilter) {
                $scope .= ' | '.$academicLevelFilter;
            }
            if ($gradingPeriod) {
                $scope .= ' | '.$gradingPeriod;
            }

            $pdf = Pdf::loadView('admin.exports.grades_pdf', [
                'records' => $records,
                'scope' => $scope,
                'classroomMap' => $classroomMap,
                'semester' => $semester,
                'gradingPeriod' => $gradingPeriod,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('official-academic-record-'.now()->format('Ymd-His').'.pdf');
        }

        $filename = 'grade-generator-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records, $classroomMap, $semester, $gradingPeriod): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Semester', 'Grading Period', 'Student Name', 'Student Email', 'Course/Strand', 'Level', 'Module Name', 'Module Code', 'Instructor', 'Grade (%)', 'GPA Equivalent']);

            $gradingService = new GradingService;

            foreach ($records as $record) {
                $raw = (float) $record->average;
                $gpa = $gradingService->toGpa($raw) ?? 'N/A';

                $courseStrand = str_contains($record->student?->academic_level ?? '', 'Senior High School')
                    ? ($record->student?->strand ?? 'N/A')
                    : ($record->student?->course ?? 'N/A');

                $classroom = $classroomMap->get($record->subject_id);

                fputcsv($output, [
                    $semester,
                    $gradingPeriod,
                    $record->student?->name ?? 'Unknown Student',
                    $record->student?->email ?? '',
                    $courseStrand,
                    $record->student?->academic_level ?? '',
                    $classroom?->name ?? $record->subject_id,
                    $record->subject_id,
                    $classroom?->faculty?->name ?? '',
                    number_format($raw, 2),
                    $gpa,
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function updateGrade(Request $request, Grade $grade): RedirectResponse
    {
        $validated = $request->validate([
            'grade_percent' => 'required|numeric|min:0|max:100',
            'reason' => 'required|string|max:500',
        ]);

        $oldGrade = $grade->average;
        $newGrade = $validated['grade_percent'];
        $reason = $validated['reason'];

        $updateData = [
            'average' => $newGrade,
            'remarks' => $newGrade >= GradingService::PASSING_GRADE ? 'Pass' : 'Fail',
            'is_overridden' => true,
            'override_reason' => $reason,
        ];

        // Save original_grade only if it hasn't been overridden yet
        if (!$grade->is_overridden) {
            $updateData['original_grade'] = $oldGrade;
        }

        $grade->update($updateData);

        // Keep legacy StudentModuleRecord in sync for the Student portal
        \App\Models\StudentModuleRecord::where('user_id', $grade->student_id)
            ->where('module_code', $grade->subject_id)
            ->where('academic_year', $grade->academic_year)
            ->where('semester', $grade->semester)
            ->update(['grade_percent' => $newGrade]);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'Update Grade',
            'module' => 'Grades',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'detail' => 'Admin '.auth()->user()->name.' manually changed grade for '.($grade->student->name ?? 'Student').' in '.$grade->subject_id." from {$oldGrade} to {$newGrade}. Reason: {$reason}",
        ]);

        return back()->with('status', 'Grade updated and logged successfully.');
    }

    public function resetGrade(Request $request, Grade $grade): RedirectResponse
    {
        if (!$grade->is_overridden) {
            return back()->with('status', 'Grade is not overridden.');
        }

        $oldGrade = $grade->average;
        $originalGrade = $grade->original_grade;

        $grade->update([
            'average' => $originalGrade,
            'remarks' => $originalGrade >= GradingService::PASSING_GRADE ? 'Pass' : 'Fail',
            'is_overridden' => false,
            'original_grade' => null,
            'override_reason' => null,
        ]);

        // Keep legacy StudentModuleRecord in sync for the Student portal
        \App\Models\StudentModuleRecord::where('user_id', $grade->student_id)
            ->where('module_code', $grade->subject_id)
            ->where('academic_year', $grade->academic_year)
            ->where('semester', $grade->semester)
            ->update(['grade_percent' => $originalGrade]);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'Reset Grade Override',
            'module' => 'Grades',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'detail' => 'Admin '.auth()->user()->name.' reset overridden grade for '.($grade->student->name ?? 'Student').' in '.$grade->subject_id." from {$oldGrade} back to computed {$originalGrade}.",
        ]);

        return back()->with('status', 'Grade override has been reset to the computed value.');
    }

    public function documents(Request $request): View
    {
        $search = $request->query('search');
        $type = $request->query('type');
        $status = $request->query('status');

        $requestsQuery = DocumentRequest::with('user:id,name')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            })
            ->when($type, function ($q) use ($type) {
                $q->where('document_type', $type);
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest();

        $requests = $requestsQuery->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'student' => $doc->user->name ?? 'Unknown',
                'doc' => $doc->document_type,
                'purpose' => $doc->purpose ?? 'N/A',
                'date' => $doc->created_at->format('M j'),
                'urgency' => $doc->urgency,
                'status' => $doc->status,
            ];
        })->all();

        $pending = DocumentRequest::where('status', 'Pending')->count();
        $processing = DocumentRequest::where('status', 'Processing')->count();
        $completed = DocumentRequest::where('status', 'Completed')->count();
        $rejected = DocumentRequest::where('status', 'Rejected')->count();
        $total = DocumentRequest::count();

        $summary = [
            ['label' => 'Pending', 'value' => (string) $pending, 'color' => 'amber'],
            ['label' => 'Processing', 'value' => (string) $processing, 'color' => 'sky'],
            ['label' => 'Completed', 'value' => (string) $completed, 'color' => 'emerald'],
            ['label' => 'Rejected', 'value' => (string) $rejected, 'color' => 'rose'],
        ];

        return view('admin.documents', compact('requests', 'search', 'type', 'status', 'summary'));
    }

    public function updateDocument(Request $request, DocumentRequest $documentRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Pending,Processing,Completed,Rejected'],
        ]);

        $documentRequest->update(['status' => $validated['status']]);

        return back()->with('status', 'Document request status updated to '.$validated['status']);
    }

    public function deleteDocument(DocumentRequest $documentRequest): RedirectResponse
    {
        $name = $documentRequest->user->name ?? 'Request';
        $documentRequest->delete();

        return back()->with('status', 'Document request for '.$name.' has been permanently deleted.');
    }

    public function forum(): View
    {
        $threads = ForumThread::with(['user', 'replies'])
            ->latest()
            ->paginate(15);

        $totalPosts = ForumThread::count();
        $totalReplies = ForumReply::count();
        $flagged = ForumThread::where('is_flagged', true)->count();

        $stats = [
            ['label' => 'Total Posts', 'value' => (string) $totalPosts, 'color' => 'slate'],
            ['label' => 'Total Replies', 'value' => (string) $totalReplies, 'color' => 'slate'],
            ['label' => 'Flagged Analytics', 'value' => (string) $flagged, 'color' => 'rose'],
        ];

        return view('admin.forum', compact('threads', 'stats'));
    }

    public function showForumThread(ForumThread $forumThread): View
    {
        $forumThread->load(['user', 'replies.user']);

        return view('admin.forum-show', compact('forumThread'));
    }

    public function toggleHideForumThread(ForumThread $forumThread): RedirectResponse
    {
        $forumThread->update(['is_visible' => ! $forumThread->is_visible]);
        $status = $forumThread->is_visible ? 'visible' : 'hidden';

        return back()->with('status', "Post is now {$status}.");
    }

    public function flagForumThread(ForumThread $forumThread): RedirectResponse
    {
        $forumThread->update(['is_flagged' => true]);

        return back()->with('status', 'Post has been flagged for review.');
    }

    public function deleteForumThread(ForumThread $forumThread): RedirectResponse
    {
        $forumThread->delete();

        return back()->with('status', 'Post and all associated replies have been permanently deleted.');
    }

    /**
     * @return array{search: string, status: string, faculty_user_id: string, academic_level: string, course: string, strand: string, subject: string, from_date: string, to_date: string}
     */
    private function resolveAttendanceFilters(Request $request): array
    {
        $status = trim((string) $request->query('status', ''));

        if (! in_array($status, ['Present', 'Absent', 'Late'], true)) {
            $status = '';
        }

        $facultyUserId = trim((string) $request->query('faculty_user_id', ''));
        $academicLevel = trim((string) $request->query('academic_level', ''));
        $course = trim((string) $request->query('course', ''));
        $strand = trim((string) $request->query('strand', ''));
        $subject = trim((string) $request->query('subject', ''));

        if ($facultyUserId !== '' && ! ctype_digit($facultyUserId)) {
            $facultyUserId = '';
        }

        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => $status,
            'faculty_user_id' => $facultyUserId,
            'academic_level' => $academicLevel,
            'course' => $course,
            'strand' => $strand,
            'subject' => $subject,
            'from_date' => trim((string) $request->query('from_date', '')),
            'to_date' => trim((string) $request->query('to_date', '')),
        ];
    }

    /**
     * @param  array{search: string, status: string, faculty_user_id: string, academic_level: string, course: string, strand: string, subject: string, from_date: string, to_date: string}  $filters
     */
    private function queryAttendanceRecords(array $filters, bool $showHistory = false): Builder
    {
        return FacultyAttendanceRecord::query()
            ->when(! $showHistory, function ($q) {
                $settings = new SystemSettingsService;
                $q->where('academic_year', $settings->get('academic_year'))
                    ->where('semester', $settings->get('current_semester'));
            })
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where('student_name', 'like', '%'.$filters['search'].'%');
            })
            ->when($filters['academic_level'] !== '', function (Builder $query) use ($filters): void {
                $query->where('academic_level', $filters['academic_level']);
            })
            ->when($filters['course'] !== '', function (Builder $query) use ($filters): void {
                $query->where('course_strand', $filters['course']);
            })
            ->when($filters['strand'] !== '', function (Builder $query) use ($filters): void {
                $query->where('course_strand', $filters['strand'])
                    ->where('academic_level', 'Senior High School');
            })
            ->when($filters['subject'] !== '', function (Builder $query) use ($filters): void {
                $query->where('subject_code', $filters['subject']);
            })
            ->when($filters['status'] !== '', function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            })

            ->when($filters['faculty_user_id'] !== '', function (Builder $query) use ($filters): void {
                $query->where('faculty_user_id', (int) $filters['faculty_user_id']);
            })
            ->when($filters['from_date'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('attendance_date', '>=', $filters['from_date']);
            })
            ->when($filters['to_date'] !== '', function (Builder $query) use ($filters): void {
                $query->whereDate('attendance_date', '<=', $filters['to_date']);
            });
    }

    private function resolveGradeStatus(float $averageGrade): string
    {
        if ($averageGrade >= 85) {
            return 'On track';
        }

        if ($averageGrade >= 75) {
            return 'Needs review';
        }

        return 'At risk';
    }

    public function enrollments(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['pending', 'enrolled', 'dropped'], true)
            ? $request->query('tab')
            : 'pending';

        $courseFilter = trim((string) $request->query('course', ''));

        $enrollments = StudentModuleRecord::query()
            ->where('enrollment_status', $tab === 'pending' ? 'faculty_approved' : $tab)
            ->when(! $request->has('history'), function ($q) {
                $settings = new SystemSettingsService;
                $q->where('academic_year', $settings->get('academic_year'))
                    ->where('semester', $settings->get('current_semester'));
            })
            ->with(['user:id,name,email'])
            ->when($courseFilter !== '', function ($query) use ($courseFilter): void {
                $query->where('module_code', $courseFilter);
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $enrolledCount = StudentModuleRecord::where('enrollment_status', 'enrolled')->count();
        $pendingCount = StudentModuleRecord::where('enrollment_status', 'faculty_approved')->count();
        $droppedCount = StudentModuleRecord::where('enrollment_status', 'dropped')->count();

        $summary = [
            ['label' => 'Pending', 'value' => (string) $pendingCount, 'color' => 'amber', 'tab' => 'pending'],
            ['label' => 'Enrolled', 'value' => (string) $enrolledCount, 'color' => 'emerald', 'tab' => 'enrolled'],
            ['label' => 'Dropped', 'value' => (string) $droppedCount, 'color' => 'rose', 'tab' => 'dropped'],
        ];

        $courseOptions = StudentModuleRecord::query()
            ->select('module_code', 'module_name')
            ->distinct()
            ->orderBy('module_name')
            ->get()
            ->map(fn (StudentModuleRecord $r): array => [
                'code' => $r->module_code,
                'name' => $r->module_name,
            ])
            ->all();

        // Real-time analytics: students by academic level
        $levelStats = collect([
            '1st Year College',
            '2nd Year College',
            '3rd Year College',
        ])->map(fn ($level) => [
            'label' => $level,
            'count' => User::where('role', 'student')->where('academic_level', $level)->count(),
        ])->all();

        // Real-time analytics: students by course
        $courseStats = collect(['BSIT', 'BSHM'])->map(fn ($c) => [
            'label' => $c,
            'count' => User::where('role', 'student')->where('course', $c)->count(),
        ])->all();

        return view('admin.enrollments', compact('enrollments', 'summary', 'tab', 'courseFilter', 'courseOptions', 'levelStats', 'courseStats'));
    }

    public function auditTrail(Request $request): View
    {
        $userFilter = trim((string) $request->query('user', ''));
        $roleFilter = trim((string) $request->query('role', ''));
        $actionFilter = trim((string) $request->query('action', ''));
        $dateFilter = trim((string) $request->query('date', ''));

        $query = AuditTrail::with('user')
            ->when($userFilter !== '', function ($q) use ($userFilter) {
                $q->whereHas('user', function ($q2) use ($userFilter) {
                    $q2->where('name', 'like', "%{$userFilter}%");
                });
            })
            ->when($roleFilter !== '', function ($q) use ($roleFilter) {
                $q->whereHas('user', function ($q2) use ($roleFilter) {
                    $q2->where('role', $roleFilter);
                });
            })
            ->when($actionFilter !== '', function ($q) use ($actionFilter) {
                $q->where('action', $actionFilter);
            })
            ->when($dateFilter !== '', function ($q) use ($dateFilter) {
                $q->whereDate('timestamp', $dateFilter);
            })
            ->orderByDesc('timestamp')
            ->paginate(50)
            ->withQueryString();

        $actions = collect($query->items())->map(function (AuditTrail $at) {
            return [
                'time' => $at->timestamp ? Carbon::parse($at->timestamp)->format('M j, Y h:i A') : 'N/A',
                'user' => $at->user->name ?? 'System',
                'role' => $at->user->role ?? 'N/A',
                'action' => $at->action,
                'module' => $at->module,
                'ip' => $at->ip_address,
                'detail' => $at->detail,
            ];
        })->all();

        $stats = [
            ['label' => 'Total Actions', 'value' => AuditTrail::count()],
            ['label' => 'Logins',        'value' => AuditTrail::where('action', 'Login')->count()],
            ['label' => 'Creates',       'value' => AuditTrail::where('action', 'Create')->count()],
            ['label' => 'Updates',       'value' => AuditTrail::where('action', 'Update')->count()],
            ['label' => 'Deletes',       'value' => AuditTrail::where('action', 'Delete')->count()],
        ];

        return view('admin.audit-trail', [
            'actions' => $actions,
            'stats' => $stats,
            'userFilter' => $userFilter,
            'roleFilter' => $roleFilter,
            'actionFilter' => $actionFilter,
            'dateFilter' => $dateFilter,
            'pagination' => $query,
        ]);
    }

    public function systemMonitoring(): View
    {
        $serverStats = $this->gatherServerStats();

        $platformStats = [
            ['label' => 'Total Users',          'value' => (string) User::count(),
                'icon' => 'users'],
            ['label' => 'Total Classrooms',      'value' => (string) Classroom::count(),
                'icon' => 'classroom'],
            ['label' => 'Attendance Records',    'value' => (string) FacultyAttendanceRecord::count(),
                'icon' => 'check'],
            ['label' => 'Document Requests',     'value' => (string) DocumentRequest::count(),
                'icon' => 'doc'],
            ['label' => 'Forum Posts',           'value' => (string) ForumThread::count(),
                'icon' => 'chat'],
        ];

        // Registration trend for the past 6 months
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $months->push($m->format('M'));
        }

        $registrations = User::selectRaw('MONTH(created_at) as month, role, COUNT(*) as cnt')
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->groupBy('month', 'role')
            ->get()
            ->groupBy(['month', 'role']);

        $registrationTrend = $months->map(function ($label, $idx) use ($registrations) {
            $monthNum = Carbon::now()->subMonths(5 - $idx)->month;

            // Safely get the grouped results for the month (may be missing)
            $monthGroup = $registrations->get($monthNum, collect());

            // Each role group may also be missing; default to empty collection
            $students = $monthGroup->get('student', collect())->sum('cnt');
            $faculty = $monthGroup->get('faculty', collect())->sum('cnt');

            return ['month' => $label, 'students' => $students, 'faculty' => $faculty];
        })->all();

        $healthChecks = $this->gatherHealthChecks();

        return view('admin.system-monitoring', compact('serverStats', 'platformStats', 'registrationTrend', 'healthChecks'));
    }

    /**
     * JSON API endpoint for real-time system monitoring polling.
     */
    public function systemMonitoringApi(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'serverStats'   => $this->gatherServerStats(),
            'platformStats' => [
                'users'       => User::count(),
                'classrooms'  => Classroom::count(),
                'attendance'  => FacultyAttendanceRecord::count(),
                'documents'   => DocumentRequest::count(),
                'forum'       => ForumThread::count(),
            ],
            'healthChecks'  => $this->gatherHealthChecks(),
            'timestamp'     => now()->format('h:i:s A'),
        ]);
    }

    /**
     * Gather real server resource stats (CPU, Memory, Disk, PHP memory).
     *
     * @return array<int, array{label: string, value: int|string, unit: string, color: string, status: string, detail: string}>
     */
    private function gatherServerStats(): array
    {
        // --- CPU Usage ---
        $cpuUsage = 0;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: use wmic
            try {
                $output = @shell_exec('wmic cpu get loadpercentage /value 2>NUL');
                if ($output && preg_match('/LoadPercentage=(\d+)/', $output, $m)) {
                    $cpuUsage = (int) $m[1];
                }
            } catch (\Throwable) {
                $cpuUsage = 0;
            }
        } else {
            // Linux/Mac: use sys_getloadavg
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $cores = (int) @shell_exec('nproc 2>/dev/null') ?: 1;
                $cpuUsage = min(100, (int) round(($load[0] / $cores) * 100));
            }
        }

        // --- Memory Usage ---
        $memUsedPercent = 0;
        $memTotalMB = 0;
        $memUsedMB = 0;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $totalOutput = @shell_exec('wmic computersystem get TotalPhysicalMemory /value 2>NUL');
                $freeOutput = @shell_exec('wmic OS get FreePhysicalMemory /value 2>NUL');
                if ($totalOutput && preg_match('/TotalPhysicalMemory=(\d+)/', $totalOutput, $tm)) {
                    $totalBytes = (float) $tm[1];
                    $memTotalMB = round($totalBytes / 1024 / 1024);
                }
                if ($freeOutput && preg_match('/FreePhysicalMemory=(\d+)/', $freeOutput, $fm)) {
                    $freeKB = (float) $fm[1];
                    $freeMB = round($freeKB / 1024);
                    $memUsedMB = $memTotalMB - $freeMB;
                    $memUsedPercent = $memTotalMB > 0 ? (int) round(($memUsedMB / $memTotalMB) * 100) : 0;
                }
            } catch (\Throwable) {
                $memUsedPercent = 0;
            }
        } else {
            // Linux: read /proc/meminfo
            try {
                $meminfo = @file_get_contents('/proc/meminfo');
                if ($meminfo) {
                    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
                    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);
                    if (!empty($total[1]) && !empty($avail[1])) {
                        $memTotalMB = round((int) $total[1] / 1024);
                        $memUsedMB = $memTotalMB - round((int) $avail[1] / 1024);
                        $memUsedPercent = (int) round(($memUsedMB / $memTotalMB) * 100);
                    }
                }
            } catch (\Throwable) {
                $memUsedPercent = 0;
            }
        }

        // --- Disk Usage ---
        $diskTotal = @disk_total_space(base_path());
        $diskFree = @disk_free_space(base_path());
        $diskUsedPercent = 0;
        $diskTotalGB = 0;
        $diskUsedGB = 0;
        if ($diskTotal && $diskTotal > 0) {
            $diskTotalGB = round($diskTotal / 1024 / 1024 / 1024, 1);
            $diskUsedGB = round(($diskTotal - $diskFree) / 1024 / 1024 / 1024, 1);
            $diskUsedPercent = (int) round((($diskTotal - $diskFree) / $diskTotal) * 100);
        }

        // --- PHP Memory ---
        $phpMemMB = round(memory_get_usage(true) / 1024 / 1024, 1);
        $phpMemPeakMB = round(memory_get_peak_usage(true) / 1024 / 1024, 1);
        $phpMemLimit = ini_get('memory_limit');

        // --- Uptime ---
        $uptimeStr = 'Unknown';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            try {
                $bootOutput = @shell_exec('wmic os get LastBootUpTime /value 2>NUL');
                if ($bootOutput && preg_match('/LastBootUpTime=(\d{14})/', $bootOutput, $bm)) {
                    $bootTime = Carbon::createFromFormat('YmdHis', $bm[1]);
                    $diff = $bootTime->diff(Carbon::now());
                    $parts = [];
                    if ($diff->d > 0) $parts[] = $diff->d.'d';
                    if ($diff->h > 0) $parts[] = $diff->h.'h';
                    $parts[] = $diff->i.'m';
                    $uptimeStr = implode(' ', $parts);
                }
            } catch (\Throwable) {
                $uptimeStr = 'Unknown';
            }
        } else {
            try {
                $uptime = @file_get_contents('/proc/uptime');
                if ($uptime) {
                    $seconds = (int) explode(' ', $uptime)[0];
                    $days = intdiv($seconds, 86400);
                    $hours = intdiv($seconds % 86400, 3600);
                    $mins = intdiv($seconds % 3600, 60);
                    $parts = [];
                    if ($days > 0) $parts[] = $days.'d';
                    if ($hours > 0) $parts[] = $hours.'h';
                    $parts[] = $mins.'m';
                    $uptimeStr = implode(' ', $parts);
                }
            } catch (\Throwable) {
                $uptimeStr = 'Unknown';
            }
        }

        $cpuColor = $cpuUsage < 50 ? 'emerald' : ($cpuUsage < 80 ? 'amber' : 'rose');
        $cpuStatus = $cpuUsage < 50 ? 'Normal' : ($cpuUsage < 80 ? 'Moderate' : 'High');

        $memColor = $memUsedPercent < 60 ? 'emerald' : ($memUsedPercent < 85 ? 'amber' : 'rose');
        $memStatus = $memUsedPercent < 60 ? 'Normal' : ($memUsedPercent < 85 ? 'Moderate' : 'High');

        $diskColor = $diskUsedPercent < 70 ? 'sky' : ($diskUsedPercent < 90 ? 'amber' : 'rose');
        $diskStatus = $diskUsedPercent < 70 ? 'Healthy' : ($diskUsedPercent < 90 ? 'Warning' : 'Critical');

        return [
            [
                'label'  => 'CPU Usage',
                'value'  => $cpuUsage,
                'unit'   => '%',
                'color'  => $cpuColor,
                'status' => $cpuStatus,
                'detail' => PHP_OS.' · '.php_uname('m'),
            ],
            [
                'label'  => 'Memory Usage',
                'value'  => $memUsedPercent,
                'unit'   => '%',
                'color'  => $memColor,
                'status' => $memStatus,
                'detail' => $memUsedMB.' / '.$memTotalMB.' MB used',
            ],
            [
                'label'  => 'Disk Usage',
                'value'  => $diskUsedPercent,
                'unit'   => '%',
                'color'  => $diskColor,
                'status' => $diskStatus,
                'detail' => $diskUsedGB.' / '.$diskTotalGB.' GB used',
            ],
            [
                'label'  => 'PHP Memory',
                'value'  => $phpMemMB,
                'unit'   => 'MB',
                'color'  => 'violet',
                'status' => 'Limit: '.$phpMemLimit,
                'detail' => 'Peak: '.$phpMemPeakMB.' MB',
            ],
            [
                'label'  => 'System Uptime',
                'value'  => $uptimeStr,
                'unit'   => '',
                'color'  => 'emerald',
                'status' => 'Online',
                'detail' => 'Since last boot',
            ],
            [
                'label'  => 'PHP Version',
                'value'  => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
                'unit'   => '',
                'color'  => 'sky',
                'status' => 'v'.PHP_VERSION,
                'detail' => php_sapi_name(),
            ],
        ];
    }

    /**
     * Gather system health check results.
     *
     * @return array<int, array{name: string, status: string, detail: string}>
     */
    private function gatherHealthChecks(): array
    {
        // Database check
        $dbStatus = 'error';
        $dbDetail = 'Connection failed';
        try {
            DB::select('SELECT 1 AS ok');
            $dbStatus = 'ok';
            $dbDetail = 'MySQL reachable · '.DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            $dbDetail = 'MySQL error: '.class_basename($e);
        }

        // Storage check
        $storageStatus = is_writable(storage_path('app')) ? 'ok' : 'error';
        $storageFree = @disk_free_space(storage_path('app'));
        $storageDetail = $storageStatus === 'ok'
            ? 'Writable · '.($storageFree ? round($storageFree / 1024 / 1024 / 1024, 1).' GB free' : 'OK')
            : 'Storage directory not writable';

        // Cache check
        $cacheStatus = 'ok';
        $cacheDetail = 'Cache driver: '.config('cache.default');
        try {
            \Illuminate\Support\Facades\Cache::put('_health_check', true, 5);
            if (!\Illuminate\Support\Facades\Cache::get('_health_check')) {
                $cacheStatus = 'error';
                $cacheDetail = 'Cache read failed';
            }
        } catch (\Throwable) {
            $cacheStatus = 'error';
            $cacheDetail = 'Cache unavailable';
        }

        // Session check
        $sessionStatus = 'ok';
        $sessionDetail = 'Driver: '.config('session.driver');

        // App environment
        $envStatus = app()->environment('production') ? 'ok' : 'warning';
        $envDetail = 'Environment: '.app()->environment().' · Debug: '.(config('app.debug') ? 'ON' : 'OFF');

        return [
            ['name' => 'Database Connection',  'status' => $dbStatus,      'detail' => $dbDetail],
            ['name' => 'File Storage',         'status' => $storageStatus, 'detail' => $storageDetail],
            ['name' => 'Cache System',         'status' => $cacheStatus,   'detail' => $cacheDetail],
            ['name' => 'Session Handler',      'status' => $sessionStatus, 'detail' => $sessionDetail],
            ['name' => 'App Environment',      'status' => $envStatus,     'detail' => $envDetail],
        ];
    }

    /**
     * Admin Profile page – shows editable profile fields and the admin's
     * own announcements (filtered by created_by).
     */
    public function profile(): View
    {
        $admin = auth()->user();

        // Announcements created by this admin only
        $myAnnouncements = Announcement::where('created_by', $admin->id)
            ->latest()
            ->get();

        return view('admin.profile', compact('admin', 'myAnnouncements'));
    }

    /**
     * Update individual or multiple admin profile fields.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $admin = auth()->user();

        $validated = $request->validate([
            'title' => 'nullable|string|in:Dr.,Mr.,Ms.,Mrs.,Prof.,Engr.',
            'designation' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'office_hours' => 'nullable|string|max:100',
            'gender' => 'nullable|string|in:Male,Female,Other,Prefer not to say',
            'address' => 'nullable|string|max:500',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            if ($admin->profile_photo) {
                Storage::disk('local')->delete($admin->profile_photo);
            }
            $admin->profile_photo = $file->store('profiles', 'local');
            $admin->profile_image_mime = $file->getMimeType();
            $admin->save();
        }

        unset($validated['profile_photo']);

        $admin->update($validated);

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                // Complexity: 1 uppercase, 1 lowercase, 1 digit, 1 special char
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ], [
            'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'current_password.current_password' => 'The provided password does not match our records.',
        ]);

        $user = $request->user();

        // Note: We do NOT use Hash::make() here because the User model
        // has 'password' => 'hashed' cast, which handles hashing automatically.
        $user->update([
            'password' => $request->password,
            'force_password_reset' => false,
        ]);

        $request->session()->forget(['force_password_change', 'force_password_change_message']);

        AuditTrail::log('Update', 'Settings', 'Admin updated their own password');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Password updated successfully. Please log in with your new credentials.');
    }

    public function facultyDirectory(Request $request): View
    {
        $search = $request->query('search', '');
        $statusFilter = $request->query('status', '');

        $query = User::where('role', 'faculty')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter, function ($q) use ($statusFilter) {
                $q->where('status', $statusFilter);
            });

        $facultyList = $query->paginate(15)->withQueryString();

        $totalFaculty = User::where('role', 'faculty')->count();
        $departments = User::where('role', 'faculty')->whereNotNull('department')->select('department')->distinct()->pluck('department');

        $departmentStats = [];
        foreach ($departments as $dept) {
            if ($dept) {
                $departmentStats[] = [
                    'label' => $dept,
                    'count' => User::where('role', 'faculty')->where('department', $dept)->count(),
                ];
            }
        }

        if (empty($departmentStats)) {
            // Fallback default levels if none are set
            $defaultLevels = ['Senior High School', '1st Year College', '2nd Year College', '3rd Year College'];
            foreach ($defaultLevels as $lvl) {
                $departmentStats[] = [
                    'label' => $lvl,
                    'count' => User::where('role', 'faculty')->where('department', $lvl)->count(),
                ];
            }
        }

        return view('admin.faculty.index', compact('facultyList', 'totalFaculty', 'departmentStats', 'search', 'statusFilter', 'departments'));
    }

    public function facultyShow(User $user): View
    {
        abort_if($user->role !== 'faculty', 404);

        // Load subjects they are teaching (classrooms)
        $classrooms = $user->classroomsAsFaculty()->withCount('students')->get();

        return view('admin.faculty.show', compact('user', 'classrooms'));
    }

    public function toggleFacultyStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'faculty', 404);

        $newStatus = $request->input('status');
        if (! in_array($newStatus, ['active', 'inactive'])) {
            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        }
        
        $user->update(['status' => $newStatus]);

        AuditTrail::log('Update', 'Faculty Directory', 'Admin ' . auth()->user()->name . ' (ID: ' . auth()->id() . ') ' . ($newStatus === 'active' ? 'activated' : 'deactivated') . ' faculty ' . $user->name . ' (ID: ' . $user->id . ')');

        return back()->with('status', "Faculty account for {$user->name} has been ".($newStatus === 'active' ? 'activated' : 'deactivated').'.');
    }
}
