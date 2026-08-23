<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Label Printer Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for direct TSPL/ZPL label printing to TSC and compatible
    | label printers. Supports both network (TCP/IP) and USB connections.
    |
    */

    'printer' => [
        // Printer command language: 'tspl' for TSC printers or 'zpl' for Zebra-compatible printers
        'language' => env('LABEL_PRINTER_LANGUAGE', 'tspl'),

        // Connection type: 'network' or 'usb'
        'connection' => env('LABEL_PRINTER_CONNECTION', 'network'),

        // Network printer IP address (used when connection = 'network')
        'host' => env('LABEL_PRINTER_HOST', '192.168.1.100'),

        // Network printer port (default 9100 for ZPL/TSPL printers)
        'port' => env('LABEL_PRINTER_PORT', 9100),

        // Windows shared printer name (used when connection = 'usb')
        // Example: 'TSC_TTP-244_Pro' or '\\COMPUTER\PrinterShare'
        'usb_printer_name' => env('LABEL_PRINTER_USB_NAME', ''),

        // Connection timeout in seconds
        'timeout' => env('LABEL_PRINTER_TIMEOUT', 5),
    ],

    'label' => [
        // 96 mm roll with two 45x30 mm labels per printed row
        'paper_width_mm' => 96,
        'width_mm' => 45,
        'height_mm' => 30,

        // Print resolution in DPI (dots per inch)
        'dpi' => 203,

        // Number of columns per label row (for batch printing)
        'columns' => 2,

        // Space between the two labels in each row
        'gap_mm' => 3,

        // Space between the QR code and the client code in horizontal layout
        'qr_text_gap_mm' => -8,

        // Rotation for the client code in degrees: 0, 90, 180, 270
        'qr_text_rotation_deg' => 0,

        // Side margin from the roll edges in mm
        'edge_margin_mm' => 1.5,

        // Top and bottom margins inside each label in mm
        'top_margin_mm' => 3,
        'bottom_margin_mm' => 3,

        // Default layout for QR + client code content
        'layout_mode' => 'horizontal',
    ],

    'qr_code' => [
        // QR code size in dots (printer pixels)
        'size_dots' => 180,

        // Error correction level: L, M, Q, H
        'error_correction' => 'M',

        // Magnification factor (1-10)
        'magnification' => 4,
    ],

    'text' => [
        // Font size for person code text (TSPL font number or ZPL ^A size)
        'font_size' => 24,

        // Text offset from bottom of label in dots
        'bottom_offset_dots' => 10,
    ],

];
