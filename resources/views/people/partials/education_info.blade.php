<div class="row mt-3">
    <div class="col-md-12">
        <h5>۶. سطح نیاز</h5>
    </div>

    <div class="col-md-4">
        <label for="need_level_id" class="form-label">سطح نیاز <span class="text-danger">*</span></label>
        <select name="need_level_id" id="need_level_id" class="form-control">
            <option value="">انتخاب سطح نیاز...</option>
            @foreach($needLevelTypes as $level)
                <option value="{{ $level->id }}" {{ old('need_level_id') == $level->id ? 'selected' : '' }}>
                    {{ $level->title }} - {{ $level->code }}
                </option>
            @endforeach
        </select>
        @error('need_level_id')
        <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>

    {{-- فیلدهای تاریخ ارزیابی و نام ارزیاب از اینجا حذف شدند --}}
</div>
