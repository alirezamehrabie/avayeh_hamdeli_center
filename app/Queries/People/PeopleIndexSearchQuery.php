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
            ])
            ->with(['creator:id,name', 'updater:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($this->normalizeSearchTerm($search) === '') {
            return $this->prepareIndexPaginator($query->paginate($perPage));
        }

        $this->applyTo($query, $search, $searchField);

        return $this->prepareIndexPaginator($query->simplePaginate($perPage));
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

    public function needsMoreInput(string $search, string $searchField): bool
    {
        return $search !== ''
            && ! ctype_digit($search)
            && in_array($searchField, ['all', 'full_name', 'first_name', 'last_name'], true)
            && mb_strlen($search) < 2;
    }

    private function applySearch(Builder $query, string $search, string $searchField): void
    {
        $isNumeric = ctype_digit($search);
        $escapedSearch = $this->escapeLike($search);
        $prefixSearch = "{$escapedSearch}%";
        $fullNameColumn = 'normalized_full_name';
        $firstNameColumn = 'normalized_first_name';
        $lastNameColumn = 'normalized_last_name';

        match ($searchField) {
            'person_code' => strlen($search) >= 5
                ? $query->where('person_code', $search)
                : $query->where('person_code', 'LIKE', $prefixSearch),
            'full_name' => $this->applyFullNameSearch($query, $fullNameColumn, $prefixSearch, $escapedSearch),
            'first_name' => $query->where($firstNameColumn, 'LIKE', $prefixSearch),
            'last_name' => $query->where($lastNameColumn, 'LIKE', $prefixSearch),
            'national_id' => $this->applyIdentifierSearch($query, 'national_id', $search, $prefixSearch),
            'mother_national_id' => $this->applyIdentifierSearch($query, 'mother_national_id', $search, $prefixSearch),
            'father_national_id' => $this->applyIdentifierSearch($query, 'father_national_id', $search, $prefixSearch),
            default => $query->where(function (Builder $q) use ($search, $escapedSearch, $prefixSearch, $isNumeric, $fullNameColumn, $firstNameColumn, $lastNameColumn) {
                if ($isNumeric) {
                    $q->where('person_code', 'LIKE', $prefixSearch)
                        ->orWhere('national_id', strlen($search) === 10 ? '=' : 'LIKE', strlen($search) === 10 ? $search : $prefixSearch)
                        ->orWhere('mother_national_id', strlen($search) === 10 ? '=' : 'LIKE', strlen($search) === 10 ? $search : $prefixSearch)
                        ->orWhere('father_national_id', strlen($search) === 10 ? '=' : 'LIKE', strlen($search) === 10 ? $search : $prefixSearch);

                    return;
                }

                $q->where($fullNameColumn, 'LIKE', $prefixSearch)
                    ->orWhere($firstNameColumn, 'LIKE', $prefixSearch)
                    ->orWhere($lastNameColumn, 'LIKE', $prefixSearch);

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

    private function applyFullNameSearch(Builder $query, string $fullNameColumn, string $prefixSearch, string $escapedSearch): void
    {
        $query->where(function (Builder $q) use ($fullNameColumn, $prefixSearch, $escapedSearch): void {
            $q->where($fullNameColumn, 'LIKE', $prefixSearch);

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
}
