<?php

namespace App\Livewire\DistributionOperators;

use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.distribution-operator')]
class ServiceList extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);
    }

    public function render()
    {
        return view('livewire.distribution-operators.service-list', [
            'services' => $this->operatorServicesQuery()
                ->with([
                    'serviceName',
                    'categories',
                    'socialWorkers',
                    'workerAllocations.socialWorker',
                ])
                ->latest()
                ->get(),
            'unitOptions' => Service::unitOptions(),
        ]);
    }

    protected function operatorServicesQuery(): Builder
    {
        abort_unless(auth()->check() && auth()->user()->can('access-distribution-operator-panel'), 403);

        return Service::query()
            ->where(function (Builder $query): void {
                $query->where(function (Builder $miscQuery): void {
                    $miscQuery->where('created_by', auth()->id())
                        ->whereHas('creator', function (Builder $creatorQuery): void {
                            $creatorQuery->where('access_level', \App\Models\User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR);
                        });
                })->orWhereHas('workerAllocations', function (Builder $allocationQuery): void {
                    $allocationQuery->where('assigned_by_user_id', auth()->id());
                });
            });
    }
}
