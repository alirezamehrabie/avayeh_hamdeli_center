<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: {{ $paperWidthMm }}mm {{ $labelHeightMm + $topMarginMm + $bottomMarginMm }}mm;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #111827;
            font-family: Tahoma, Arial, sans-serif;
        }

        .sheet {
            box-sizing: border-box;
            width: {{ $paperWidthMm }}mm;
            padding: {{ $topMarginMm }}mm {{ $edgeMarginMm }}mm {{ $bottomMarginMm }}mm;
        }

        .sheet.rotate180 {
            transform: rotate(180deg);
            transform-origin: center center;
        }

        .row {
            display: grid;
            grid-template-columns: repeat(2, {{ $labelWidthMm }}mm);
            gap: {{ $gapMm }}mm;
            page-break-after: always;
        }

        .row:last-child {
            page-break-after: auto;
        }

        .label {
            box-sizing: border-box;
            width: {{ $labelWidthMm }}mm;
            height: {{ $labelHeightMm }}mm;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            page-break-inside: avoid;
        }

        .label.vertical {
            flex-direction: column;
            gap: 2mm;
        }

        .label.horizontal {
            flex-direction: row;
            gap: {{ $qrTextGapMm }}mm;
        }

        .qr {
            width: {{ max(10, round($qrSizeDots * 25.4 / max(1, $dpi), 2)) }}mm;
            height: {{ max(10, round($qrSizeDots * 25.4 / max(1, $dpi), 2)) }}mm;
            flex: 0 0 auto;
        }

        .qr svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .code {
            font-size: {{ max(6, round($textFontSize / 2)) }}px;
            font-weight: 700;
            line-height: 1.1;
            text-align: center;
            direction: rtl;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <div class="sheet {{ $rotate180 ? 'rotate180' : '' }}">
        @foreach(array_chunk($items, 2) as $row)
            <div class="row">
                @foreach($row as $item)
                    <div class="label {{ $layoutMode === 'horizontal' ? 'horizontal' : 'vertical' }}">
                        <div class="qr">{!! $item['qr_svg'] !!}</div>
                        <div class="code">{{ $item['person_code'] }}</div>
                    </div>
                @endforeach

                @for($i = count($row); $i < 2; $i++)
                    <div class="label {{ $layoutMode === 'horizontal' ? 'horizontal' : 'vertical' }}"></div>
                @endfor
            </div>
        @endforeach
    </div>
</body>
</html>
