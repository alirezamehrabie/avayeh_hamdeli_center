<div>
    <div>
        @props([
        'model' => 'amount',
        'unit' => 'ریال',
    ])

        <p
            x-show="{{ $model }} !== '' && {{ $model }} !== null"
            x-cloak
            x-transition.opacity.scale.95.duration.300ms
            class="mt-2 d-inline-flex align-items-center gap-1 px-3 py-2 small fw-semibold text-green-600 bg-green-50 rounded-pill"
            x-text="new Intl.NumberFormat('en-US').format(Number({{ $model }} || 0)) + ' {{ $unit }}'"
        ></p>

    </div>
</div>
