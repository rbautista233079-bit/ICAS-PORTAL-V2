<!DOCTYPE html>
<html>
<head>
    <title>Official Academic Record</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #666; }
        .info-section { margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px 0; }
        .grades-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .grades-table th { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; text-align: left; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .grades-table td { border: 1px solid #e2e8f0; padding: 10px; vertical-align: top; }
        .footer { margin-top: 50px; text-align: right; font-size: 10px; color: #999; }
        .status-verified { color: #059669; font-weight: bold; }
        .status-pending { color: #d97706; font-weight: bold; }
        .gpa { font-weight: bold; }
        .components { font-size: 9px; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ICAS PORTAL ACADEMIC RECORD</h1>
        <p>Institutional Centralized Academic System</p>
        <p>Official Grade Report generated on {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <div class="info-section">
        <table class="info-table">
            <tr>
                <td style="width: 15%;"><strong>Report Scope:</strong></td>
                <td>{{ $scope }}</td>
                <td style="width: 15%; text-align: right;"><strong>Status:</strong></td>
                <td style="width: 15%; text-align: right;">Official</td>
            </tr>
            <tr>
                <td><strong>Semester:</strong></td>
                <td>{{ $semester }}</td>
                <td style="text-align: right;"><strong>Grading Period:</strong></td>
                <td style="text-align: right;">{{ $gradingPeriod }}</td>
            </tr>
        </table>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Course / Level</th>
                <th>Subject</th>
                <th>Components / Breakdown</th>
                <th>Final Grade</th>
                <th>GPA</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                @php
                    $gradingService = new \App\Services\GradingService();
                    $gpa = $gradingService->toGpa((float) $record->average) ?? 'N/A';

                    $classroom = $classroomMap[$record->subject_id] ?? null;

                    $componentsStr = 'Standard Calculation';
                    if ($record->component_scores) {
                        $compList = [];
                        foreach ($record->component_scores as $key => $val) {
                            $compList[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $val . '%';
                        }
                        $componentsStr = implode(', ', $compList);
                    }
                @endphp
                <tr>
                    <td>
                        <strong>{{ $record->student->name ?? 'Unknown' }}</strong><br>
                        <span style="font-size: 9px; color: #666;">{{ $record->student->email ?? '' }}</span>
                    </td>
                    <td>
                        {{ str_contains($record->student->academic_level ?? '', 'Senior High School') ? $record->student->strand : $record->student->course }}<br>
                        <span style="font-size: 9px; color: #666;">{{ $record->student->academic_level }}</span>
                    </td>
                    <td>
                        {{ $classroom?->name ?? $record->subject_id }}<br>
                        <span style="font-size: 9px; color: #666;">Code: {{ $record->subject_id }}</span>
                    </td>
                    <td class="components">
                        {{ $componentsStr }}
                    </td>
                    <td><strong>{{ number_format((float) $record->average, 2) }}%</strong></td>
                    <td class="gpa">{{ $gpa }}</td>
                    <td>
                        @if($record->average !== null)
                            <span class="status-verified">Recorded</span>
                        @else
                            <span class="status-pending">Pending</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>&copy; {{ date('Y') }} ICAS Portal. All rights reserved.</p>
    </div>
</body>
</html>
