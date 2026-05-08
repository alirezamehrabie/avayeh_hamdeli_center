import './bootstrap';
import 'bootstrap/dist/js/bootstrap.min.js';
import * as bootstrap from 'bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Alpine = Alpine;
window.bootstrap = bootstrap;

Alpine.data('rialAmountInput', (model) => ({
    model,
    formatted: '',
    init() {
        this.formatted = this.format(this.model);

        this.$watch('model', (value) => {
            this.formatted = this.format(value);
        });
    },
    normalize(value) {
        const persianDigits = '۰۱۲۳۴۵۶۷۸۹';
        const arabicDigits = '٠١٢٣٤٥٦٧٨٩';

        return String(value ?? '')
            .replace(/[۰-۹]/g, digit => persianDigits.indexOf(digit))
            .replace(/[٠-٩]/g, digit => arabicDigits.indexOf(digit))
            .replace(/\D/g, '');
    },
    format(value) {
        const digits = this.normalize(value);

        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },
    formatInput() {
        const digits = this.normalize(this.formatted);

        this.formatted = this.format(digits);
        this.model = digits;
    },
}));

Livewire.start();
