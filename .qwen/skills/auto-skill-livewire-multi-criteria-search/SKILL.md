---
name: livewire-multi-criteria-search
description: Extend Livewire search functionality to support multiple criteria with database-level filtering and proper indexing
source: auto-skill
extracted_at: '2026-06-14T13:30:03.694Z'
---

# Livewire Multi-Criteria Search Implementation

This skill documents the approach for extending Livewire search functionality to support multiple criteria (e.g., father's national ID, mother's national ID, etc.) while maintaining performance and clean architecture.

## Key Steps

### 1. Frontend - Blade Template
- Add new search option to the dropdown select element
- Maintain consistent HTML structure and accessibility attributes
- Preserve existing Livewire bindings (wire:model.live.debounce.300ms)

### 2. Backend - Livewire Component
- Extend the `getPeopleProperty()` method's match statement with new cases
- Add dedicated case for each new search field: `'field_name' => $query->where('field_name', 'LIKE', "%{$search}%")`
- Update the default "all fields" search condition to include the new field
- Ensure proper indentation and comma placement for PHP syntax

### 3. Database Considerations
- Verify the column exists in the database table
- Confirm indexing exists for performance (e.g., migration adding `->index('field_name')`)
- Use `LIKE` queries with proper wildcard placement for partial matching

### 4. Integration Points
- The `updatingSearch()` method automatically resets pagination
- Livewire's reactive binding handles debounced updates
- Existing search field validation and error handling remain unchanged

## Best Practices

- Always add new search fields to both the specific case AND the default "all fields" condition
- Maintain consistent indentation and formatting in match statements
- Verify PHP syntax after modifications with `php -l`
- Check that the Blade template option value matches the PHP case value exactly
- Leverage existing Livewire pagination reset behavior rather than duplicating logic

## Example Implementation

```php
// In Livewire component's getPeopleProperty() method
match ($this->searchField) {
    'person_code' => $query->where('person_code', 'LIKE', "%{$search}%"),
    'father_national_id' => $query->where('father_national_id', 'LIKE', "%{$search}%"), // New field
    // ... other existing fields
    default => $query->where(function ($q) use ($search) {
        $q->where('person_code', 'LIKE', "%{$search}%")
            ->orWhere('father_national_id', 'LIKE', "%{$search}%") // New field
            // ... other existing fields
    }),
};
```