<?php

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('displays final exam start date on student schedule', function () {
    $student = User::factory()->create(['role' => 'student']);
    $examDate = '2025-05-12';

    SystemSetting::create(['setting_key' => 'final_exam_start', 'setting_value' => $examDate]);

    $response = actingAs($student)->get(route('student.schedule'));

    $response->assertStatus(200);
    $response->assertSee('Final Exam Period');
    $response->assertSee('May 12, 2025');
});

it('displays final exam start date on faculty schedule', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);
    $examDate = '2025-05-12';

    SystemSetting::create(['setting_key' => 'final_exam_start', 'setting_value' => $examDate]);

    $response = actingAs($faculty)->get(route('faculty.schedule'));

    $response->assertStatus(200);
    $response->assertSee('Final Exam Period');
    $response->assertSee('May 12, 2025');
});

it('hides exam banner on student schedule when no date is set', function () {
    $student = User::factory()->create(['role' => 'student']);

    $response = actingAs($student)->get(route('student.schedule'));

    $response->assertStatus(200);
    $response->assertDontSee('Final Exam Period');
});

it('hides exam banner on faculty schedule when no date is set', function () {
    $faculty = User::factory()->create(['role' => 'faculty']);

    $response = actingAs($faculty)->get(route('faculty.schedule'));

    $response->assertStatus(200);
    $response->assertDontSee('Final Exam Period');
});

it('updates student schedule when admin changes exam date', function () {
    $student = User::factory()->create(['role' => 'student']);
    $initialDate = '2025-05-12';

    SystemSetting::create(['setting_key' => 'final_exam_start', 'setting_value' => $initialDate]);

    $response = actingAs($student)->get(route('student.schedule'));
    $response->assertSee('May 12, 2025');

    // Admin updates the exam date
    $newDate = '2025-05-19';
    SystemSetting::where('setting_key', 'final_exam_start')->update(['setting_value' => $newDate]);

    $response = actingAs($student)->get(route('student.schedule'));
    $response->assertSee('May 19, 2025');
    $response->assertDontSee('May 12, 2025');
});
