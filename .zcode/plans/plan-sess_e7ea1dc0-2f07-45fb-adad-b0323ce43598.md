# پلن پیاده‌سازی «پیام‌ها» (ارتباط اعضا و حامی‌ها با مدیریت)

گفتگوی دوطرفه با پیوست عکس، مطابق الگوهای موجود پروژه (الگوی کامل: مرکز اعلان‌ها).

## ۱) دیتابیس — مایگریشن جدید `database/migrations/2026_09_25_000001_create_conversation_tables.php`

سه جدول (با گارد `Schema::hasTable` مثل مایگریشن اعلان‌ها):

**`conversations`** — گفتگوها:
- `subject` string(190)، `status` enum('pending','answered','closed') default 'pending' (در انتظار بررسی / پاسخ داده شد / بسته شده)
- `sender_type`/`sender_id` (morph — 'person' برای عضو، 'user' برای حامی) + `sender_name` (اسنپ‌شات)، `sender_role` ('member'|'child_supporter')، `sender_code` nullable (کد عضویت یا کد حامی CS-####)
- `last_message_at` nullable timestamp؛ indexes: `[status]`, `[last_message_at]`, `[sender_type,sender_id]`

**`messages`** — پیام‌های هر گفتگو:
- `conversation_id` FK cascade، `sender_type`/`sender_id` morph، `sender_name` nullable
- `is_from_staff` boolean (پیام مدیریت)، `body` text
- `staff_read_at` nullable (خوانده‌شدن پیام کاربر توسط مدیریت → بج صندوق)، `member_read_at` nullable (خوانده‌شدن پاسخ مدیر در سمت کاربر)
- indexes: `[conversation_id, created_at]`, `[is_from_staff, staff_read_at]`

**`message_attachments`** — پیوست‌ها:
- `message_id` FK cascade، `original_name`، `path`، `mime` string(50)، `size` unsignedInteger

## ۲) مدل‌ها و زیرساخت

- `app/Models/Conversation.php`: روابط `sender()` morphTo، `messages()` hasMany؛ ثابت `STATUS_*` + `statusLabels()` فارسی؛ اسکوپ `forSender(string $type, int $id)`.
- `app/Models/Message.php`: `sender()` morphTo، `conversation()` belongsTo، `attachments()` hasMany؛ متدهای `markStaffRead()`/`markMemberRead()`.
- `app/Models/MessageAttachment.php`: belongsTo message؛ متد `isAccessibleBy(Person|User $user, bool $isStaff)`.
- `app/Providers/AppServiceProvider.php`:
  - افزودن `'message' => Message::class` به `Relation::enforceMorphMap` ('person' و 'user' از قبل هستند) — برای استفاده به‌عنوان subject اعلان.
  - گیت جدید (هم‌تراز با `manage-notifications`، خط ۱۱۴): `Gate::define('manage-messages', fn (User $user) => $user->isManager());`

## ۳) پنل مدیریت (داشبورد SPA)

پوشه‌های خالی `app/Livewire/Admin/Messages/` و `resources/views/livewire/admin/messages/` از قبل آماده‌اند:

- **`app/Livewire/Admin/DashboardHome.php`**: در `normalizeActiveSection()` مثل بلاک manage-notifications (خط ۳۱۴): `$user?->can('manage-messages')` → `$validSections[] = 'messages-inbox';` + انشعاب deep-link `admin.messages` در mount.
- **`resources/views/livewire/admin/dashboard-home.blade.php`**: داخل `@switch($activeSection)` بعد از کیس‌های اعلان‌ها: `@case('messages-inbox')` + `<livewire:admin.messages.message-inbox :key="'messages-inbox'" />`.
- **`app/Livewire/Admin/Messages/MessageInbox.php`** (قالب: `NotificationCenter.php`): `WithPagination`؛ propهای `$statusFilter/$search/$dateFrom/$dateTo/$perPage=15/$selectedThreadId/$replyBody`؛ `abort_unless(...->can('manage-messages'), 403)`؛ فیلترها با `jalaliToCarbon` (کپی همان متد). اکشن‌ها:
  - `selectThread(id)`: انتخاب گفتگو + بک‌آپدیت `staff_read_at` پیام‌های خوانده‌نشده + dispatch `messages-updated`.
  - `sendReply()`: validate body (required، max:5000، `PersianText::normalizeText`)؛ اگر بسته بود خطا؛ ایجاد پیام staff (sender = کاربر جاری)، `last_message_at=now()`، `status='answered'`؛ توست موفقیت با `InteractsWithNotificationModal`.
  - `closeThread(id)` / `reopenThread(id)` / `deleteThread(id)` (حذف آبشاری با FK).
- **`resources/views/livewire/admin/messages/message-inbox.blade.php`** (قالب: notification-center.blade.php): هدر با شمارندهٔ خوانده‌نشده، فیلترها (وضعیت/جست‌وجوی نام و کد/تاریخ جلالی)، لیست دوستونه (lg: سه‌ستونه: لیست + نخ گفتگو)؛ ردیف: نام + کد فرستنده + چیپ نقش (عضو/حامی) + موضوع + بج وضعیت (amber/emerald/slate) + تعداد خوانده‌نشده + تاریخ جلالی؛ نخ: حباب‌های پیام (مدیریت indigo، کاربر خاکستری، `nl2br(e($body))`)، بند انگشتی عکس‌های پیوست با لینک دانلود، کادر پاسخ + دکمه بستن گفتگو با confirm.
- **`app/Livewire/Admin/Messages/MessageBadge.php`** + ویو: بج رز کوچک با `wire:poll.60s` (الگوی NotificationBell) که تعداد پیام‌های `is_from_staff=false && staff_read_at=null` را می‌شمارد — داخل دکمهٔ گروه سایدبار.
- **`resources/views/layouts/partials/sidebar.blade.php`**: `$messagesOpen = $dashboardMode ? $isActive(['messages-inbox']) : false;` (خط ۶۳)، انشعاب `$defaultOpenMenu`، گروه `'messages' => [['section' => 'messages-inbox', 'label' => 'صندوق پیام‌ها']]` در `$dashboardMenuItems`، و بلوک رندر گروه «پیام‌ها» با آیکون پاکت‌نامه `@can('manage-messages')` **درست بعد از گروه اعلان‌ها** (خط ۵۱۲) شامل `<livewire:admin.messages.message-badge />` در هدر گروه.
- **`routes/web.php`**: روت deep-link `Route::get('/admin/messages', DashboardHome::class)->middleware(['auth','can:manage-messages'])->name('admin.messages');`

## ۴) پنل اعضا (مدل Person، گارد member)

- **`app/Livewire/Members/ContactManagement.php`**: `#[Layout('layouts.auth')]`؛ mount: اگر `Auth::guard('member')->check()` نبود redirect به member.login. `WithFileUploads`. propها: `$newSubject/$newBody/$newPhotos/$replyBody/$selectedThreadId/$showNewForm`.
  - `createThread()`: validate موضوع (required max:190)، متن (required max:5000)، عکس‌ها (array max:3، هرکدام image، mimes jpg/jpeg/png/webp، max:2048)؛ محدودیت نرخ ۳ گفتگو در ۲۴ ساعت (RateLimiter — الگوی MemberLogin)؛ ایجاد Conversation (اسنپ‌شات: full_name، sender_role='member'، sender_code=person_code) + Message + ذخیره پیوست‌ها؛ پیام موفقیت درون‌خطی (لایهٔ auth فلش/مودال ندارد).
  - `openThread(id)`: بررسی مالکیت (sender morph) + علامت‌گذاری `member_read_at` پیام‌های staff.
  - `sendReply()`: مالکیت + گفتگوی باز + محدودیت ۱۰ پیام در ساعت؛ ارسال پاسخ.
- **`resources/views/livewire/members/contact-management.blade.php`**: هم‌سبک پنل اعضا (max-w-3xl، گرادیان #5964AE، آیکون‌های `bi bi-*`)، خطاهای ولیدیشن درون‌خطی، نخ گفتگو با حباب‌ها.
- **`resources/views/livewire/members/dashboard.blade.php`**: کارت/دکمهٔ «ارتباط با مدیریت» با لینک به روت جدید (جای بخشی از کادر «در حال توسعه»).

## ۵) پنل حامی کودک

- **`app/Livewire/ChildSupporters/ContactManagement.php`**: `#[Layout('layouts.child-supporter')]`؛ mount با `can('access-child-supporter-panel')` (الگوی Dashboard موجود)؛ همان منطق با اسنپ‌شات حامی: full_name، sender_role='child_supporter'، `sender_code=SponsorProfile?->supporter_code`؛ بازخورد با `session()->flash('success')` (x-flash-alerts در این لایه فعال است).
- **`resources/views/livewire/child-supporters/contact-management.blade.php`**: هم‌سبک پنل حامی (Tailwind، کارت سفید).
- **`resources/views/layouts/partials/child-supporter-sidebar.blade.php`**: آیتم سطح‌اول «ارتباط با مدیریت» بین پیشخوان و تنظیمات سیستم با آیکون چت + `$isContactActive = request()->routeIs('child-supporter.messages')`.

## ۶) دانلود امن پیوست‌ها

- **`app/Http/Controllers/Messages/DownloadMessageAttachment.php`** (invokable): فایل از disk `local` (خارج از public) با `Storage::download`؛ دسترسی فقط: staff با `manage-messages`، یا مالک گفتگو (تطبیق sender morph با `Auth::guard('member')->user()` و `auth()->user()`)؛ در غیر این‌صورت 403/404.
- روت: `Route::get('/messages/attachments/{attachment}', ...)->middleware(['auth'])->name('messages.attachments.download');` — بررسی مالکیت داخل کنترلر است چون دو گارد دارد.
- آپلود: `Storage::disk('local')->putFile('message-attachments/'.$conversation->id, $photo)` (هش‌نیم، نه الگوی public_path پروفایل‌ها — حریم خصوصی).

## ۷) اتصال به سیستم اعلان‌ها

- **`app/Support/Notifications/NotificationEventRegistry.php`**: رویداد `EVENT_MESSAGE_RECEIVED = 'message.received'` با برچسب «پیام جدید از اعضا و حامی‌ها»، گروه «پیام‌ها»، `supports_targeting => true`، `targetable_roles => [ACCESS_LEVEL_CHILD_SUPPORTER]`.
- هنگام هر پیام کاربر (`is_from_staff=false`)، فراخوانی `ManagerNotificationDispatcher::dispatch(...)` با `subject: $message` (morph map دارد)، عنوان «پیام جدید: {موضوع}» و پیش‌نمایش متن + نام/کد فرستنده در context. (dispatcher خودش dedupe و ترجیحات مدیر را اعمال می‌کند؛ بج سایدبار مستقل و همیشه فعال است.)

## ۸) امنیت و محدودیت‌ها

- بازبینی مجوز در mount و هر اکشن (الگوی UserAccount)؛ owner-check در همهٔ اکشن‌های کاربر.
- نرمال‌سازی متن با `PersianText::normalizeText`؛ خروجی body با `{{ }}` و `nl2br(e())` (ضد XSS).
- محدودیت نرخ: ۳ گفتگو/۲۴ ساعت و ۱۰ پیام/ساعت به‌ازای هر فرستنده با پیام فارسی.
- بدنهٔ پاسخ مدیریت حداکثر ۵۰۰۰ نویسه؛ پیوست فقط تصویر با whitelist mime و max 2MB و حداکثر ۳ فایل.

## ۹) تست‌ها (`tests/Feature/MessagesTest.php` + `tests/Feature/AdminMessageInboxTest.php`)

- حامی پیام می‌فرستد → assertDatabaseHas conversations/messages + با ساخت `ManagerNotificationPreference` فعال، ثبت `manager_notifications` با `message.received`.
- عضو (Person با `Auth::guard('member')->login`) پیام می‌فرستد؛ عضو دیگر به گفتگو دسترسی ندارد.
- مدیر: `Livewire::test(MessageInbox::class)` — مشاهدهٔ لیست، `selectThread` → ست‌شدن `staff_read_at`، `sendReply` → status answered + دیده‌شدن پاسخ برای فرستنده، `closeThread`/`deleteThread`، کاربر غیرمجاز `assertForbidden` و fallback به overview.
- دانلود پیوست: مالک/مدیر 200، کاربر غریبه 403.
- محدودیت نرخ گفتگوی چهارم → خطا.

## ۱۰) ترتیب اجرا

۱. مایگریشن + مدل‌ها + morph map + گیت → ۲. رجیستری رویداد → ۳. صندوق مدیریت (کامپوننت + ویو + سکشن + سایدبار + بج + روت) → ۴. پنل اعضا → ۵. پنل حامی → ۶. دانلود پیوست → ۷. تست‌ها.

## اعتبارسنجی نهایی

- `php artisan migrate` بدون خطا؛ `php artisan test` سبز؛ `./vendor/bin/pint` فرمت نهایی.
- چک‌لیست دستی: بج سایدبار با poll به‌روز می‌شود؛ فلوی کامل ارسال/پاسخ/بستن از هر دو پنل؛ بازگشت‌های 403 امن.