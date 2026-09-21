<?php

namespace App\Livewire\Admin\Landing;

use App\Models\LandingServiceCard;
use App\Support\Images\OptimizedImageStorage;
use App\Support\Landing\LandingImageCatalog;
use App\Traits\InteractsWithLandingOrdering;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class ManageServiceCards extends Component
{
    use InteractsWithLandingOrdering;
    use InteractsWithNotificationModal;
    use WithFileUploads;

    public const UPLOAD_DIRECTORY = 'landing/services';

    public bool $embedded = false;

    public ?int $editingCardId = null;

    public bool $showCreateForm = false;

    public string $imageMode = 'existing';

    public ?UploadedFile $imageUpload = null;

    public string $imagePath = '';

    public string $title = '';

    public int $railRow = 1;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function openCreateForm(): void
    {
        $this->guard();
        $this->resetForm();
        $this->showCreateForm = true;
    }

    public function edit(int $cardId): void
    {
        $this->guard();

        $card = LandingServiceCard::query()->findOrFail($cardId);

        $this->editingCardId = $card->id;
        $this->imageMode = 'existing';
        // کارت‌های آپلودی در کاتالوغ فایل نیستند؛ انتخاب را خالی می‌گذاریم تا
        // کاربر صریحاً فایل موجود یا آپلود جدید را برگزیند.
        $this->imagePath = str_starts_with((string) $card->image_path, LandingImageCatalog::ROOT.'/')
            ? (string) $card->image_path
            : '';
        $this->title = (string) $card->title;
        $this->railRow = (int) $card->rail_row;
        $this->showCreateForm = false;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function updatedImageMode(): void
    {
        $this->imageUpload = null;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->guard();

        $this->validate([
            'imageMode' => ['required', Rule::in(['existing', 'upload'])],
            'title' => ['required', 'string', 'max:255'],
            'railRow' => ['required', Rule::in(LandingServiceCard::RAIL_ROWS)],
        ], [], [
            'imageMode' => 'منبع تصویر',
            'title' => 'عنوان کارت',
            'railRow' => 'ردیف رگال',
        ]);

        if ($this->imageMode === 'upload') {
            $this->validate([
                'imageUpload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            ], [], [
                'imageUpload' => 'فایل تصویر',
            ]);

            try {
                $imagePath = app(OptimizedImageStorage::class)->storeWebp(
                    $this->imageUpload,
                    self::UPLOAD_DIRECTORY,
                    'public',
                    'card',
                );
            } catch (\Throwable) {
                $this->openSystemErrorModal('تصویر انتخاب‌شده قابل پردازش نبود. لطفاً فایل دیگری را امتحان کنید.');

                return;
            }
        } else {
            $validated = $this->validate([
                'imagePath' => [
                    'required',
                    'string',
                    Rule::unique('landing_service_cards', 'image_path')->ignore($this->editingCardId),
                ],
            ], [], [
                'imagePath' => 'تصویر کارت',
            ]);

            $imagePath = $this->normalizeImagePath($validated['imagePath']);

            if (! $imagePath || ! LandingImageCatalog::exists($imagePath)) {
                $this->addError('imagePath', 'تصویر انتخاب‌شده در دسترس نیست.');

                return;
            }
        }

        $isNew = ! $this->editingCardId;
        $previousRailRow = $isNew ? null : (int) LandingServiceCard::query()->whereKey($this->editingCardId)->value('rail_row');
        $newRailRow = (int) $this->railRow;

        // فایل قدیمی عمداً روی دیسک می‌ماند؛ در محیط واقعی حذف خودکار قابل بازگشت نیست.
        $card = LandingServiceCard::query()->updateOrCreate(
            ['id' => $this->editingCardId],
            [
                'image_path' => $imagePath,
                'title' => trim($this->title),
                'rail_row' => $newRailRow,
                'created_by' => $isNew ? auth()->id() : LandingServiceCard::query()->whereKey($this->editingCardId)->value('created_by'),
            ]
        );

        if (! $isNew && $previousRailRow !== $newRailRow) {
            // کارت به ردیف جدید رفته؛ sort_idاش را به انتهای همان ردیف منتقل کن.
            $maxSort = (int) LandingServiceCard::query()->where('rail_row', $newRailRow)->whereKeyNot($card->id)->max('sort_id');
            $card->forceFill(['sort_id' => $maxSort + 1])->save();
        }

        $this->resetForm();
        session()->flash('landing-success', 'کارت خدمت با موفقیت ذخیره شد.');
    }

    public function toggleActive(int $cardId): void
    {
        $this->guard();

        $card = LandingServiceCard::query()->findOrFail($cardId);

        $card->active_status = ! $card->active_status;
        $card->save();

        session()->flash('landing-success', $card->active_status
            ? 'کارت «'.$card->title.'» فعال شد و در لندینگ نمایش داده می‌شود.'
            : 'کارت «'.$card->title.'» غیرفعال شد؛ از لندینگ حذف می‌شود ولی رکورد حفظ شده است.');
    }

    public function moveUp(int $cardId): void
    {
        $this->guard();

        $card = LandingServiceCard::query()->findOrFail($cardId);
        $this->moveLandingRowUp(LandingServiceCard::query()->where('rail_row', $card->rail_row), $card);
    }

    public function moveDown(int $cardId): void
    {
        $this->guard();

        $card = LandingServiceCard::query()->findOrFail($cardId);
        $this->moveLandingRowDown(LandingServiceCard::query()->where('rail_row', $card->rail_row), $card);
    }

    private function resetForm(): void
    {
        $this->editingCardId = null;
        $this->showCreateForm = false;
        $this->imageMode = 'existing';
        $this->imageUpload = null;
        $this->imagePath = '';
        $this->title = '';
        $this->railRow = 1;
        $this->resetErrorBag();
    }

    private function normalizeImagePath(string $path): ?string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        return str_starts_with($normalized, LandingImageCatalog::ROOT.'/') ? $normalized : null;
    }

    private function guard(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
    }

    public function render()
    {
        return view('livewire.admin.landing.manage-service-cards', [
            'cards' => LandingServiceCard::query()->ordered()->get()->groupBy('rail_row'),
            'availableImages' => LandingImageCatalog::all(),
        ]);
    }
}
