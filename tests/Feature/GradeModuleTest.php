<?php

use App\Models\Classroom;
use App\Models\Grade;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('loads faculty grades for an active classroom', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
    ]);

    $classroom = Classroom::create([
        'faculty_user_id' => $faculty->id,
        'name' => 'Math 101',
        'code' => 'MATH101',
        'schedule' => 'MWF 8:00-9:00',
        'description' => 'Intro Math',
        'status' => 'active',
        'academic_year' => '2026-2027',
        'semester' => 'First Semester',
    ]);

    $classroom->students()->attach($student->id, ['enrolled_at' => now()]);

    Grade::create([
        'student_id' => $student->id,
        'subject_id' => 'MATH101',
        'average' => 88.5,
        'remarks' => 'Pass',
        'component_scores' => ['quiz' => 90],
    ]);

    actingAs($faculty);

    $this->get(route('faculty.grades', ['tab' => 'grades', 'grade_subject' => 'MATH101']))
        ->assertSuccessful()
        ->assertSee('Math 101')
        ->assertSee('MATH101');
});

it('loads admin grade distribution from grade records', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
        'academic_level' => '1st Year College',
        'course' => 'BSIT',
    ]);

    Classroom::create([
        'faculty_user_id' => $faculty->id,
        'name' => 'Science 101',
        'code' => 'SCI101',
        'schedule' => 'TTH 10:00-11:00',
        'description' => 'Intro Science',
        'status' => 'active',
        'academic_year' => '2026-2027',
        'semester' => 'First Semester',
    ]);

    Grade::create([
        'student_id' => $student->id,
        'subject_id' => 'SCI101',
        'average' => 91.25,
        'remarks' => 'Pass',
    ]);

    actingAs($admin);

    $this->get(route('admin.grades'))
        ->assertSuccessful()
        ->assertSee('Science 101')
        ->assertSee('SCI101');
});
