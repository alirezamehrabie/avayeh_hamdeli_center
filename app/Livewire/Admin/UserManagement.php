<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserActionRequest;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class UserManagement extends Component
{
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $access_level = User::ACCESS_LEVEL_REGULAR;

    public function createUser(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless(Schema::hasColumn('users', 'access_level'), 500, 'users.access_level column is required.');
        $currentUser = auth()->user();

        $validated = $this->validate([
            'username' => ['required', 'string', 'max:100', 'unique:users,name'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'access_level' => ['required', 'in:'.implode(',', [
                User::ACCESS_LEVEL_MANAGER,
                User::ACCESS_LEVEL_ADMIN,
                User::ACCESS_LEVEL_REGULAR,
            ])],
        ], [
            'username.required' => 'نام کاربری الزامی است.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'access_level.required' => 'انتخاب سطح دسترسی الزامی است.',
        ]);

        $username = mb_strtolower($validated['username']);

        if ($validated['access_level'] === User::ACCESS_LEVEL_ADMIN && ! $currentUser->isManager()) {
            $this->addError('access_level', 'You are not allowed to create an Admin user.');
            return;
        }

        if ($validated['access_level'] === User::ACCESS_LEVEL_MANAGER) {
            session()->flash('success', 'ایجاد Manager جدید مجاز نیست. فقط حساب Manager اصلی سیستم وجود دارد.');
            return;
        }

        User::create([
            'name' => $username,
            'email' => $username . '@local.system',
            'password' => $validated['password'],
            'access_level' => $validated['access_level'],
            'is_admin' => in_array($validated['access_level'], [User::ACCESS_LEVEL_MANAGER, User::ACCESS_LEVEL_ADMIN], true),
        ]);

        $this->reset(['username', 'password', 'password_confirmation', 'access_level']);
        $this->access_level = User::ACCESS_LEVEL_REGULAR;
        session()->flash('success', 'کاربر جدید با موفقیت ایجاد شد.');
    }

    public function deleteUser(int $userId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        if (auth()->id() === $userId) {
            session()->flash('success', 'امکان حذف حساب کاربری فعلی وجود ندارد.');
            return;
        }

        $user = User::query()->find($userId);
        if (!$user) {
            return;
        }

        if ($user->isProtectedManagerAccount()) {
            session()->flash('success', 'حساب Manager اصلی سیستم کاملاً محافظت شده و قابل حذف نیست.');
            return;
        }

        $actor = auth()->user();
        if (! $actor->isManager() && $user->access_level === User::ACCESS_LEVEL_ADMIN) {
            $this->queueManagerApproval($user->id, UserActionRequest::ACTION_DELETE);
            session()->flash('success', 'درخواست حذف ادمین ثبت شد و منتظر تایید Manager است.');
            return;
        }

        $user->delete();
        session()->flash('success', 'کاربر با موفقیت حذف شد.');
    }

    public function setAccessLevel(int $userId, string $accessLevel): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless(Schema::hasColumn('users', 'access_level'), 500, 'users.access_level column is required.');

        abort_unless(in_array($accessLevel, [
            User::ACCESS_LEVEL_MANAGER,
            User::ACCESS_LEVEL_ADMIN,
            User::ACCESS_LEVEL_REGULAR,
        ], true), 422);

        if (auth()->id() === $userId) {
            session()->flash('success', 'امکان تغییر سطح دسترسی حساب کاربری فعلی وجود ندارد.');
            return;
        }

        $user = User::query()->find($userId);
        if (!$user) {
            return;
        }

        if ($user->isProtectedManagerAccount()) {
            session()->flash('success', 'هیچ کاربری اجازه ویرایش یا تغییر حساب Manager اصلی سیستم را ندارد.');
            return;
        }

        if ($accessLevel === User::ACCESS_LEVEL_MANAGER) {
            session()->flash('success', 'تنها حساب Manager اصلی سیستم معتبر است و قابل انتساب به کاربر دیگر نیست.');
            return;
        }

        $actor = auth()->user();
        if (! $actor->isManager() && $user->access_level === User::ACCESS_LEVEL_REGULAR && $accessLevel === User::ACCESS_LEVEL_ADMIN) {
            $this->queueManagerApproval($user->id, UserActionRequest::ACTION_PROMOTE);
            session()->flash('success', 'درخواست ارتقا به Admin ثبت شد و منتظر تایید Manager است.');
            return;
        }

        if (! $actor->isManager() && $user->access_level === User::ACCESS_LEVEL_ADMIN && $accessLevel === User::ACCESS_LEVEL_REGULAR) {
            $this->queueManagerApproval($user->id, UserActionRequest::ACTION_DOWNGRADE);
            session()->flash('success', 'درخواست تنزل سطح ادمین ثبت شد و منتظر تایید Manager است.');
            return;
        }

        $user->update([
            'access_level' => $accessLevel,
            'is_admin' => $accessLevel === User::ACCESS_LEVEL_ADMIN,
        ]);
        session()->flash('success', 'سطح دسترسی کاربر با موفقیت تغییر کرد.');
    }

    public function approveRequest(int $requestId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless(auth()->user()->isManager(), 403);

        $request = UserActionRequest::query()
            ->where('status', UserActionRequest::STATUS_PENDING)
            ->find($requestId);

        if (! $request) {
            return;
        }

        $target = User::query()->find($request->target_user_id);
        if (! $target || $target->isProtectedManagerAccount()) {
            $request->update([
                'status' => UserActionRequest::STATUS_REJECTED,
                'approved_by_user_id' => auth()->id(),
            ]);
            return;
        }

        if ($request->action_type === UserActionRequest::ACTION_DELETE) {
            $target->delete();
        } elseif ($request->action_type === UserActionRequest::ACTION_DOWNGRADE) {
            $target->update([
                'access_level' => User::ACCESS_LEVEL_REGULAR,
                'is_admin' => false,
            ]);
        } elseif ($request->action_type === UserActionRequest::ACTION_PROMOTE) {
            $target->update([
                'access_level' => User::ACCESS_LEVEL_ADMIN,
                'is_admin' => true,
            ]);
        }

        $request->update([
            'status' => UserActionRequest::STATUS_APPROVED,
            'approved_by_user_id' => auth()->id(),
        ]);

        session()->flash('success', 'درخواست با موفقیت تایید و اجرا شد.');
    }

    public function rejectRequest(int $requestId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);
        abort_unless(auth()->user()->isManager(), 403);

        $request = UserActionRequest::query()
            ->where('status', UserActionRequest::STATUS_PENDING)
            ->find($requestId);

        if (! $request) {
            return;
        }

        $request->update([
            'status' => UserActionRequest::STATUS_REJECTED,
            'approved_by_user_id' => auth()->id(),
        ]);

        session()->flash('success', 'درخواست رد شد.');
    }

    private function queueManagerApproval(int $targetUserId, string $actionType): void
    {
        if (! Schema::hasTable('user_action_requests')) {
            abort(500, 'user_action_requests table is required.');
        }

        $existingPending = UserActionRequest::query()
            ->where('target_user_id', $targetUserId)
            ->where('action_type', $actionType)
            ->where('status', UserActionRequest::STATUS_PENDING)
            ->exists();

        if ($existingPending) {
            return;
        }

        UserActionRequest::query()->create([
            'action_type' => $actionType,
            'target_user_id' => $targetUserId,
            'requested_by_user_id' => auth()->id(),
            'status' => UserActionRequest::STATUS_PENDING,
        ]);
    }

    public function render()
    {
        abort_unless(auth()->check() && auth()->user()->can('access-admin-panel'), 403);

        $columns = ['id', 'name', 'email', 'is_admin', 'created_at'];
        if (Schema::hasColumn('users', 'access_level')) {
            $columns[] = 'access_level';
        }

        $actorCanCreateAdmin = auth()->user()?->isManager() ?? false;
        $isManager = auth()->user()?->isManager() ?? false;
        $pendingRequests = collect();
        $pendingActionMap = [];
        $pendingUserNames = [];
        if (Schema::hasTable('user_action_requests')) {
            $allPendingRequests = UserActionRequest::query()
                ->where('status', UserActionRequest::STATUS_PENDING)
                ->latest()
                ->get();

            foreach ($allPendingRequests as $pendingRequest) {
                $pendingActionMap[$pendingRequest->target_user_id][$pendingRequest->action_type] = true;
            }

            if ($isManager) {
                $pendingRequests = $allPendingRequests->take(20);
            }

            $pendingUserIds = $allPendingRequests
                ->pluck('target_user_id')
                ->merge($allPendingRequests->pluck('requested_by_user_id'))
                ->unique()
                ->values();

            $pendingUserNames = User::query()
                ->whereIn('id', $pendingUserIds)
                ->pluck('name', 'id')
                ->all();
        }

        return view('livewire.admin.user-management', [
            'users' => User::query()->latest()->take(50)->get($columns),
            'actorCanCreateAdmin' => $actorCanCreateAdmin,
            'pendingRequests' => $pendingRequests,
            'pendingActionMap' => $pendingActionMap,
            'pendingUserNames' => $pendingUserNames,
            'isManager' => $isManager,
        ]);
    }
}
