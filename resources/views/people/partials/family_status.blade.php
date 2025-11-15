{{-- Family Status Form Section --}}
<div class="family-status-section neumorphic-card">
    <h4 class="section-title">وضعیت خانوادگی مددجو</h4>

    {{-- guardian_relation --}}
    <div class="form-group neumorphic-input" id="guardian_relation_box">
        <label for="guardian_relation">نسب سرپرست (در حالت سایر):</label>
        <select name="guardian_relation" id="guardian_relation" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدربزرگ">پدربزرگ</option>
            <option value="مادربزرگ">مادربزرگ</option>
            <option value="اقوام">اقوام</option>
            <option value="سایر">سایر</option>
        </select>
    </div>


    {{-- economic_decile --}}
    <div class="form-group">
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
    <div class="form-group neumorphic-input">
        <label for="living_parents">والدین در قید حیات:</label>
        <select name="living_parents" id="living_parents" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدر">پدر</option>
            <option value="مادر">مادر</option>
            <option value="هر دو (پدر و مادر)">هر دو (پدر و مادر)</option>
        </select>
    </div>

    {{-- deceased_parent --}}
    <div class="form-group neumorphic-input">
        <label for="deceased_parent">کدام والد فوت شده است؟</label>
        <select name="deceased_parent" id="deceased_parent" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدر">پدر</option>
            <option value="مادر">مادر</option>
            <option value="هر دو (پدر و مادر)">هر دو</option>
        </select>
    </div>

    {{-- death_year --}}
    <div class="form-group neumorphic-input">
        <label for="death_year">سال فوت:</label>
        <input type="number" name="death_year" id="death_year" class="form-control" placeholder="مثلاً ۱۳۹۵">
    </div>

    {{-- death_reason --}}
    <div class="form-group neumorphic-input">
        <label for="death_reason">علت فوت:</label>
        <input type="text" name="death_reason" id="death_reason" class="form-control"
               placeholder="مثلاً بیماری یا حادثه">
    </div>

    {{-- divorced_parent --}}
    <div class="form-group neumorphic-input">
        <label for="divorced_parent">کدام والد جدا شده است؟</label>
        <select name="divorced_parent" id="divorced_parent" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدر">پدر</option>
            <option value="مادر">مادر</option>
            <option value="هر دو (پدر و مادر)">هر دو</option>
        </select>
    </div>

    {{-- remarried_parent --}}
    <div class="form-group neumorphic-input">
        <label for="remarried_parent">کدام والد ازدواج مجدد کرده است؟</label>
        <select name="remarried_parent" id="remarried_parent" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="پدر">پدر</option>
            <option value="مادر">مادر</option>
            <option value="هر دو (پدر و مادر)">هر دو</option>
        </select>
    </div>

    {{-- children_from_previous_marriage --}}
    <div class="form-group neumorphic-input">
        <label for="children_from_previous_marriage">تعداد فرزندان از ازدواج قبلی سرپرست:</label>
        <input type="number" name="children_from_previous_marriage" id="children_from_previous_marriage"
               class="form-control" min="0">
    </div>

    {{-- has_parent_disability --}}
    <div class="form-group neumorphic-checkbox">
        <label>
            <input type="checkbox" name="has_parent_disability" id="has_parent_disability">
            آیا در والدین، معلولیت وجود دارد؟
        </label>
    </div>

    {{-- parent_disability_description --}}
    <div class="form-group neumorphic-input" id="disability_description_box" style="display: none;">
        <label for="parent_disability_description">توضیحات نوع معلولیت والد:</label>
        <textarea name="parent_disability_description" id="parent_disability_description" class="form-control" rows="3"
                  placeholder="شرح مختصر نوع و میزان معلولیت"></textarea>
    </div>
</div>

<script>
    document.getElementById('has_parent_disability').addEventListener('change', function () {
        document.getElementById('disability_description_box').style.display = this.checked ? 'block' : 'none';
    });
</script>
