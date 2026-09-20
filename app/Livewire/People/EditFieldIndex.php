<?php

namespace App\Livewire\People;

use App\Models\NeedsLevel;
use Livewire\Component;

class EditFieldIndex extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can('full-access'), 403);
    }

    public function render()
    {
        $needLevelRecords = NeedsLevel::query()->count();

        $levelCodes = ['E', 'D', 'C', 'B', 'A'];
        $levelDots = [
            'E' => 'bg-emerald-500',
            'D' => 'bg-lime-500',
            'C' => 'bg-amber-500',
            'B' => 'bg-orange-500',
            'A' => 'bg-rose-600',
        ];

        $fields = [
            [
                'section' => 'people-edit-field-need-level',
                'title' => 'ویرایش سطح نیازمندی',
                'icon' => 'bi bi-speedometer2',
                'iconClass' => 'text-indigo-600',
                'badge' => '۵ سطح',
                'badgeClass' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
                'chips' => array_map(fn (string $code) => [
                    'label' => $code,
                    'dot' => $levelDots[$code],
                ], $levelCodes),
            ],
        ];

        return view('livewire.people.edit-field-index', [
            'fields' => $fields,
            'needLevelRecords' => $needLevelRecords,
        ]);
    }
}
