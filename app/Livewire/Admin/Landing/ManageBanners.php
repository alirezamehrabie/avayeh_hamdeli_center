<?php

namespace App\Livewire\Admin\Landing;

use App\Models\LandingBanner;
use App\Support\Landing\LandingImageCatalog;
use App\Traits\InteractsWithLandingOrdering;
use App\Traits\InteractsWithNotificationModal;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ManageBanners extends Component
{
    use InteractsWithLandingOrdering;
    use InteractsWithNotificationModal;

    public bool $embedded = false;

    public ?int $editingBannerId = null;

    public bool $showCreateForm = false;

    public string $imagePath = '';

    public string $altText = '';

    public string $linkUrl = '';

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

    public function edit(int $bannerId): void
    {
        $this->guard();

        $banner = LandingBanner::query()->findOrFail($bannerId);

        $this->editingBannerId = $banner->id;
        $this->imagePath = (string) $banner->image_path;
        $this->altText = (string) $banner->alt_text;
        $this->linkUrl = (string) ($banner->link_url ?? '');
        $this->showCreateForm = false;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        $this->guard();

        $validated = $this->validate([
            'imagePath' => [
                'required',
                'string',
                Rule::unique('landing_banners', 'image_path')->ignore($this->editingBannerId),
            ],
            'altText' => ['required', 'string', 'max:255'],
            'linkUrl' => ['nullable', 'url', 'max:255'],
        ], [], [
            'imagePath' => 'تصویر بنر',
            'altText' => 'متن جایگزین',
            'linkUrl' => 'لینک',
        ]);

        $imagePath = $this->normalizeImagePath($validated['imagePath']);

        if (! $imagePath || ! LandingImageCatalog::exists($imagePath)) {
            $this->addError('imagePath', 'تصویر انتخاب‌شده در دسترس نیست.');

            return;
        }

        LandingBanner::query()->updateOrCreate(
            ['id' => $this->editingBannerId],
            [
                'image_path' => $imagePath,
                'alt_text' => trim($validated['altText']),
                'link_url' => filled($validated['linkUrl']) ? trim($validated['linkUrl']) : null,
                'created_by' => $this->editingBannerId
                    ? LandingBanner::query()->whereKey($this->editingBannerId)->value('created_by')
                    : auth()->id(),
            ]
        );

        $this->resetForm();
        session()->flash('landing-success', 'بنر با موفقیت ذخیره شد.');
    }

    public function toggleActive(int $bannerId): void
    {
        $this->guard();

        $banner = LandingBanner::query()->findOrFail($bannerId);

        $banner->active_status = ! $banner->active_status;
        $banner->save();

        session()->flash('landing-success', $banner->active_status
            ? 'بنر «'.$banner->alt_text.'» فعال شد و در لندینگ نمایش داده می‌شود.'
            : 'بنر «'.$banner->alt_text.'» غیرفعال شد؛ از لندینگ حذف می‌شود ولی رکورد حفظ شده است.');
    }

    public function moveUp(int $bannerId): void
    {
        $this->guard();

        $banner = LandingBanner::query()->findOrFail($bannerId);
        $this->moveLandingRowUp(LandingBanner::query(), $banner);
    }

    public function moveDown(int $bannerId): void
    {
        $this->guard();

        $banner = LandingBanner::query()->findOrFail($bannerId);
        $this->moveLandingRowDown(LandingBanner::query(), $banner);
    }

    private function resetForm(): void
    {
        $this->editingBannerId = null;
        $this->showCreateForm = false;
        $this->imagePath = '';
        $this->altText = '';
        $this->linkUrl = '';
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
        return view('livewire.admin.landing.manage-banners', [
            'banners' => LandingBanner::query()->ordered()->get(),
            'availableImages' => LandingImageCatalog::all(),
        ]);
    }
}
