<?php

use App\Models\Classroom;
use App\Models\FacultyAttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('shows subject labels instead of class labels on the faculty attendance records table', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $classroom = Classroom::create([
        'faculty_user_id' => $faculty->id,
        'name' => 'Advanced Mathematics',
        'code' => 'MATH301',
        'schedule' => 'Mon 9:00 AM',
        'description' => 'Math subject',
        'status' => 'active',
        'academic_year' => '2024–2025',
        'semester' => 'Second Semester',
    ]);

    FacultyAttendanceRecord::create([
        'faculty_user_id' => $faculty->id,
        'student_name' => 'Jane Doe',
        'student_class' => $classroom->code,
        'subject_code' => $classroom->code,
        'attendance_date' => now()->toDateString(),
        'status' => 'Present',
        'academic_year' => '2024–2025',
        'semester' => 'Second Semester',
    ]);

    actingAs($faculty)
        ->get(route('faculty.grades', ['tab' => 'attendance']))
        ->assertStatus(200)
        ->assertSee('>Subject</th>', false)
        ->assertDontSee('>Class</th>', false)
        ->assertSee('Advanced Mathematics (MATH301)');
});
