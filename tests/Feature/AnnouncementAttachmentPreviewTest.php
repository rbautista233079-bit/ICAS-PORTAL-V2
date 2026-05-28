<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('serves announcement attachments inline for browser preview', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin']);

    Storage::disk('local')->put('announcements/sample.txt', 'preview content');

    $announcement = Announcement::create([
        'title' => 'Quarterly Update',
        'content' => 'Attachment preview test',
        'audience' => 'all',
        'attachment_path' => 'announcements/sample.txt',
        'attachment_filename' => 'preview.txt',
        'attachment_mime' => 'text/plain',
        'created_by' => $admin->id,
    ]);

    actingAs($admin)
        ->get(route('file.show', ['type' => 'announcement_attachment', 'id' => $announcement->id]))
        ->assertStatus(200)
        ->assertHeader('Content-Disposition', 'inline; filename="preview.txt"');
});
