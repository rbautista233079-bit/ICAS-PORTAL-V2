<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Material;
use App\Models\MaterialSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function show(Request $request, string $type, int $id): StreamedResponse
    {
        $viewer = $request->user();
        if (! $viewer) {
            abort(403);
        }

        switch ($type) {
            case 'profile_image':
                $user = User::findOrFail($id);
                if (! $user->profile_photo) {
                    abort(404);
                }
                if ($viewer->role !== 'admin' && (int) $viewer->id !== (int) $user->id) {
                    abort(403);
                }
                return $this->streamInline(
                    $user->profile_photo,
                    $this->defaultFilename($user->profile_photo, 'profile-photo'),
                    $user->profile_image_mime
                );

            case 'receipt_proof':
                $user = User::findOrFail($id);
                if (! $user->receipt_proof) {
                    abort(404);
                }
                if ($viewer->role !== 'admin' && (int) $viewer->id !== (int) $user->id) {
                    abort(403);
                }
                return $this->streamInline(
                    $user->receipt_proof,
                    $this->defaultFilename($user->receipt_proof, 'receipt-proof'),
                    $user->receipt_proof_mime
                );

            case 'student_id_proof':
                $user = User::findOrFail($id);
                if (! $user->student_id_proof) {
                    abort(404);
                }
                if ($viewer->role !== 'admin' && (int) $viewer->id !== (int) $user->id) {
                    abort(403);
                }
                return $this->streamInline(
                    $user->student_id_proof,
                    $this->defaultFilename($user->student_id_proof, 'student-id'),
                    $user->student_id_proof_mime
                );

            case 'announcement_attachment':
                $announcement = Announcement::findOrFail($id);
                $this->authorizeAnnouncement($announcement, $viewer);
                if (! $announcement->attachment_path) {
                    abort(404);
                }
                return $this->streamDownload(
                    $announcement->attachment_path,
                    $announcement->attachment_filename ?? 'attachment',
                    $announcement->attachment_mime
                );

            case 'material_file':
                $material = Material::with('classroom')->findOrFail($id);
                if (! $material->file_path) {
                    abort(404);
                }
                $this->authorizeMaterial($material, $viewer);
                return $this->streamDownload(
                    $material->file_path,
                    $material->original_filename ?? 'material',
                    null
                );

            case 'submission_file':
                $submission = MaterialSubmission::with('material.classroom')->findOrFail($id);
                if (! $submission->file_path) {
                    abort(404);
                }
                $this->authorizeSubmission($submission, $viewer);
                return $this->streamDownload(
                    $submission->file_path,
                    $submission->original_filename ?? 'submission',
                    null
                );

            default:
                abort(404);
        }
    }

    private function streamInline(string $path, string $filename, ?string $mime): StreamedResponse
    {
        return $this->streamWithDisposition($path, $filename, $mime, 'inline');
    }

    private function streamDownload(string $path, string $filename, ?string $mime): StreamedResponse
    {
        return $this->streamWithDisposition($path, $filename, $mime, 'attachment');
    }

    private function streamWithDisposition(
        string $path,
        string $filename,
        ?string $mime,
        string $disposition
    ): StreamedResponse {
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $contentType = $mime ?: ($disk->mimeType($path) ?? 'application/octet-stream');

        return $disk->response($path, $filename, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        ]);
    }

    private function defaultFilename(string $path, string $fallback): string
    {
        $base = basename($path);
        return $base !== '' ? $base : $fallback;
    }

    private function authorizeAnnouncement(Announcement $announcement, User $viewer): void
    {
        if ($viewer->role === 'admin') {
            return;
        }

        if ($announcement->audience === 'all' || $announcement->audience === $viewer->role) {
            return;
        }

        abort(403);
    }

    private function authorizeMaterial(Material $material, User $viewer): void
    {
        if ($viewer->role === 'admin') {
            return;
        }

        if ($material->classroom_id) {
            if ($viewer->role === 'faculty' && $material->classroom && (int) $material->classroom->faculty_user_id === (int) $viewer->id) {
                return;
            }

            if ($viewer->role === 'student' && $viewer->classroomsAsStudent()->where('classrooms.id', $material->classroom_id)->exists()) {
                return;
            }
        } elseif ($viewer->role === 'faculty') {
            return;
        }

        abort(403);
    }

    private function authorizeSubmission(MaterialSubmission $submission, User $viewer): void
    {
        if ($viewer->role === 'admin') {
            return;
        }

        if ((int) $submission->user_id === (int) $viewer->id) {
            return;
        }

        $material = $submission->material;
        if ($viewer->role === 'faculty' && $material && $material->classroom && (int) $material->classroom->faculty_user_id === (int) $viewer->id) {
            return;
        }

        abort(403);
    }
}
