<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

it('locks only the specific student account and leaves other students unaffected', function () {
    $studentA = User::factory()->create([
        'email' => 'student-a@example.test',
        'role' => 'student',
        'password' => 'password',
    ]);

    $studentB = User::factory()->create([
        'email' => 'student-b@example.test',
        'role' => 'student',
        'password' => 'password',
    ]);

    $lockKey = 'login:student:student-a@example.test';

    RateLimiter::hit($lockKey, 1800);
    RateLimiter::hit($lockKey, 1800);
    RateLimiter::hit($lockKey, 1800);

    $lockedResponse = $this->post(route('login'), [
        'email' => $studentA->email,
        'password' => 'wrong-password',
        'role' => 'student',
    ]);

    $lockedResponse->assertSessionHasErrors('email');
    $lockedResponse->assertSessionHas('lockout_seconds');

    $unaffectedResponse = $this->post(route('login'), [
        'email' => $studentB->email,
        'password' => 'password',
        'role' => 'student',
    ]);

    $unaffectedResponse->assertRedirect();
    $unaffectedResponse->assertSessionMissing('errors');

    RateLimiter::clear($lockKey);
});
