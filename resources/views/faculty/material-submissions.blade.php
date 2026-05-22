@extends('layouts.faculty')

@section('title', 'Submissions')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('faculty.classrooms.show', $classroom->id) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition">← Back</a>
            <h2 class="text-2xl font-bold">{{ $material->title }} — Submissions</h2>
        </div>

        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            @if(count($material->submissions) > 0)
                <div class="space-y-3">
                    @foreach($material->submissions as $sub)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 p-3">
                            <div>
                                <p class="text-sm font-bold">{{ $sub->user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $sub->original_filename }} · {{ $sub->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('file.show', ['type' => 'submission_file', 'id' => $sub->id]) }}" class="text-sm text-green-600 hover:underline" target="_blank">Download</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-500">No submissions yet for this material.</p>
            @endif
        </section>
    </div>
@endsection
