<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;

it('redirects csv imported admin to password tab', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'registration_source' => 'csv_import',
        'force_password_reset' => true,
    ]);

    actingAs($user);

    $response = $this->get(route('admin.dashboard'));

    $response
        ->assertRedirect(route('admin.settings', ['tab' => 'password']))
        ->assertSessionHas('status', 'For your security, please change your password before proceeding.');
});

it('redirects csv imported student to password tab', function () {
    $user = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
        'registration_source' => 'csv_import',
        'force_password_reset' => true,
    ]);

    actingAs($user);

    $response = $this->get(route('student.dashboard'));

    $response
        ->assertRedirect(route('student.settings', ['tab' => 'password']))
        ->assertSessionHas('status', 'For your security, please change your password before proceeding.');
});

it('redirects csv imported faculty to settings', function () {
    $user = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'registration_source' => 'csv_import',
        'force_password_reset' => true,
    ]);

    actingAs($user);

    $response = $this->get(route('faculty.dashboard'));

    $response
        ->assertRedirect(route('faculty.settings'))
        ->assertSessionHas('status', 'For your security, please change your password before proceeding.');
});

it('allows manual registration to access dashboard', function () {
    $user = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
        'registration_source' => 'manual',
        'force_password_reset' => false,
    ]);

    actingAs($user);

    $this->get(route('student.dashboard'))->assertSuccessful();
});

it('logs out admin after password update', function () {
    $user = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'password' => 'OldPass1!',
        'force_password_reset' => true,
    ]);

    actingAs($user);

    $response = $this->patch(route('admin.settings.password.update'), [
        'current_password' => 'OldPass1!',
        'password' => 'NewPass1!',
        'password_confirmation' => 'NewPass1!',
    ]);

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Password updated successfully. Please log in with your new credentials.');

    $this->assertGuest();
    $freshUser = $user->refresh();
    expect(Hash::check('NewPass1!', $freshUser->password))->toBeTrue();
    expect((bool) $freshUser->force_password_reset)->toBeFalse();
});

it('logs out student after password update', function () {
    $user = User::factory()->create([
        'role' => 'student',
        'status' => 'active',
        'password' => 'OldPass1!',
        'force_password_reset' => true,
    ]);

    actingAs($user);

    $response = $this->post(route('student.settings.password'), [
        'current_password' => 'OldPass1!',
        'new_password' => 'NewPass1!',
        'new_password_confirmation' => 'NewPass1!',
    ]);

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Password updated successfully. Please log in with your new credentials.');

    $this->assertGuest();
    $freshUser = $user->refresh();
    expect(Hash::check('NewPass1!', $freshUser->password))->toBeTrue();
    expect((bool) $freshUser->force_password_reset)->toBeFalse();
});

it('logs out faculty after password update', function () {
    $user = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'password' => 'OldPass1!',
        'force_password_reset' => true,
    ]);

    actingAs($user);

    $response = $this->post(route('faculty.settings.password'), [
        'current_password' => 'OldPass1!',
        'new_password' => 'NewPass1!',
        'new_password_confirmation' => 'NewPass1!',
    ]);

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Password updated successfully. Please log in with your new credentials.');

    $this->assertGuest();
    $freshUser = $user->refresh();
    expect(Hash::check('NewPass1!', $freshUser->password))->toBeTrue();
    expect((bool) $freshUser->force_password_reset)->toBeFalse();
});
