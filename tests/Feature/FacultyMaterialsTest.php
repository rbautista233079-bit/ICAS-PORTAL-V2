<?php

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// uses(TestCase::class, RefreshDatabase::class); // Commented out to avoid duplication

it('shows add materials button to faculty and admin only', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $student = User::factory()->create(['role' => 'student']);

    $classroom = \App\Models\Classroom::create([
        'faculty_user_id' => $faculty->id,
        'name' => 'Test Class',
        'code' => 'SUB1',
        'schedule' => 'Mon 9:00 AM',
        'description' => 'Test',
        'status' => 'active',
        'academic_year' => '2024–2025',
        'semester' => 'Second Semester',
    ]);

    // Faculty should see the button
    $this->actingAs($faculty)
        ->get(route('faculty.classrooms.show', $classroom->id))
        ->assertSee('+ Add Content');

    // Student should not see the button
    $this->actingAs($student)
        ->get(route('faculty.classrooms.show', $classroom->id))
        ->assertRedirect(route('student.dashboard'));
});

it('allows faculty to upload a material and stores file and db record', function () {
    Storage::fake('local');

    $faculty = User::factory()->create(['role' => 'faculty']);

    $file = UploadedFile::fake()->create('notes.pdf', 100);

    $response = $this->actingAs($faculty)->postJson(route('faculty.materials.store'), [
        'subject_slug' => 'math301',
        'topic_index' => 0,
        'title' => 'Integration Notes',
        'body' => 'Some notes',
        'type' => 'material',
        'file' => $file,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('materials', [
        'title' => 'Integration Notes',
        'subject_slug' => 'math301',
    ]);

    $material = Material::where('title', 'Integration Notes')->first();
    expect($material)->not->toBeNull();
    Storage::disk('local')->assertExists($material->file_path);
});
