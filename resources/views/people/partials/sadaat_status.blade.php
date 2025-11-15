{{-- وضعیت سادات --}}
<div class="form-group">
    <label class="d-block">وضعیت سادات <span class="text-danger">*</span></label>
    <label class="radio-inline me-3">
        <input
            type="radio"
            name="sadaat_status"
            value="عام"
            {{ old('sadaat_status', 'عام') === 'عام' ? 'checked' : '' }}
        >
        عام
    </label>
    <label class="radio-inline">
        <input
            type="radio"
            name="sadaat_status"
            value="سادات"
            {{ old('sadaat_status') === 'سادات' ? 'checked' : '' }}
        >
        سادات
    </label>
    @error('sadaat_status')
    <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

{{-- نسب سادات (مخفی/نمایان بر اساس رادیو بالا) --}}
<div
    id="sadaat_relation_container"
    class="form-group"
    style="display: none;"
>
    <label for="sadaat_relation_id">نسب سادات <span class="text-danger">*</span></label>
    <select
        name="sadaat_relation_id"
        id="sadaat_relation_id"
        class="form-control"
    >
        <option value="">— انتخاب کنید —</option>
        @foreach($sadaatRelations as $rel)
            <option
                value="{{ $rel->id }}"
                {{ old('sadaat_relation_id') == $rel->id ? 'selected' : '' }}
            >
                {{ $rel->name }}
            </option>
        @endforeach
    </select>
    @error('sadaat_relation_id')
    <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const radios = document.querySelectorAll('input[name="sadaat_status"]');
        const container = document.getElementById('sadaat_relation_container');

        function toggleSadaatRelation() {
            const selected = document.querySelector('input[name="sadaat_status"]:checked').value;
            if (selected === 'سادات') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
                // در صورت نیاز مقدار قبلی را پاک می‌کنیم
                const select = container.querySelector('select');
                if (select) select.value = '';
            }
        }

        radios.forEach(radio => {
            radio.addEventListener('change', toggleSadaatRelation);
        });

        // حالت اولیه هنگام بارگذاری صفحه
        toggleSadaatRelation();
    });
</script>
