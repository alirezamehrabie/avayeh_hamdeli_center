<?php

namespace App\Models;

use App\Models\Concerns\InvalidatesIncompleteCasesQueueCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportOrganization extends Model
{
    use HasFactory, InvalidatesIncompleteCasesQueueCache;

    // نام جدول به صورت استاندارد لاراول (جمع) است، اما برای اطمینان تعریف می‌کنیم
    protected $table = 'support_organizations';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function supportCoverages(): HasMany
    {
        return $this->hasMany(SupportCoverage::class, 'support_organization_id');
    }
}
