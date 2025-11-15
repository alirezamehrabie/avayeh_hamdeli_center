@php
    /**
     * با استفاده از collect() اطمینان می‌دهیم که اگر
     * $person->skills برابر null باشد، یک Collection خالی ساخته شود
     */
    $selectedSkills = old('skills', collect($person->skills)->pluck('id')->toArray());
@endphp

<div class="form-group">
    <label for="skills">استعدادها</label>
    <div class="row">
        @foreach($skills as $skill)
            <div class="col-md-4">
                <div class="form-check">
                    <input
                        type="checkbox"
                        name="skills[]"
                        id="skill_{{ $skill->id }}"
                        value="{{ $skill->id }}"
                        class="form-check-input"
                        {{ in_array($skill->id, $selectedSkills) ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="skill_{{ $skill->id }}">
                        {{ $skill->name }}
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    @error('skills')
    <small class="text-danger d-block mt-1">{{ $message }}</small>
    @enderror
</div>
