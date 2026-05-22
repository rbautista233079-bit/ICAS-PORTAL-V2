<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_slug',
        'classroom_id',
        'topic_id',
        'title',
        'body',
        'type',
        'grading_section',
        'file_path',
        'original_filename',
        'icon',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(MaterialSubmission::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    protected $casts = [
        'topic_index' => 'integer',
    ];
}
