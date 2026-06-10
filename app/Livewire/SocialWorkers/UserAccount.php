<?php

namespace App\Livewire\SocialWorkers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.social-worker')]
class UserAccount extends Component
{
    use WithFileUploads;

    public string $username = '';
    public string $email = '';
    public bool $isEditingUsername = false;

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';
    public $newProfilePhoto = null;
    public ?string $profilePhotoPreviewUrl = null;

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);

        $this->username = (string) auth()->user()->name;
        $this->email = (string) auth()->user()->email;
    }

    public function startUsernameEdit(): void
    {
        $this->isEditingUsername = true;
    }

    public function cancelUsernameEdit(): void
    {
        $this->isEditingUsername = false;
        $this->username = (string) auth()->user()->name;
        $this->resetErrorBag('username');
    }

    public function updateUsername(): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless($this->isEditingUsername, 403);

        $validated = $this->validate([
            'username' => ['required', 'string', 'max:100', 'unique:users,name,' . auth()->id()],
        ], [
            'username.required' => 'نام کاربری الزامی است.',
            'username.unique' => 'این نام کاربری قبلا ثبت شده است.',
        ]);

        auth()->user()->update([
            'name' => mb_strtolower(trim($validated['username'])),
        ]);

        $this->isEditingUsername = false;
        session()->flash('success', 'نام کاربری با موفقیت به‌روزرسانی شد.');
    }

    public function updateEmail(): void
    {
        abort_unless(auth()->check(), 403);

        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . auth()->id()],
        ], [
            'email.required' => 'ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل معتبر نیست.',
            'email.unique' => 'این ایمیل قبلا ثبت شده است.',
        ]);

        auth()->user()->update([
            'email' => mb_strtolower(trim($validated['email'])),
        ]);

        session()->flash('success', 'ایمیل سیستمی با موفقیت به‌روزرسانی شد.');
    }

    public function updateProfilePhoto(): void
    {
        abort_unless(auth()->check(), 403);

        $validated = $this->validate([
            'newProfilePhoto' => ['required', 'image', 'max:2048'],
        ], [
            'newProfilePhoto.required' => 'انتخاب تصویر الزامی است.',
            'newProfilePhoto.image' => 'فایل انتخابی باید تصویر باشد.',
            'newProfilePhoto.max' => 'حجم تصویر باید کمتر از 2 مگابایت باشد.',
        ]);

        /** @var UploadedFile $image */
        $image = $validated['newProfilePhoto'];
        $user = auth()->user();
        $uploadDirectory = public_path('uploads/profile-photos');
        if (! File::exists($uploadDirectory)) {
            File::makeDirectory($uploadDirectory, 0755, true);
        }

        try {
            if (!empty($user->profile_photo_path)) {
                $oldPath = public_path(ltrim($user->profile_photo_path, '/'));
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            $extension = strtolower((string) $image->getClientOriginalExtension());
            $fileName = 'user-'.$user->id.'-'.time().'.'.$extension;
            $targetPath = $uploadDirectory.DIRECTORY_SEPARATOR.$fileName;
            $relativePath = 'uploads/profile-photos/'.$fileName;

            // Copy is more reliable on Windows than move from Livewire tmp files.
            if (! File::copy($image->getRealPath(), $targetPath)) {
                // Fallback: stream write via Storage facade if filesystem copy fails.
                $stream = fopen($image->getRealPath(), 'rb');
                if ($stream === false) {
                    $this->addError('newProfilePhoto', 'آپلود تصویر انجام نشد. لطفا دوباره تلاش کنید.');
                    return;
                }

                $writeResult = Storage::disk('local')->put('public/'.$relativePath, $stream);
                fclose($stream);

                if (! $writeResult) {
                    $this->addError('newProfilePhoto', 'آپلود تصویر انجام نشد. لطفا دوباره تلاش کنید.');
                    return;
                }
            }

            $user->update(['profile_photo_path' => $relativePath]);

            if ($user->socialWorker) {
                $user->socialWorker->update(['photo_path' => $relativePath]);
            }
        } catch (\Throwable $e) {
            report($e);
            $this->addError('newProfilePhoto', 'آپلود تصویر با خطا مواجه شد. لطفا دوباره تلاش کنید.');
            return;
        }

        $this->reset('newProfilePhoto');
        $this->profilePhotoPreviewUrl = asset($relativePath);
        session()->flash('success', 'تصویر پروفایل با موفقیت به‌روزرسانی شد.');
    }

    public function updatedNewProfilePhoto(): void
    {
        $this->resetErrorBag('newProfilePhoto');

        if ($this->newProfilePhoto) {
            $this->updateProfilePhoto();
        }
    }

    public function updatePassword()
    {
        abort_unless(auth()->check(), 403);

        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'رمز عبور فعلی الزامی است.',
            'new_password.required' => 'رمز عبور جدید الزامی است.',
            'new_password.min' => 'رمز عبور جدید باید حداقل 8 کاراکتر باشد.',
            'new_password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد.',
        ]);

        if (! Hash::check($validated['current_password'], (string) auth()->user()->password)) {
            $this->addError('current_password', 'رمز عبور فعلی صحیح نیست.');
            return;
        }

        auth()->user()->update([
            'password' => $validated['new_password'],
        ]);

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'رمز عبور با موفقیت تغییر کرد. لطفا با رمز جدید وارد شوید.');
    }

    public function render()
    {
        abort_unless(auth()->check(), 403);

        return view('livewire.social-workers.user-account', [
            'user' => auth()->user(),
            'profilePhotoUrl' => $this->profilePhotoPreviewUrl ?? (auth()->user()->profile_photo_path ? asset(auth()->user()->profile_photo_path) : null),
        ]);
    }
}
