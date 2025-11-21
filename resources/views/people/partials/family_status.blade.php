{{-- Family Status Form Section --}}

<div class="family-status-section neumorphic-card">
    <h4 class="section-title">وضعیت خانوادگی مددجو</h4>

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
    <div class="form-check-reverse">

        <input class="form-check-input" type="checkbox" name="has_parent_disability" id="has_parent_disability" value="1"
            {{ old('has_parent_disability') ? 'checked' : '' }}>

        <label class="form-check-label" for="has_parent_disability">
            آیا والدین دارای معلولیت هستند؟
        </label>


    </div>

    {{-- parent_disability_description --}}
    <div class="form-group neumorphic-input" id="disability_description_box" style="display: none;">
        <label for="parent_disability_description">توضیحات نوع معلولیت والد:</label>
        <textarea name="parent_disability_description" id="parent_disability_description" class="form-control" rows="3"
                  placeholder="شرح مختصر نوع و میزان معلولیت"></textarea>
    </div>
</div>

