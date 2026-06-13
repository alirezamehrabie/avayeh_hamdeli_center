<div
    x-data="notificationModal()"
    x-on:open-notification-modal.window="open($event.detail.config || {})"
    x-on:close-notification-modal.window="close()"
    x-on:keydown.escape.window="if (openState && modal.closable) close()"
    x-cloak
>
    <div
        x-show="openState"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 px-4 py-6 backdrop-blur-sm"
        style="display: none;"
    >
        <div class="absolute inset-0" @click="if (modal.closable) close()"></div>

        <div
            x-show="openState"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md rounded-[28px] bg-white p-6 shadow-2xl"
            @click.stop
        >
            <button
                x-show="modal.closable"
                type="button"
                @click="close()"
                class="absolute left-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200"
                aria-label="بستن"
            >
                <span class="text-xl leading-none">&times;</span>
            </button>

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full" :class="typeClasses(modal.type).iconWrapper">
                <template x-if="resolvedIcon() === 'success'">
                    <svg class="h-7 w-7" :class="typeClasses(modal.type).icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <template x-if="resolvedIcon() === 'warning'">
                    <svg class="h-7 w-7" :class="typeClasses(modal.type).icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86l-7.5 13A1 1 0 003.66 18h16.68a1 1 0 00.87-1.5l-7.5-13a1 1 0 00-1.74 0z" />
                    </svg>
                </template>
                <template x-if="resolvedIcon() === 'error'">
                    <svg class="h-7 w-7" :class="typeClasses(modal.type).icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>
                <template x-if="resolvedIcon() === 'info'">
                    <svg class="h-7 w-7" :class="typeClasses(modal.type).icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </div>

            <div class="mt-4 text-center">
                <h2 class="text-lg font-extrabold text-slate-800" x-text="modal.title"></h2>
                <p class="mt-2 whitespace-pre-line text-sm font-medium text-slate-600" x-text="modal.message"></p>
            </div>

            <template x-if="modal.template === 'service-delivery-success'">
                <div class="mt-5 rounded-2xl border p-4 text-right" :class="typeClasses(modal.type).panel">
                    <p class="text-sm font-bold text-slate-800" x-text="modal.meta.service_name || 'خدمت انتخاب‌شده'"></p>
                    <template x-if="modal.meta.code">
                        <p class="mt-1 text-xs font-semibold text-slate-500">
                            کد خدمت: <span x-text="modal.meta.code"></span>
                        </p>
                    </template>
                    <div class="mt-4 rounded-2xl bg-white px-4 py-3 text-center shadow-sm">
                        <p class="text-xs font-semibold text-slate-500">سهمیه باقی‌مانده این خدمت</p>
                        <p class="mt-1 text-2xl font-extrabold" :class="typeClasses(modal.type).accent" dir="ltr" x-text="modal.meta.remaining_quota || '0'"></p>
                    </div>
                </div>
            </template>

            <div class="mt-6 flex flex-wrap gap-3" :class="modal.buttons.length > 1 ? 'justify-between' : 'justify-center'">
                <template x-for="(button, index) in modal.buttons" :key="`${button.label}-${index}`">
                    <div class="flex min-w-32 flex-1">
                        <template x-if="button.href">
                            <a
                                :href="button.href"
                                :target="button.new_tab ? '_blank' : null"
                                :rel="button.new_tab ? 'noopener noreferrer' : null"
                                class="inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-bold transition active:scale-[0.98]"
                                :class="buttonClasses(button.variant)"
                                x-text="button.label"
                            ></a>
                        </template>
                        <template x-if="!button.href">
                            <button
                                type="button"
                                @click="handleButton(button)"
                                class="inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-bold transition active:scale-[0.98]"
                                :class="buttonClasses(button.variant)"
                                x-text="button.label"
                            ></button>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function notificationModal() {
                return {
                    openState: false,
                    modal: {
                        type: 'info',
                        template: 'default',
                        title: '',
                        message: '',
                        icon: 'info',
                        closable: true,
                        buttons: [],
                        meta: {},
                    },
                    open(config) {
                        this.modal = {
                            ...this.modal,
                            ...config,
                            buttons: Array.isArray(config.buttons) ? config.buttons : [],
                            meta: config.meta && typeof config.meta === 'object' ? config.meta : {},
                        };
                        this.openState = true;
                        document.body.classList.add('overflow-hidden');
                    },
                    close() {
                        this.openState = false;
                        document.body.classList.remove('overflow-hidden');
                    },
                    resolvedIcon() {
                        return this.modal.icon === 'none' ? null : (this.modal.icon || this.modal.type);
                    },
                    handleButton(button) {
                        if (button.action === 'event' && button.event) {
                            if (window.Livewire?.dispatch) {
                                window.Livewire.dispatch(button.event, button.payload || {});
                            } else {
                                window.dispatchEvent(new CustomEvent(button.event, { detail: button.payload || {} }));
                            }
                        }

                        if (button.action === 'close' || !button.action || button.action === 'event') {
                            this.close();
                        }
                    },
                    typeClasses(type) {
                        return {
                            success: {
                                iconWrapper: 'bg-emerald-100',
                                icon: 'text-emerald-600',
                                panel: 'border-emerald-100 bg-emerald-50',
                                accent: 'text-emerald-600',
                            },
                            warning: {
                                iconWrapper: 'bg-amber-100',
                                icon: 'text-amber-600',
                                panel: 'border-amber-100 bg-amber-50',
                                accent: 'text-amber-600',
                            },
                            error: {
                                iconWrapper: 'bg-rose-100',
                                icon: 'text-rose-600',
                                panel: 'border-rose-100 bg-rose-50',
                                accent: 'text-rose-600',
                            },
                            info: {
                                iconWrapper: 'bg-sky-100',
                                icon: 'text-sky-600',
                                panel: 'border-sky-100 bg-sky-50',
                                accent: 'text-sky-600',
                            },
                        }[type] || this.typeClasses('info');
                    },
                    buttonClasses(variant) {
                        return {
                            primary: 'bg-indigo-600 text-white hover:bg-indigo-500',
                            secondary: 'bg-slate-100 text-slate-700 hover:bg-slate-200',
                            success: 'bg-emerald-600 text-white hover:bg-emerald-500',
                            warning: 'bg-amber-500 text-white hover:bg-amber-400',
                            danger: 'bg-rose-600 text-white hover:bg-rose-500',
                            info: 'bg-sky-600 text-white hover:bg-sky-500',
                        }[variant] || 'bg-indigo-600 text-white hover:bg-indigo-500';
                    },
                };
            }
        </script>
    @endpush
@endonce
