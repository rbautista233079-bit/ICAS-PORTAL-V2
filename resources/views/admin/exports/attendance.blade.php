<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Records Export</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 4px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; text-align: left; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        td { border: 1px solid #e2e8f0; padding: 8px; vertical-align: top; }
        .muted { font-size: 10px; color: #666; }
        .status-present { color: #059669; font-weight: bold; }
        .status-late { color: #d97706; font-weight: bold; }
        .status-absent { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Records</h1>
        <p>Attendance export generated on {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Course/Strand</th>
                <th>Academic Level</th>
                <th>Faculty</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                @php
                    $statusClass = match($record['status'] ?? '') {
                        'Present' => 'status-present',
                        'Late' => 'status-late',
                        default => 'status-absent',
                    };
                @endphp
                <tr>
                    <td>
                        <strong>{{ $record['student_name'] ?? '' }}</strong>
                    </td>
                    <td>{{ $record['student_course'] ?? '' }}</td>
                    <td>{{ $record['student_academic_level'] ?? '' }}</td>
                    <td>{{ $record['faculty'] ?? '' }}</td>
                    <td class="muted">{{ $record['subject'] ?? '' }}</td>
                    <td>{{ $record['attendance_date'] ?? '' }}</td>
                    <td class="{{ $statusClass }}">{{ $record['status'] ?? '' }}</td>
                    <td class="muted">{{ $record['notes'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="muted">No attendance records found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
