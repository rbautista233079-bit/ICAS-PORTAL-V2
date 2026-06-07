@extends('layouts.admin')

@section('title', 'Classroom — '.($classroom->name ?? 'Classroom'))
@section('pageDescription', 'Students enrolled in the classroom')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ $classroom->name }} <span class="text-sm font-mono text-slate-400">({{ $classroom->code }})</span></h1>
                <p class="text-sm text-slate-500 mt-1">Assigned faculty: <strong>{{ $classroom->faculty?->name ?? 'Unassigned' }}</strong></p>
            </div>

            <div class="flex items-center gap-3">
                <div class="text-sm text-slate-600">Total students: <span class="font-bold">{{ $total }}</span></div>

                <a href="{{ route('admin.classrooms') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-600">Back</a>
            </div>
        </div>

        <section class="rounded-3xl bg-white border border-slate-200 shadow-sm p-6">
            <form method="GET" action="{{ route('admin.classrooms.show', $classroom) }}" class="mb-4 flex items-center gap-3">
                <input type="text" name="q" value="{{ $search }}" placeholder="Search student number, name, or email…"
                       class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 w-72">
                <button class="rounded-xl bg-green-600 px-4 py-2 text-sm text-white">Search</button>
                @if($search)
                    <a href="{{ route('admin.classrooms.show', $classroom) }}" class="text-sm text-slate-500">Clear</a>
                @endif
            </form>

            <div class="overflow-x-auto rounded-2xl border border-slate-200">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase">Student Number</th>
                            <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase">Full Name</th>
                            <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase">Academic Level</th>
                            <th class="px-5 py-3.5 font-semibold text-slate-500 text-xs uppercase">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($students as $student)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4 text-slate-700">{{ $student->student_number ?? '—' }}</td>
                                <td class="px-5 py-4 font-medium text-slate-900">{{ $student->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $student->academic_level ?? '—' }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $student->email }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $students->links() }}</div>
        </section>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Poll for updated student list every 5 seconds (only if no active search)
            const searchInput = document.querySelector('input[name="q"]');
            setInterval(async function(){
                if (searchInput && searchInput.value.trim() === '') {
                    try {
                        const res = await fetch('{{ route('admin.classrooms.students.json', $classroom->id) }}');
                        const data = await res.json();
                        // Update the total count
                        const totalEl = document.querySelector('.text-sm.text-slate-600');
                        if (totalEl) {
                            totalEl.innerHTML = 'Total students: <span class="font-bold">' + data.total + '</span>';
                        }
                    } catch (e) { console.error(e); }
                }
            }, 5000);
        });
    </script>
@endsection
