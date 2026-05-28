<?php

namespace App\Models;

use App\Events\AdminModelChanged;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'academic_year',
        'semester',
        'quiz',
        'assignment',
        'exam',
        'component_scores',
        'average',
        'remarks',
        'grading_period',
    ];

    protected $casts = [
        'component_scores' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    protected static function booted(): void
    {
        static::created(function ($model) {
            event(new AdminModelChanged('grade', $model->id, 'created'));
        });

        // avoid noisy updates for grade edits; only broadcast create/delete
        static::deleted(function ($model) {
            event(new AdminModelChanged('grade', $model->id, 'deleted'));
        });
    }
}
