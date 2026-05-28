<?php

use App\Models\User;

test('dashboard widgets link to filtered modules', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    User::factory()->create([
        'role' => 'student',
        'course' => 'BSIT',
    ]);

    User::factory()->create([
        'role' => 'student',
        'course' => 'BSHM',
    ]);

    User::factory()->create([
        'role' => 'student',
        'academic_level' => 'Senior High School',
        'strand' => 'ICT',
    ]);

    User::factory()->create([
        'role' => 'student',
        'academic_level' => 'Senior High School',
        'strand' => 'HE',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $normalized = preg_replace('/\s+/', ' ', $response->getContent());

    $response->assertOk()
        ->assertSeeText('Total Courses')
        ->assertSeeText('Total Strands')
        ->assertSeeText('Status')
        ->assertSee('href="'.e(route('admin.users')).'"', false)
        ->assertSee('href="'.e(route('admin.users', ['status' => 'active', 'role' => 'faculty'])).'"', false)
        ->assertSee('href="'.e(route('admin.users', ['status' => 'active', 'role' => 'student'])).'"', false)
        ->assertSee('href="'.e(route('admin.documents', ['status' => 'Pending'])).'"', false);

    expect($normalized)
        ->toContain('Total Courses</p> <p class="mt-2 text-2xl font-bold text-slate-900">2</p>')
        ->toContain('Total Strands</p> <p class="mt-2 text-2xl font-bold text-slate-900">2</p>');
});
