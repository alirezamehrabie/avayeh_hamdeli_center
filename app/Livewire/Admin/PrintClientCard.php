<?php

namespace App\Livewire\Admin;

use App\Models\Person;
use App\Models\QrIdentity;
use App\Models\SocialWorker;
use App\Services\QrIdentityService;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PrintClientCard extends Component
{
    public string $search = '';

    public string $searchField = 'all';

    /** @var array<int, array{id: int, person_code: string, full_name: string, national_id: string}> */
    public array $printList = [];

    public bool $showSearchResults = false;

    public string $socialWorkerSearch = '';

    public ?int $selectedSocialWorkerId = null;

    public array $socialWorkerOptions = [];

    public bool $loadingSocialWorkerClients = false;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
    }

    public function updatedSearch(): void
    {
        $this->showSearchResults = mb_strlen(trim($this->search)) >= 2;
    }

    public function getSearchResultsProperty(): array
    {
        $term = trim($this->search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $query = Person::query()->select(['id', 'first_name', 'last_name', 'national_id', 'person_code']);

        if ($this->searchField === 'national_id') {
            $query->where('national_id', 'LIKE', "%{$term}%");
        } elseif ($this->searchField === 'full_name') {
            $normalized = Person::normalizeSearchText($term);
            $query->where('normalized_full_name', 'LIKE', "%{$normalized}%");
        } else {
            $normalized = Person::normalizeSearchText($term);
            $query->where(function ($q) use ($term, $normalized) {
                $q->where('normalized_full_name', 'LIKE', "%{$normalized}%")
                    ->orWhere('national_id', 'LIKE', "%{$term}%")
                    ->orWhere('person_code', 'LIKE', "%{$term}%");
            });
        }

        return $query->limit(20)->get()->map(fn (Person $p) => [
            'id' => $p->id,
            'full_name' => $p->full_name ?: trim($p->first_name . ' ' . $p->last_name) ?: '-',
            'national_id' => $p->national_id ?: '-',
            'person_code' => $p->person_code ?: '-',
        ])->toArray();
    }

    public function addToPrintList(int $personId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        foreach ($this->printList as $item) {
            if ($item['id'] === $personId) {
                session()->flash('error', 'این مددجو قبلاً به لیست چاپ اضافه شده است.');
                return;
            }
        }

        $person = Person::query()->find($personId);

        if (! $person) {
            session()->flash('error', 'مددجو یافت نشد.');
            return;
        }

        $this->printList[] = [
            'id' => $person->id,
            'full_name' => $person->full_name ?: trim($person->first_name . ' ' . $person->last_name) ?: '-',
            'national_id' => $person->national_id ?: '-',
            'person_code' => $person->person_code ?: '-',
        ];

        $this->search = '';
        $this->showSearchResults = false;
    }

    public function removeFromPrintList(int $index): void
    {
        if (isset($this->printList[$index])) {
            unset($this->printList[$index]);
            $this->printList = array_values($this->printList);
        }
    }

    public function clearPrintList(): void
    {
        $this->printList = [];
    }

    public function exportToCsv(): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (empty($this->printList)) {
            session()->flash('error', 'لیست چاپ خالی است.');
            return redirect()->back();
        }

        $filename = 'client-cards-' . now()->format('Y-m-d-His') . '.csv';
        $rows = $this->buildExportRows();

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['QR Code', 'نام کامل', 'کد ملی', 'کد مددجو'], ',');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ',');
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportToExcel(): ?\Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (empty($this->printList)) {
            session()->flash('error', 'لیست چاپ خالی است.');
            return null;
        }

        $rows = $this->buildExportRows();
        $filename = 'client-cards-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new class($rows) implements FromArray, WithHeadings, ShouldAutoSize, WithEvents {
            private array $rows;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return ['QR Code', 'نام کامل', 'کد ملی', 'کد مددجو'];
            }

            public function registerEvents(): array
            {
                return [
                    AfterSheet::class => function (AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();
                        $lastCol = $sheet->getHighestColumn();
                        $lastRow = $sheet->getHighestRow();
                        $fullRange = "A1:{$lastCol}{$lastRow}";

                        $sheet->setRightToLeft(true);

                        $sheet->getStyle($fullRange)->applyFromArray([
                            'font' => ['name' => 'B Zar', 'size' => 11],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'readorder' => 2,
                            ],
                        ]);

                        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                            'font' => ['name' => 'B Zar', 'bold' => true, 'size' => 12],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                        $sheet->getRowDimension(1)->setRowHeight(28);
                    },
                ];
            }
        }, $filename);
    }

    public function updatedSocialWorkerSearch(): void
    {
        $term = trim($this->socialWorkerSearch);

        if (mb_strlen($term) < 2) {
            $this->socialWorkerOptions = [];
            return;
        }

        $normalized = Person::normalizeSearchText($term);

        $this->socialWorkerOptions = SocialWorker::query()
            ->select(['id', 'first_name', 'last_name', 'worker_code'])
            ->where(function ($q) use ($term, $normalized) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$normalized}%"])
                    ->orWhere('worker_code', 'LIKE', "%{$term}%");
            })
            ->orderBy('first_name')
            ->limit(15)
            ->get()
            ->map(fn (SocialWorker $sw) => [
                'id' => $sw->id,
                'name' => trim($sw->first_name . ' ' . $sw->last_name),
                'code' => (string) $sw->worker_code,
            ])
            ->toArray();
    }

    public function selectSocialWorker(int $socialWorkerId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $this->selectedSocialWorkerId = $socialWorkerId;
        $this->socialWorkerSearch = '';
        $this->socialWorkerOptions = [];
    }

    public function clearSocialWorkerSelection(): void
    {
        $this->selectedSocialWorkerId = null;
    }

    public function addSocialWorkerClientsToPrintList(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (! $this->selectedSocialWorkerId) {
            session()->flash('error', 'لطفاً ابتدا یک مددکار انتخاب کنید.');
            return;
        }

        $this->loadingSocialWorkerClients = true;

        $existingIds = collect($this->printList)->pluck('id')->all();

        $people = Person::query()
            ->select(['people.id', 'people.first_name', 'people.last_name', 'people.national_id', 'people.person_code'])
            ->join('guardians', 'people.guardian_id', '=', 'guardians.id')
            ->where('guardians.social_worker_id', $this->selectedSocialWorkerId)
            ->whereNotIn('id', $existingIds)
            ->get();

        $addedCount = 0;

        foreach ($people as $person) {
            $this->printList[] = [
                'id' => $person->id,
                'full_name' => $person->full_name ?: trim($person->first_name . ' ' . $person->last_name) ?: '-',
                'national_id' => $person->national_id ?: '-',
                'person_code' => $person->person_code ?: '-',
            ];
            $addedCount++;
        }

        $this->loadingSocialWorkerClients = false;

        if ($addedCount === 0) {
            session()->flash('error', 'تمام مددجویان این مددکار قبلاً به لیست چاپ اضافه شده‌اند.');
        } else {
            session()->flash('success', "{$addedCount} مددجو از مددکار انتخاب‌شده به لیست چاپ اضافه شد.");
        }
    }

    public function getSelectedSocialWorkerProperty(): ?SocialWorker
    {
        if (! $this->selectedSocialWorkerId) {
            return null;
        }

        return SocialWorker::query()->find($this->selectedSocialWorkerId);
    }

    public function render()
    {
        return view('livewire.admin.print-client-card');
    }

    private function buildExportRows(): array
    {
        $service = app(QrIdentityService::class);
        $rows = [];

        foreach ($this->printList as $item) {
            $identity = QrIdentity::query()
                ->where('subject_type', QrIdentity::SUBJECT_PERSON)
                ->where('subject_id', $item['id'])
                ->where('status', QrIdentity::STATUS_ACTIVE)
                ->latest('id')
                ->first();

            if (! $identity) {
                $issued = $service->ensureActiveFor(
                    Person::query()->find($item['id']),
                    auth()->id()
                );
                $identity = $issued;
            }

            $rows[] = [
                $identity->public_code,
                $item['full_name'],
                $item['national_id'],
                $item['person_code'],
            ];
        }

        return $rows;
    }
}
