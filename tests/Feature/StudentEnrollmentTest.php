<?php

use App\Models\Classroom;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows a student to join a classroom by subject code', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $student = User::factory()->create(['role' => 'student']);

    // Set current term settings used by joinByCode()
    SystemSetting::create(['setting_key' => 'academic_year', 'setting_value' => '2024–2025']);
    SystemSetting::create(['setting_key' => 'current_semester', 'setting_value' => 'Second Semester']);

    $classroom = Classroom::create([
        'faculty_user_id' => $faculty->id,
        'name' => 'Intro to Testing',
        'code' => 'ABC123',
        'schedule' => 'Mon 9:00 AM',
        'description' => 'Test classroom',
        'status' => 'active',
        'academic_year' => '2024–2025',
        'semester' => 'Second Semester',
    ]);

    actingAs($student)
        ->post(route('student.classrooms.join'), ['code' => 'ABC123'])
        ->assertRedirect(route('student.classrooms'))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('classroom_students', [
        'classroom_id' => $classroom->id,
        'user_id' => $student->id,
    ]);
});

it('prevents joining the same classroom twice', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $student = User::factory()->create(['role' => 'student']);

    SystemSetting::create(['setting_key' => 'academic_year', 'setting_value' => '2024–2025']);
    SystemSetting::create(['setting_key' => 'current_semester', 'setting_value' => 'Second Semester']);

    $classroom = Classroom::create([
        'faculty_user_id' => $faculty->id,
        'name' => 'Intro to Testing',
        'code' => 'ABC123',
        'schedule' => 'Mon 9:00 AM',
        'description' => 'Test classroom',
        'status' => 'active',
        'academic_year' => '2024–2025',
        'semester' => 'Second Semester',
    ]);

    // First join
    $student->classroomsAsStudent()->attach($classroom->id);

    actingAs($student)
        ->post(route('student.classrooms.join'), ['code' => 'ABC123'])
        ->assertRedirect(route('student.classrooms'))
        ->assertSessionHas('status');
});

it('rejects invalid subject codes', function () {
    $student = User::factory()->create(['role' => 'student']);

    SystemSetting::create(['setting_key' => 'academic_year', 'setting_value' => '2024–2025']);
    SystemSetting::create(['setting_key' => 'current_semester', 'setting_value' => 'Second Semester']);

    actingAs($student)
        ->post(route('student.classrooms.join'), ['code' => 'INVALID'])
        ->assertRedirect(route('student.classrooms'))
        ->assertSessionHasErrors(['code']);
});

it('redirects non-students away from the join route', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);

    actingAs($faculty)
        ->post(route('student.classrooms.join'), ['code' => 'ABC123'])
        ->assertRedirect(route('faculty.dashboard'));
});
