<?php

use App\Models\Classroom;
use App\Models\Material;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows a student to submit a material and faculty to view it', function () {
    Storage::fake('local');

    $faculty = User::factory()->create(['role' => 'faculty']);
    $student = User::factory()->create(['role' => 'student']);

    SystemSetting::create(['setting_key' => 'academic_year', 'setting_value' => '2024–2025']);
    SystemSetting::create(['setting_key' => 'current_semester', 'setting_value' => 'Second Semester']);

    $classroom = Classroom::create([
        'faculty_user_id' => $faculty->id,
        'name' => 'Test Class',
        'code' => 'SUB1',
        'schedule' => 'Mon 9:00 AM',
        'description' => 'Test',
        'status' => 'active',
        'academic_year' => '2024–2025',
        'semester' => 'Second Semester',
    ]);

    $material = Material::create([
        'classroom_id' => $classroom->id,
        'subject_slug' => 'sub1',
        'title' => 'Assignment 1',
        'type' => 'assignment',
        'grading_section' => 'prelim',
    ]);

    // student joins
    $student->classroomsAsStudent()->attach($classroom->id);

    $file = UploadedFile::fake()->create('homework.pdf', 100);

    actingAs($student)
        ->post(route('student.classrooms.materials.submit', [$classroom->id, $material->id]), ['file' => $file])
        ->assertRedirect(route('student.classrooms.show', $classroom->id))
        ->assertSessionHas('status');

    // assert db record
    $this->assertDatabaseCount('material_submissions', 1);

    // faculty can view submissions (direct submissions page)
    actingAs($faculty)
        ->get(route('faculty.classrooms.materials.submissions', [$classroom->id, $material->id]))
        ->assertStatus(200)
        ->assertSeeText('Assignment 1')
        ->assertSeeText($student->name);
});
