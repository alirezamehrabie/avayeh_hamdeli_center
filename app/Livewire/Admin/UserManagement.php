<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\UserActionRequest;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class UserManagement extends Component
{
    use WithPagination;

    public string $first_name = '';
    public string $last_name = '';
    public string $username = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $access_level = User::ACCESS_LEVEL_REGULAR;
    public array $permissions = [];
    public ?int $editing_user_id = null;
    public string $edit_first_name = '';
    public string $edit_last_name = '';
    public string $edit_username = '';
    public string $edit_password = '';
    public string $edit_password_confirmation = '';
    public string $edit_access_level = User::ACCESS_LEVEL_REGULAR;
    public bool $showEditModal = false;
    public string $search = '';
    public string $roleFilter = 'all';
    public string $statusFilter = 'all';
    public string $permissionFilter = 'all';
    public bool $viewingDeletedUsersState = false;
    public bool $listOnly = false;

    public function mount(bool $showDeletedUsers = false, bool $listOnly = false): void
    {
        $this->listOnly = $listOnly;
        $this->viewingDeletedUsersState = $showDeletedUsers
            || request()->routeIs('admin.user-management.deleted')
            || request()->routeIs('admin.user-list.deleted');
    }

    private function viewingDeletedUsers(): bool
    {
        return $this->viewingDeletedUsersState;
    }

    public function createUser(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        abort_unless(Schema::hasColumn('users', 'access_level'), 500, 'users.access_level column is required.');
        abort_unless(Schema::hasColumns('users', ['first_name', 'last_name']), 500, 'users.first_name and users.last_name columns are required.');
        abort_unless(Schema::hasColumn('users', 'permissions'), 500, 'users.permissions column is required.');
        $currentUser = auth()->user();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:users,name'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'access_level' => ['required', 'in:'.implode(',', [
                User::ACCESS_LEVEL_MANAGER,
                User::ACCESS_LEVEL_ADMIN,
                User::ACCESS_LEVEL_REGULAR,
                User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            ])],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_keys(User::permissionOptions()))],
        ], [
            'first_name.required' => 'نام الزامی است.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'username.required' => 'نام کاربری الزامی است.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'access_level.required' => 'انتخاب سطح دسترسی الزامی است.',
            'permissions.*.in' => 'سطح دسترسی انتخاب‌شده معتبر نیست.',
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

        User::createRoleAccount([
            'name' => $username,
            'first_name' => trim($validated['first_name']),
            'last_name' => trim($validated['last_name']),
            'email' => $username . '@local.system',
            'password' => $validated['password'],
            'access_level' => $validated['access_level'],
        ], $validated['permissions'] ?? []);

        $this->reset(['first_name', 'last_name', 'username', 'password', 'password_confirmation', 'access_level', 'permissions']);
        $this->access_level = User::ACCESS_LEVEL_REGULAR;
        session()->flash('success', 'کاربر جدید با موفقیت ایجاد شد.');
    }

    public function startEditingUser(int $userId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $user = User::query()->find($userId);
        if (! $user) {
            return;
        }

        if ($user->isProtectedManagerAccount()) {
            session()->flash('success', 'حساب Manager اصلی سیستم قابل ویرایش نیست.');
            return;
        }

        $this->resetValidation([
            'edit_first_name',
            'edit_last_name',
            'edit_username',
            'edit_password',
            'edit_password_confirmation',
            'edit_access_level',
        ]);

        $this->editing_user_id = $user->id;
        $this->edit_first_name = (string) ($user->first_name ?? '');
        $this->edit_last_name = (string) ($user->last_name ?? '');
        $this->edit_username = (string) $user->name;
        $this->edit_password = '';
        $this->edit_password_confirmation = '';
        $this->edit_access_level = (string) ($user->access_level ?? User::ACCESS_LEVEL_REGULAR);
        $this->showEditModal = true;
    }

    public function cancelEditingUser(): void
    {
        $this->editing_user_id = null;
        $this->edit_first_name = '';
        $this->edit_last_name = '';
        $this->edit_username = '';
        $this->edit_password = '';
        $this->edit_password_confirmation = '';
        $this->edit_access_level = User::ACCESS_LEVEL_REGULAR;
        $this->showEditModal = false;
        $this->resetValidation([
            'edit_first_name',
            'edit_last_name',
            'edit_username',
            'edit_password',
            'edit_password_confirmation',
            'edit_access_level',
        ]);
    }

    public function openEditModal(int $userId): void
    {
        $this->startEditingUser($userId);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPermissionFilter(): void
    {
        $this->resetPage();
    }

    public function clearUserFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter', 'permissionFilter']);
        $this->resetPage();
    }

    public function updateUser(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        if (! $this->editing_user_id) {
            return;
        }

        $user = User::query()->find($this->editing_user_id);
        if (! $user) {
            $this->cancelEditingUser();
            return;
        }

        if ($user->isProtectedManagerAccount()) {
            session()->flash('success', 'حساب Manager اصلی سیستم قابل ویرایش نیست.');
            $this->cancelEditingUser();
            return;
        }

        $validated = $this->validate([
            'edit_first_name' => ['required', 'string', 'max:100'],
            'edit_last_name' => ['required', 'string', 'max:100'],
            'edit_username' => ['required', 'string', 'max:100', 'unique:users,name,' . $user->id],
            'edit_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'edit_access_level' => ['required', 'in:' . implode(',', [
                User::ACCESS_LEVEL_MANAGER,
                User::ACCESS_LEVEL_ADMIN,
                User::ACCESS_LEVEL_REGULAR,
                User::ACCESS_LEVEL_SOCIAL_WORKER,
                User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
            ])],
        ], [
            'edit_first_name.required' => 'نام الزامی است.',
            'edit_last_name.required' => 'نام خانوادگی الزامی است.',
            'edit_username.required' => 'نام کاربری الزامی است.',
            'edit_username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'edit_password.min' => 'رمز عبور باید حداقل 8 کاراکتر باشد.',
            'edit_password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
            'edit_access_level.required' => 'انتخاب سطح دسترسی الزامی است.',
        ]);

        $username = mb_strtolower(trim($validated['edit_username']));

        if ($validated['edit_access_level'] === User::ACCESS_LEVEL_MANAGER) {
            session()->flash('success', 'تنها حساب Manager اصلی سیستم معتبر است و قابل انتساب به کاربر دیگر نیست.');
            return;
        }

        if ($validated['edit_access_level'] === User::ACCESS_LEVEL_ADMIN && ! auth()->user()->isManager()) {
            $this->addError('edit_access_level', 'You are not allowed to assign Admin access.');
            return;
        }

        $payload = [
            'first_name' => trim($validated['edit_first_name']),
            'last_name' => trim($validated['edit_last_name']),
            'name' => $username,
            'email' => $username . '@local.system',
            'access_level' => $validated['edit_access_level'],
            'is_admin' => $validated['edit_access_level'] === User::ACCESS_LEVEL_ADMIN,
        ];

        if (filled($validated['edit_password'] ?? null)) {
            $payload['password'] = $validated['edit_password'];
        }

        $user->update($payload);

        $this->cancelEditingUser();
        session()->flash('success', 'اطلاعات کاربر با موفقیت ویرایش شد.');
    }

    public function toggleUserPermission(int $userId, string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        abort_unless(Schema::hasColumn('users', 'permissions'), 500, 'users.permissions column is required.');
        abort_unless(array_key_exists($permission, User::permissionOptions()), 422);

        if (auth()->id() === $userId) {
            session()->flash('success', 'امکان تغییر دسترسی‌های حساب کاربری فعلی وجود ندارد.');
            return;
        }

        $user = User::query()->find($userId);
        if (! $user) {
            return;
        }

        if ($user->isProtectedManagerAccount()) {
            session()->flash('success', 'حساب Manager اصلی سیستم قابل تغییر نیست.');
            return;
        }

        $permissions = $user->getPermissionKeys();

        if ($permission === User::PERMISSION_FULL_ACCESS) {
            $permissions = in_array(User::PERMISSION_FULL_ACCESS, $permissions, true)
                ? []
                : [User::PERMISSION_FULL_ACCESS];
        } else {
            $permissions = array_values(array_diff($permissions, [User::PERMISSION_FULL_ACCESS]));

            if (in_array($permission, $permissions, true)) {
                $permissions = array_values(array_diff($permissions, [$permission]));
            } else {
                $permissions[] = $permission;
            }
        }

        $user->update([
            'permissions' => User::normalizePermissionKeys($permissions),
        ]);

        session()->flash('success', 'دسترسی‌های کاربر به‌روزرسانی شد.');
    }

    public function deleteUser(int $userId): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

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
            session()->flash('success', 'درخواست حذف ادمین ثبت شد و منتظر تایید مدیر است.');
            return;
        }

        $user->delete();
        $this->resetPage();
        session()->flash('success', 'کاربر با موفقیت حذف شد.');
    }

    public function setAccessLevel(int $userId, string $accessLevel): void
    {
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
        abort_unless(Schema::hasColumn('users', 'access_level'), 500, 'users.access_level column is required.');

        abort_unless(in_array($accessLevel, [
            User::ACCESS_LEVEL_MANAGER,
            User::ACCESS_LEVEL_ADMIN,
            User::ACCESS_LEVEL_REGULAR,
            User::ACCESS_LEVEL_SOCIAL_WORKER,
            User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR,
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
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
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
            $this->resetPage();
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
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);
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
        abort_unless(auth()->check() && auth()->user()->can('full-access'), 403);

        $actorCanCreateAdmin = auth()->user()?->isManager() ?? false;
        $isManager = auth()->user()?->isManager() ?? false;
        $viewingDeletedUsers = $this->viewingDeletedUsers();
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

            $pendingUserNameColumns = ['id', 'name'];
            if (Schema::hasColumns('users', ['first_name', 'last_name'])) {
                $pendingUserNameColumns[] = 'first_name';
                $pendingUserNameColumns[] = 'last_name';
            }

            $pendingUserNames = User::withTrashed()
                ->whereIn('id', $pendingUserIds)
                ->get($pendingUserNameColumns)
                ->mapWithKeys(fn (User $user) => [
                    $user->id => $user->full_name !== '' ? $user->full_name . ' (' . $user->name . ')' : $user->name,
                ])
                ->all();
        }

        $usersQuery = $viewingDeletedUsers
            ? User::onlyTrashed()->orderByDesc('deleted_at')
            : User::query()->latest();

        if (filled($this->search)) {
            $search = trim($this->search);

            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');

                if (Schema::hasColumns('users', ['first_name', 'last_name'])) {
                    $query->orWhere('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%');
                }

                if (Schema::hasColumn('users', 'mobile')) {
                    $query->orWhere('mobile', 'like', '%' . $search . '%');
                }
            });
        }

        if ($this->roleFilter !== 'all' && Schema::hasColumn('users', 'access_level')) {
            $usersQuery->where('access_level', $this->roleFilter);
        }

        if ($this->statusFilter === 'admin') {
            $usersQuery->where('is_admin', true);
        } elseif ($this->statusFilter === 'regular') {
            $usersQuery->where('is_admin', false);
        } elseif ($this->statusFilter === 'protected') {
            $usersQuery->where(function ($query) {
                $query->where('name', User::PRIMARY_ADMIN_USERNAME)
                    ->orWhere('email', User::PRIMARY_ADMIN_EMAIL);
            });
        }

        if ($this->permissionFilter !== 'all' && Schema::hasColumn('users', 'permissions')) {
            $permission = $this->permissionFilter;

            $usersQuery->where(function ($query) use ($permission) {
                $query->whereJsonContains('permissions', $permission)
                    ->orWhere('permissions', 'like', '%"'.$permission.'"%');
            });
        }

        return view('livewire.admin.user-management', [
            'users' => $usersQuery->paginate(12),
            'viewingDeletedUsers' => $viewingDeletedUsers,
            'actorCanCreateAdmin' => $actorCanCreateAdmin,
            'permissionOptions' => User::permissionOptions(),
            'pendingRequests' => $pendingRequests,
            'hasPendingRequests' => $pendingRequests->isNotEmpty(),
            'pendingActionMap' => $pendingActionMap,
            'pendingUserNames' => $pendingUserNames,
            'isManager' => $isManager,
            'userStats' => [
                'total' => User::count(),
                'deleted' => User::onlyTrashed()->count(),
                'admins' => User::query()->where('is_admin', true)->count(),
                'distributionOperators' => User::query()
                    ->where('access_level', User::ACCESS_LEVEL_DISTRIBUTION_OPERATOR)
                    ->count(),
                'socialWorkers' => User::query()
                    ->where('access_level', User::ACCESS_LEVEL_SOCIAL_WORKER)
                    ->count(),
                'childSupporters' => User::query()
                    ->where('access_level', User::ACCESS_LEVEL_CHILD_SUPPORTER)
                    ->count(),
                'regular' => User::query()
                    ->where('access_level', User::ACCESS_LEVEL_REGULAR)
                    ->count(),
            ],
        ]);
    }
}
