<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
                color: #000;
            }

            html.dark {
                background-color: oklch(0.145 0 0);
                color: #000;
            }

            /* Fix select dropdown visibility - Maximum specificity */
            select, select#client-select, #client-select,
            select.mt-1, select.block, select.w-full,
            .mt-1.block.w-full.rounded-md.border-gray-300.shadow-sm.focus\\:border-indigo-500.focus\\:ring-indigo-500,
            .mt-1.block.w-full.rounded-md.border-gray-300.shadow-sm.focus\\:border-indigo-500.focus\\:ring-indigo-500.text-black.sm\\:text-sm,
            .mt-1.block.w-full.rounded-md.border-gray-300.shadow-sm.focus\\:border-indigo-500.p-4.focus\\:ring-indigo-500.text-black.sm\\:text-sm {
                background-color: white !important;
                color: #000000 !important;
                border: 3px solid #4f46e5 !important;
                padding: 16px 50px 16px 16px !important;
                appearance: none !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 5"><path fill="%23000" d="M2 0L0 2h4zm0 5L0 3h4z"/></svg>') !important;
                background-repeat: no-repeat !important;
                background-position: right 16px center !important;
                background-size: 16px !important;
                cursor: pointer !important;
                font-size: 18px !important;
                font-weight: 600 !important;
                line-height: 1.5 !important;
                border-radius: 8px !important;
                margin-top: 0.5rem !important;
                display: block !important;
                width: 100% !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
                transition: all 0.3s ease !important;
            }

            /* Force placeholder text when no value is selected */
            select:not([value]):not(:focus):not(:valid)::before,
            select[value=""]:not(:focus)::before,
            select#client-select:not([value]):not(:focus)::before {
                content: "-- Select a client --" !important;
                color: #9ca3af !important;
                font-style: italic !important;
                position: absolute !important;
                pointer-events: none !important;
                left: 12px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }

            /* Alternative approach - style the select when empty */
            select:not(:focus):invalid,
            select[value=""]:not(:focus),
            select#client-select:not(:focus):invalid,
            select#client-select[value=""]:not(:focus) {
                color: #9ca3af !important;
                font-style: italic !important;
            }

            /* Force the select to show placeholder when empty value */
            select[value=""]:not(:focus)::before {
                content: "-- Select a client --" !important;
                color: #9ca3af !important;
                font-style: italic !important;
                position: absolute !important;
                left: 12px !important;
                top: 12px !important;
                pointer-events: none !important;
                z-index: 1 !important;
            }

            select:focus, select#client-select:focus, #client-select:focus {
                border-color: #312e81 !important;
                outline: none !important;
                box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.3), 0 8px 12px -2px rgba(0, 0, 0, 0.15) !important;
                transform: translateY(-2px) !important;
            }

            select option, select#client-select option, #client-select option {
                background-color: white !important;
                color: #1f2937 !important;
                padding: 12px 16px !important;
                font-size: 16px !important;
                font-weight: 500 !important;
                line-height: 1.6 !important;
            }

            select option[value=""], select#client-select option[value=""], #client-select option[value=""],
            select option:first-child, select#client-select option:first-child, #client-select option:first-child {
                color: #9ca3af !important;
                font-style: italic !important;
            }

            /* Ensure placeholder text shows when nothing is selected */
            select:invalid, select#client-select:invalid, #client-select:invalid {
                color: #9ca3af !important;
                font-style: italic !important;
            }

            select option:hover,
            select option:focus,
            select option:checked {
                background-color: #f3f4f6 !important;
            }

            select option:disabled {
                color: #9ca3af !important;
                background-color: #f9fafb !important;
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
