@extends('layouts.student')

@section('title', $classroom->name)
@section('pageDescription', 'Classroom content and submissions')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('student.classrooms') }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">← Classrooms</a>
            <h2 class="text-2xl font-bold">{{ $classroom->name }}</h2>
        </div>

        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-4">
            <h3 class="text-lg font-bold text-slate-900 mb-2">Classwork</h3>
            @php
                $allMaterials = collect($classroom->topics)->flatMap(fn($t) => $t->materials);
                $sections = ['prelim' => 'Prelim', 'midterm' => 'Midterm', 'finals' => 'Finals'];
                $userId = auth()->id();
            @endphp

            <div class="grid gap-4 md:grid-cols-3">
                @foreach($sections as $key => $label)
                    <div class="rounded-2xl border border-slate-100 p-4 bg-slate-50">
                        <h4 class="font-bold text-slate-800 mb-3">{{ $label }}</h4>
                        <div class="space-y-3 max-h-80 overflow-y-auto">
                            @php $sectionMats = $allMaterials->filter(fn($m) => (($m->grading_section ?? 'prelim') === $key))->values(); @endphp
                            @forelse($sectionMats as $mat)
                                <div class="rounded-xl bg-white border border-slate-100 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800">{{ $mat->title }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $mat->type }}</p>
                                        </div>
                                        <div class="text-right">
                                            @php $existing = $mat->submissions->firstWhere('user_id', $userId); @endphp
                                            @if($existing)
                                                <p class="text-xs text-slate-500">Submitted: <span class="font-semibold">{{ $existing->original_filename }}</span></p>
                                                <a href="{{ route('file.show', ['type' => 'submission_file', 'id' => $existing->id]) }}" class="text-xs text-green-600 hover:underline" target="_blank">Download</a>
                                            @else
                                                <form action="{{ route('student.classrooms.materials.submit', [$classroom->id, $mat->id]) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                                                    @csrf
                                                    <input type="file" name="file" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/*" required class="text-xs text-slate-500">
                                                    <button type="submit" class="text-xs rounded-xl bg-green-600 text-white px-3 py-1">Submit</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    @if($mat->body)
                                        <p class="mt-2 text-xs text-slate-500">{{ $mat->body }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 italic">No materials in this section.</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
