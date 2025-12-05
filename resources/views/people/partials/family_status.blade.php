{{-- Family Status Form Section --}}

<div class="row family-status-section neumorphic-card">
    <h4 class="section-title border-bottom pb-2 mb-3 font-bold">وضعیت خانوادگی مددجو</h4>

    <!-- فیلد نسبت با سرپرست / نقش در خانواده -->
    <div class="col-md-4 mb-3">
        <label for="guardian_relation_type_id" class="form-label">نسبت با سرپرست</label>
        <select class="form-select @error('guardian_relation_type_id') is-invalid @enderror"
                id="guardian_relation_type_id"
                name="guardian_relation_type_id">

            <option value="" disabled selected>انتخاب کنید...</option>

            @foreach($guardianRelationTypes as $type)
                <option value="{{ $type->id }}"
                    {{ old('guardian_relation_type_id') == $type->id ? 'selected' : '' }}>
                    {{ $type->title }}
                </option>
            @endforeach

        </select>

        @error('guardian_relation_type_id')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    </div>



    {{-- economic_decile --}}
    <div class="form-group col-md-4">
        <label for="economic_decile">دهک معیشتی خانوار <span class="text-danger">*</span></label>
        <select
            name="economic_decile"
            id="economic_decile"
            class="form-control"
        >
            <option value="">— انتخاب کنید —</option>
            @foreach($deciles as $value => $label)
                <option
                    value="{{ $value }}"
                    {{ old('economic_decile') == $value ? 'selected' : '' }}
                >
                    {{ "{$label} | {$value}" }}
                </option>
            @endforeach
        </select>
        @error('economic_decile')
        <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>


    {{-- living_parents --}}
    <div class="form-group col-md-4 neumorphic-input">
        <label for="living_parents">والدین در قید حیات:</label>
        <select name="living_parents" id="living_parents" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدر">پدر</option>
            <option value="مادر">مادر</option>
            <option value="هر دو (پدر و مادر)">هر دو (پدر و مادر)</option>
        </select>
    </div>

    {{-- deceased_parent --}}
    <div class="form-group col-md-4 neumorphic-input">
        <label for="deceased_parent">کدام والد فوت شده است؟</label>
        <select name="deceased_parent" id="deceased_parent" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدر">پدر</option>
            <option value="مادر">مادر</option>
            <option value="هر دو (پدر و مادر)">هر دو</option>
        </select>
    </div>

    {{-- death_year --}}
    <div class="form-group col-md-4 neumorphic-input">
        <label for="death_year">سال فوت:</label>
        <input type="number" name="death_year" id="death_year" class="form-control" placeholder="مثلاً ۱۳۹۵">
    </div>

    {{-- death_reason --}}
    <div class="form-group col-md-4 neumorphic-input">
        <label for="death_reason">علت فوت:</label>
        <input type="text" name="death_reason" id="death_reason" class="form-control"
               placeholder="مثلاً بیماری یا حادثه">
    </div>

    {{-- divorced_parent --}}
    <div class="form-group col-md-4 neumorphic-input">
        <label for="divorced_parent">کدام والد جدا شده است؟</label>
        <select name="divorced_parent" id="divorced_parent" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدر">پدر</option>
            <option value="مادر">مادر</option>
            <option value="هر دو (پدر و مادر)">هر دو</option>
        </select>
    </div>

    {{-- remarried_parent --}}
    <div class="form-group col-md-4 neumorphic-input">
        <label for="remarried_parent">کدام والد ازدواج مجدد کرده است؟</label>
        <select name="remarried_parent" id="remarried_parent" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدر">پدر</option>
            <option value="مادر">مادر</option>
            <option value="هر دو (پدر و مادر)">هر دو</option>
        </select>
    </div>

    {{-- children_from_previous_marriage --}}
    <div class="form-group col-md-4 neumorphic-input">
        <label for="children_from_previous_marriage">تعداد فرزندان از ازدواج قبلی سرپرست:</label>
        <input type="number" name="children_from_previous_marriage" id="children_from_previous_marriage"
               class="form-control" min="0">
    </div>


    {{-- has_parent_disability - چک‌باکس معلولیت والدین --}}
    <div class="form-check form-check mb-3 mt-3">
        <input class="form-check-input"
               type="checkbox"
               name="has_parent_disability"
               id="has_parent_disability"
               value="1"
            {{ old('has_parent_disability') ? 'checked' : '' }}>
        <label class="form-check-label" for="has_parent_disability">
            آیا والدین دارای معلولیت هستند؟
        </label>
    </div>

    {{-- parent_disability_description - توضیحات معلولیت --}}
    <div class="form-group neumorphic-input"
         id="disability_description_box"
         style="display: {{ old('has_parent_disability') ? 'block' : 'none' }};">
        <label for="parent_disability_description">توضیحات نوع معلولیت والد:</label>
        <textarea name="parent_disability_description"
                  id="parent_disability_description"
                  class="form-control"
                  rows="3"
                  placeholder="شرح مختصر نوع و میزان معلولیت">{{ old('parent_disability_description') }}</textarea>
    </div>
</div>

{{-- ✅ کد JavaScript برای نمایش/مخفی کردن باکس توضیحات معلولیت --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // المان‌های مورد نیاز
            const checkbox = document.getElementById('has_parent_disability');
            const descriptionBox = document.getElementById('disability_description_box');
            const descriptionTextarea = document.getElementById('parent_disability_description');

            // تابع نمایش/مخفی کردن
            function toggleDisabilityDescription() {
                if (checkbox.checked) {
                    // نمایش با انیمیشن
                    descriptionBox.style.display = 'block';
                    descriptionBox.style.opacity = '0';
                    descriptionBox.style.transition = 'opacity 0.3s ease-in-out';

                    // کمی تاخیر برای اعمال انیمیشن
                    setTimeout(function() {
                        descriptionBox.style.opacity = '1';
                    }, 10);

                    // فوکوس روی textarea
                    setTimeout(function() {
                        descriptionTextarea.focus();
                    }, 300);
                } else {
                    // مخفی کردن با انیمیشن
                    descriptionBox.style.opacity = '0';

                    setTimeout(function() {
                        descriptionBox.style.display = 'none';
                        // پاک کردن محتوای textarea (اختیاری)
                        // descriptionTextarea.value = '';
                    }, 300);
                }
            }

            // Event listener برای تغییر وضعیت checkbox
            checkbox.addEventListener('change', toggleDisabilityDescription);

            // بررسی وضعیت اولیه (برای حالت old() یا edit)
            // این خط مهم است چون اگر صفحه با validation error برگردد، باید وضعیت درست نمایش داده شود
            if (checkbox.checked) {
                descriptionBox.style.display = 'block';
                descriptionBox.style.opacity = '1';
            }
        });
    </script>
@endpush

