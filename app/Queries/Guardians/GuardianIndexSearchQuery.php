<?php

namespace App\Queries\Guardians;

use App\Helpers\PersianText;
use App\Models\Guardian;
use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Guardian list search — mirrors PeopleIndexSearchQuery on the guardians table's own
 * normalized/compact search columns: alef/ya/kef folding, space-insensitive matching,
 * prefix-first matching with a 3+-character contains fallback, and CASE-based relevance
 * tiers. The distribution gates' manual household search reuses this same logic.
 */
class GuardianIndexSearchQuery
{
    public function paginate(string $search = '', string $searchField = 'all', int $perPage = 20): LengthAwarePaginator
    {
        $query = Guardian::query()->withCount('people');

        if ($this->searchTerm($search) === '') {
            return $query
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate($perPage);
        }

        $this->applyTo($query, $search, $searchField);
        $this->applyRelevanceOrdering($query, $search, $searchField);

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function applyTo(Builder $query, string $search = '', string $searchField = 'all'): Builder
    {
        $term = $this->searchTerm($search);

        if ($term === '') {
            return $query;
        }

        if ($this->needsMoreInput($term, $searchField)) {
            return $query->whereRaw('1 = 0');
        }

        $this->applySearch($query, $term, $searchField);

        return $query;
    }

    public function applyRelevanceOrdering(Builder $query, string $search, string $searchField): void
    {
        $term = $this->searchTerm($search);

        if ($term === '') {
            return;
        }

        $prefix = $this->escapeLike($term).'%';
        $compactPrefix = $this->escapeLike(str_replace(' ', '', $term)).'%';
        $contains = "%{$this->escapeLike($term)}%";

        match ($searchField) {
            'national_code' => $query->orderByRaw(
                'CASE WHEN national_code = ? THEN 0 ELSE 1 END',
                [PersianText::digitsOnly($term)]
            ),
            'mobile' => $query->orderByRaw(
                'CASE WHEN guardian_phone_number = ? THEN 0 WHEN guardian_phone_number LIKE ? THEN 1 ELSE 2 END',
                [PersianText::digitsOnly($term), $prefix]
            ),
            'full_name' => $query->orderByRaw(
                'CASE
                    WHEN normalized_full_name LIKE ? THEN 0
                    WHEN compact_full_name LIKE ? THEN 1
                    WHEN normalized_full_name LIKE ? THEN 2
                    ELSE 3
                END',
                [$prefix, $compactPrefix, $contains]
            ),
            default => ctype_digit($term)
                ? $query->orderByRaw(
                    'CASE
                        WHEN guardian_code = ? THEN 0
                        WHEN national_code = ? THEN 1
                        WHEN guardian_code LIKE ? THEN 2
                        WHEN national_code LIKE ? THEN 3
                        WHEN guardian_phone_number = ? THEN 4
                        WHEN guardian_phone_number LIKE ? THEN 5
                        ELSE 6
                    END',
                    [$term, $term, $prefix, $prefix, $term, $prefix]
                )
                : $query->orderByRaw(
                    'CASE
                        WHEN normalized_full_name LIKE ? THEN 0
                        WHEN compact_full_name LIKE ? THEN 1
                        WHEN normalized_first_name LIKE ? THEN 2
                        WHEN normalized_last_name LIKE ? THEN 3
                        WHEN normalized_full_name LIKE ? THEN 4
                        ELSE 5
                    END',
                    [$prefix, $compactPrefix, $prefix, $prefix, $contains]
                ),
        };
    }

    public function normalizeSearchTerm(string $search): string
    {
        return Person::normalizeSearchText(PersianText::normalizeDigits($search));
    }

    /**
     * Folded search term: Persian/Arabic digits converted to Latin ones, the standard
     * letter fold applied, and bare digit-and-separator input collapsed to its digits
     * so "14-90" behaves as a code lookup like List People's numeric path.
     */
    private function searchTerm(string $search): string
    {
        $normalized = $this->normalizeSearchTerm(trim($search));
        $digits = PersianText::digitsOnly($normalized);

        return $digits !== '' && preg_match('/^[\p{N}\s\-().\/]+$/u', $normalized) === 1
            ? $digits
            : $normalized;
    }

    private function needsMoreInput(string $term, string $searchField): bool
    {
        return $term !== ''
            && ! ctype_digit($term)
            && in_array($searchField, ['all', 'full_name'], true)
            && mb_strlen($term) < 2;
    }

    private function applySearch(Builder $query, string $term, string $searchField): void
    {
        $prefix = $this->escapeLike($term).'%';
        $compactPrefix = $this->escapeLike(str_replace(' ', '', $term)).'%';
        $contains = "%{$this->escapeLike($term)}%";

        match ($searchField) {
            'national_code' => $this->applyNationalCodeSearch($query, $term),
            'mobile' => $this->applyMobileSearch($query, $term),
            'full_name' => $query->where(function (Builder $q) use ($prefix, $compactPrefix, $contains, $term): void {
                $q->where('normalized_full_name', 'LIKE', $prefix)
                    ->orWhere('compact_full_name', 'LIKE', $compactPrefix);

                if (mb_strlen($term) >= 3) {
                    $q->orWhere('normalized_full_name', 'LIKE', $contains);
                }
            }),
            default => ctype_digit($term)
                ? $query->where(function (Builder $q) use ($term, $prefix): void {
                    $q->where('guardian_code', 'LIKE', $prefix)
                        ->orWhere('national_code', strlen($term) === 10 ? '=' : 'LIKE', strlen($term) === 10 ? $term : $prefix)
                        ->orWhere('guardian_phone_number', 'LIKE', "%{$this->escapeLike($term)}%");
                })
                : $query->where(function (Builder $q) use ($term, $prefix, $compactPrefix, $contains): void {
                    $q->where('normalized_full_name', 'LIKE', $prefix)
                        ->orWhere('normalized_first_name', 'LIKE', $prefix)
                        ->orWhere('normalized_last_name', 'LIKE', $prefix)
                        ->orWhere('compact_full_name', 'LIKE', $compactPrefix)
                        ->orWhere('compact_first_name', 'LIKE', $compactPrefix)
                        ->orWhere('compact_last_name', 'LIKE', $compactPrefix);

                    if (mb_strlen($term) >= 3) {
                        $q->orWhere('normalized_full_name', 'LIKE', $contains);
                    }
                }),
        };
    }

    private function applyNationalCodeSearch(Builder $query, string $term): void
    {
        $digits = PersianText::digitsOnly($term);

        if ($digits === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        strlen($digits) === 10
            ? $query->where('national_code', $digits)
            : $query->where('national_code', 'LIKE', $this->escapeLike($digits).'%');
    }

    private function applyMobileSearch(Builder $query, string $term): void
    {
        $digits = PersianText::digitsOnly($term);

        if ($digits === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('guardian_phone_number', 'LIKE', "%{$this->escapeLike($digits)}%");
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
