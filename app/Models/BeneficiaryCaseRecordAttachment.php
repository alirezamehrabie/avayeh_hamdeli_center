<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryCaseRecordAttachment extends Model
{
    protected $fillable = [
        'beneficiary_case_record_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'beneficiary_case_record_id' => 'integer',
            'uploaded_by' => 'integer',
            'size' => 'integer',
        ];
    }

    public function caseRecord(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryCaseRecord::class, 'beneficiary_case_record_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return route('admin.people.case-file.attachments.show', ['attachment' => $this]);
    }

    public function getSizeLabelAttribute(): string
    {
        if (! $this->size) {
            return '';
        }

        if ($this->size >= 1024 * 1024) {
            return number_format($this->size / (1024 * 1024), 1).' MB';
        }

        return number_format($this->size / 1024, 1).' KB';
    }
}
