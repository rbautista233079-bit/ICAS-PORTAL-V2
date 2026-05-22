<?php

namespace App\Services;

use Carbon\Carbon;

class AcademicTermService
{
    protected SystemSettingsService $settings;

    public function __construct()
    {
        $this->settings = new SystemSettingsService;
    }

    public function getCurrentSemester(): string
    {
        return (string) $this->settings->get('current_semester', 'Second Semester');
    }

    public function enrollmentOpen(): bool
    {
        // Enrollment window logic removed — joining by subject code allowed anytime
        return true;
    }

    public function finalExamStarted(): bool
    {
        $exam = $this->settings->get('final_exam_start');
        if (! $exam) {
            return false;
        }

        return now()->greaterThanOrEqualTo(Carbon::parse($exam));
    }
}
