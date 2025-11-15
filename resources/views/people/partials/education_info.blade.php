<div class="mb-5">
    <h4 class="border-bottom pb-2 mb-3">۶. ارزیابی اولیه نیازمندی</h4>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="need_level" class="form-label">سطح نیاز <span class="text-danger">*</span></label>
            <select name="need_level_id" class="form-control">
                <option value="">انتخاب سطح نیاز...</option>
                @foreach(\App\Models\NeedLevelType::orderBy('severity_order')->get() as $level)
                    <option value="{{ $level->id }}">{{ $level->code }} - {{ $level->title }}</option>
                @endforeach
            </select>

        </div>
        <div class="col-md-4">
            <label for="evaluation_date" class="form-label">تاریخ ارزیابی <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="evaluation_date" value="{{ old('evaluation_date') }}" required>
        </div>
        <div class="col-md-4">
            <label for="reviewer_name" class="form-label">نام ارزیاب <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="reviewer_name" value="{{ old('reviewer_name') }}" required>
        </div>
    </div>
</div>
