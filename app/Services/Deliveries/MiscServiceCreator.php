<?php

namespace App\Services\Deliveries;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceName;
use App\Models\ServiceWorkerAllocation;
use App\Support\Images\OptimizedImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Creates an operator-defined "misc" service together with its categories and
 * the single social-worker allocation that covers every category.
 *
 * Extracted verbatim from the ServiceBatchCreator Livewire component so the
 * persistence rules live in the domain layer:
 *  - a ServiceName is reused when one already matches the trimmed name, else a
 *    new one is created with the next sort id,
 *  - each category row optionally stores an optimized image and mirrors its
 *    quantity into a worker allocation for the chosen social worker,
 *  - if the transaction fails, any images already written to disk are removed
 *    so no orphaned files survive a rollback.
 *
 * The caller is responsible for validation and for converting the Jalali date
 * to the Gregorian string passed here.
 */
class MiscServiceCreator
{
    public function __construct(private readonly OptimizedImageStorage $imageStorage) {}

    /**
     * @param  array<int, array<string, mixed>>  $categories  validated miscCategories rows (name, quantity, unit, optional UploadedFile image)
     * @param  string  $distributionDate  Gregorian date string (Y-m-d) used for both start and end columns
     *
     * @throws \Throwable
     */
    public function create(
        string $name,
        string $serviceType,
        ?string $description,
        string $distributionDate,
        array $categories,
        int $socialWorkerId,
        int $createdBy,
    ): Service {
        $name = trim($name);
        $storedImagePaths = [];

        try {
            return DB::transaction(function () use ($name, $serviceType, $description, $distributionDate, $categories, $socialWorkerId, $createdBy, &$storedImagePaths): Service {
                $serviceName = ServiceName::query()
                    ->where('name', $name)
                    ->first();

                if (! $serviceName) {
                    $serviceName = new ServiceName;
                    $serviceName->fill([
                        'name' => $name,
                        'sort_id' => ((int) ServiceName::query()->max('sort_id')) + 1,
                        'created_by' => $createdBy,
                    ])->save();
                }

                $totalQuantity = collect($categories)
                    ->sum(fn (array $category): float => (float) $category['quantity']);

                $service = new Service;

                $service->fill([
                    'code' => Service::generateNextCode(),
                    'created_by' => $createdBy,
                    'quantity_delivered' => 0,
                    'name' => $name,
                    'service_name_id' => $serviceName->id,
                    'service_type' => $serviceType,
                    'supports_gate_delivery' => false,
                    'supports_home_delivery' => true,
                    'description' => $description,
                    'total_quantity' => $totalQuantity,
                    'total_service_value' => 0,
                    'district_id' => null,
                    'distribution_start_date' => $distributionDate,
                    'distribution_end_date' => $distributionDate,
                    'priority' => null,
                    'status' => 'in_distribution',
                    'status_notes' => 'خدمت متفرقه ایجادشده توسط اپراتور توزیع.',
                ])->save();

                foreach (array_values($categories) as $index => $categoryRow) {
                    $category = new ServiceCategory;
                    $category->fill([
                        'service_name_id' => $serviceName->id,
                        'name' => trim((string) $categoryRow['name']),
                        'image_path' => $this->storeCategoryImage($service, $categoryRow, $storedImagePaths),
                        'quantity' => (float) $categoryRow['quantity'],
                        'unit' => $categoryRow['unit'],
                        'value' => 0,
                        'sort_id' => $index + 1,
                        'created_by' => $createdBy,
                    ]);
                    $service->categories()->save($category);

                    $allocation = new ServiceWorkerAllocation;
                    $allocation->fill([
                        'social_worker_id' => $socialWorkerId,
                        'service_category_id' => $category->id,
                        'allocated_quantity' => (float) $categoryRow['quantity'],
                        'assigned_by_user_id' => $createdBy,
                    ]);
                    $service->workerAllocations()->save($allocation);
                }

                $service->refreshFinancialTotals();
                $service->refreshDeliveryProgress();

                return $service;
            });
        } catch (\Throwable $e) {
            $this->cleanupCategoryImages($storedImagePaths);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $categoryRow
     * @param  array<int, string>  $storedImagePaths
     */
    private function storeCategoryImage(Service $service, array $categoryRow, array &$storedImagePaths): ?string
    {
        $image = $categoryRow['image'] ?? null;

        if (! $image instanceof UploadedFile) {
            return null;
        }

        $path = $this->imageStorage->store(
            $image,
            'service-categories/'.$service->id,
            'public',
            'category'
        );

        $storedImagePaths[] = $path;

        return $path;
    }

    /**
     * @param  array<int, string>  $storedImagePaths
     */
    private function cleanupCategoryImages(array $storedImagePaths): void
    {
        foreach ($storedImagePaths as $path) {
            $this->imageStorage->delete($path, 'public');
        }
    }
}
