<?php

namespace App\Queries\People;

use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class PeopleIndexSearchQuery
{
    public function paginate(string $search = '', string $searchField = 'all', int $perPage = 20): LengthAwarePaginator|Paginator
    {
        $query = Person::query()
            ->select([
                'id',
                'person_code',
                'first_name',
                'last_name',
                'full_name',
                'national_id',
                'birth_day',
                'birth_month',
                'birth_year',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
                'normalized_first_name',
                'normalized_last_name',
                'normalized_full_name',
                'compact_first_name',
                'compact_last_name',
                'compact_full_name',
            ])
            ->with(['creator:id,name', 'updater:id,name']);

        if ($this->normalizeSearchTerm($search) === '') {
            return $this->prepareIndexPaginator(
                $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->paginate($perPage)
            );
        }

        $this->applyTo($query, $search, $searchField);
        $this->applyRelevanceOrdering($query, $search, $searchField);

        return $this->prepareIndexPaginator(
            $query
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->simplePaginate($perPage)
        );
    }

    public function applyTo(Builder $query, string $search = '', string $searchField = 'all'): Builder
    {
        $search = $this->normalizeSearchTerm($search);

        if ($search === '') {
            return $query;
        }

        if ($this->needsMoreInput($search, $searchField)) {
            return $query->whereRaw('1 = 0');
        }

        $this->applySearch($query, $search, $searchField);

        return $query;
    }

    public function normalizeSearchTerm(string $search): string
    {
        return Person::normalizeSearchText($search);
    }

    /**
     * Space-insensitive form of an already-normalized search term, matching
     * the compact_* columns so "محمد حسین" and "محمدحسین" search the same.
     */
    public function compactSearchTerm(string $normalizedSearch): string
    {
        return str_replace(' ', '', $normalizedSearch);
    }

    public function needsMoreInput(string $search, string $searchField): bool
    {
        return $search !== ''
            && ! ctype_digit($search)
            && in_array($searchField, ['all', 'full_name'], true)
            && mb_strlen($search) < 2;
    }

    private function applySearch(Builder $query, string $search, string $searchField): void
    {
        $isNumeric = ctype_digit($search);
        $escapedSearch = $this->escapeLike($search);
        $prefixSearch = "{$escapedSearch}%";
        $compactSearch = $this->escapeLike($this->compactSearchTerm($search));
        $compactPrefix = "{$compactSearch}%";
        $fullNameColumn = 'normalized_full_name';
        $firstNameColumn = 'normalized_first_name';
        $lastNameColumn = 'normalized_last_name';
        $compactFullNameColumn = 'compact_full_name';
        $compactFirstNameColumn = 'compact_first_name';
        $compactLastNameColumn = 'compact_last_name';

        match ($searchField) {
            'person_code' => strlen($search) >= 5
                ? $query->where('person_code', $search)
                : $query->where('person_code', 'LIKE', $prefixSearch),
            'full_name' => $this->applyFullNameSearch(
                $query,
                $fullNameColumn,
                $compactFullNameColumn,
                $prefixSearch,
                $compactPrefix,
                $escapedSearch
            ),
            'first_name' => $query->where(function (Builder $q) use ($firstNameColumn, $compactFirstNameColumn, $prefixSearch, $compactPrefix): void {
                $q->where($firstNameColumn, 'LIKE', $prefixSearch)
                    ->orWhere($compactFirstNameColumn, 'LIKE', $compactPrefix);
            }),
            'last_name' => $query->where(function (Builder $q) use ($lastNameColumn, $compactLastNameColumn, $prefixSearch, $compactPrefix): void {
                $q->where($lastNameColumn, 'LIKE', $prefixSearch)
                    ->orWhere($compactLastNameColumn, 'LIKE', $compactPrefix);
            }),
            'national_id' => $this->applyIdentifierSearch($query, 'national_id', $search, $prefixSearch),
            'mother_national_id' => $this->applyIdentifierSearch($query, 'mother_national_id', $search, $prefixSearch),
            'father_national_id' => $this->applyIdentifierSearch($query, 'father_national_id', $search, $prefixSearch),
            default => $query->where(function (Builder $q) use ($search, $escapedSearch, $prefixSearch, $compactPrefix, $isNumeric, $fullNameColumn, $firstNameColumn, $lastNameColumn, $compactFullNameColumn, $compactFirstNameColumn, $compactLastNameColumn) {
                if ($isNumeric) {
                    $q->where('person_code', 'LIKE', $prefixSearch)
                        ->orWhere('national_id', strlen($search) === 10 ? '=' : 'LIKE', strlen($search) === 10 ? $search : $prefixSearch)
                        ->orWhere('mother_national_id', strlen($search) === 10 ? '=' : 'LIKE', strlen($search) === 10 ? $search : $prefixSearch)
                        ->orWhere('father_national_id', strlen($search) === 10 ? '=' : 'LIKE', strlen($search) === 10 ? $search : $prefixSearch);

                    return;
                }

                $q->where($fullNameColumn, 'LIKE', $prefixSearch)
                    ->orWhere($firstNameColumn, 'LIKE', $prefixSearch)
                    ->orWhere($lastNameColumn, 'LIKE', $prefixSearch)
                    ->orWhere($compactFullNameColumn, 'LIKE', $compactPrefix)
                    ->orWhere($compactFirstNameColumn, 'LIKE', $compactPrefix)
                    ->orWhere($compactLastNameColumn, 'LIKE', $compactPrefix);

                if (mb_strlen($search) >= 3) {
                    $q->orWhere($fullNameColumn, 'LIKE', "%{$escapedSearch}%");
                }
            }),
        };
    }

    private function applyIdentifierSearch(Builder $query, string $column, string $search, string $prefixSearch): void
    {
        strlen($search) === 10
            ? $query->where($column, $search)
            : $query->where($column, 'LIKE', $prefixSearch);
    }

    private function applyFullNameSearch(
        Builder $query,
        string $fullNameColumn,
        string $compactFullNameColumn,
        string $prefixSearch,
        string $compactPrefix,
        string $escapedSearch
    ): void {
        $query->where(function (Builder $q) use ($fullNameColumn, $compactFullNameColumn, $prefixSearch, $compactPrefix, $escapedSearch): void {
            $q->where($fullNameColumn, 'LIKE', $prefixSearch)
                ->orWhere($compactFullNameColumn, 'LIKE', $compactPrefix);

            if (mb_strlen($escapedSearch) >= 3) {
                $q->orWhere($fullNameColumn, 'LIKE', "%{$escapedSearch}%");
            }
        });
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function prepareIndexPaginator(LengthAwarePaginator|Paginator $paginator): LengthAwarePaginator|Paginator
    {
        return $paginator->through(function (Person $person): Person {
            $person->setAppends(['birth_date']);

            return $person;
        });
    }

    private function applyRelevanceOrdering(Builder $query, string $search, string $searchField): void
    {
        $search = $this->normalizeSearchTerm($search);

        if ($search === '') {
            return;
        }

        $escapedSearch = $this->escapeLike($search);
        $prefixSearch = "{$escapedSearch}%";
        $compactPrefix = $this->escapeLike($this->compactSearchTerm($search)).'%';

        match ($searchField) {
            'person_code' => $query->orderByRaw(
                'CASE WHEN person_code = ? THEN 0 ELSE 1 END',
                [$search]
            ),
            'national_id' => $query->orderByRaw(
                'CASE WHEN national_id = ? THEN 0 ELSE 1 END',
                [$search]
            ),
            'mother_national_id' => $query->orderByRaw(
                'CASE WHEN mother_national_id = ? THEN 0 ELSE 1 END',
                [$search]
            ),
            'father_national_id' => $query->orderByRaw(
                'CASE WHEN father_national_id = ? THEN 0 ELSE 1 END',
                [$search]
            ),
            'full_name' => $query->orderByRaw(
                'CASE
                    WHEN normalized_full_name LIKE ? THEN 0
                    WHEN compact_full_name LIKE ? THEN 1
                    WHEN normalized_full_name LIKE ? THEN 2
                    ELSE 3
                END',
                [$prefixSearch, $compactPrefix, "%{$escapedSearch}%"]
            ),
            'first_name' => $query->orderByRaw(
                'CASE
                    WHEN normalized_first_name LIKE ? THEN 0
                    WHEN compact_first_name LIKE ? THEN 1
                    ELSE 2
                END',
                [$prefixSearch, $compactPrefix]
            ),
            'last_name' => $query->orderByRaw(
                'CASE
                    WHEN normalized_last_name LIKE ? THEN 0
                    WHEN compact_last_name LIKE ? THEN 1
                    ELSE 2
                END',
                [$prefixSearch, $compactPrefix]
            ),
            default => ctype_digit($search)
                ? $query->orderByRaw(
                    'CASE
                        WHEN person_code = ? THEN 0
                        WHEN national_id = ? THEN 1
                        WHEN mother_national_id = ? THEN 2
                        WHEN father_national_id = ? THEN 3
                        WHEN person_code LIKE ? THEN 4
                        WHEN national_id LIKE ? THEN 5
                        WHEN mother_national_id LIKE ? THEN 6
                        WHEN father_national_id LIKE ? THEN 7
                        ELSE 8
                    END',
                    [$search, $search, $search, $search, $prefixSearch, $prefixSearch, $prefixSearch, $prefixSearch]
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
                    [$prefixSearch, $compactPrefix, $prefixSearch, $prefixSearch, "%{$escapedSearch}%"]
                ),
        };
    }
}
