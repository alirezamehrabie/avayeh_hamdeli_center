{{-- Sadaat Status Section --}}
<div class="sadaat-section neumorphic-card">
    <h4 class="section-title">نسب سادات / عام</h4>

    <div class="form-group neumorphic-radio">
        <label>وضعیت مددجو:</label><br>
        <label>
            <input type="radio" name="sadaat_status" value="سادات" id="sadaat_option_sadaat">
            سادات
        </label>
        <label style="margin-right:20px;">
            <input type="radio" name="sadaat_status" value="عام" id="sadaat_option_am">
            عام
        </label>
    </div>

    <div class="form-group neumorphic-input" id="sadaat_relation_box" style="display: none;">
        <label for="sadaat_relation">انتخاب نسب سادات:</label>
        <select name="sadaat_relation" id="sadaat_relation" class="form-control">
            <option value="">انتخاب کنید</option>
            <option value="موسوی">سادات موسوی</option>
            <option value="حسنی">سادات حسنی</option>
            <option value="حسینی">سادات حسینی</option>
            <option value="طباطبائی">سادات طباطبائی</option>
            <option value="هاشمی">سادات هاشمی</option>
        </select>
    </div>
</div>

<script>
    // نمایش نسب سادات فقط وقتی "سادات" انتخاب شود
    const sadaatRadio = document.getElementById('sadaat_option_sadaat');
    const amRadio = document.getElementById('sadaat_option_am');
    const sadaatBox = document.getElementById('sadaat_relation_box');

    sadaatRadio.addEventListener('change', () => sadaatBox.style.display = 'block');
    amRadio.addEventListener('change', () => sadaatBox.style.display = 'none');
</script>
