<?php

namespace App\Queries\Guardians;

use App\Models\Guardian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GuardianIndexSearchQuery
{
    public function paginate(string $search = '', string $searchField = 'all', int $perPage = 20): LengthAwarePaginator
    {
        $query = Guardian::withCount('people');
        $search = trim($search);

        if ($search !== '') {
            $this->applySearch($query, $search, $searchField);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    private function applySearch(Builder $query, string $search, string $searchField): void
    {
        $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"
            : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

        match ($searchField) {
            'national_code' => $query->where('national_code', 'LIKE', "%{$search}%"),
            'full_name' => $query->whereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]),
            'mobile' => $query->where('guardian_phone_number', 'LIKE', "%{$search}%"),
            default => $query->where(function (Builder $q) use ($search, $fullNameExpression) {
                $q->where('national_code', 'LIKE', "%{$search}%")
                    ->orWhere('guardian_phone_number', 'LIKE', "%{$search}%")
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhereRaw("{$fullNameExpression} LIKE ?", ["%{$search}%"]);
            }),
        };
    }
}
