    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS, Alpine.js, Chart.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'SFMono-Regular', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --surface-bg: #F8FAFC;
            --surface-card: #FFFFFF;
            --border-subtle: #E2E8F0;
            --brand-primary: #0F766E;
            --brand-accent: #0D9488;
        }

        [x-cloak] { display: none !important; }

        body {
            background-color: var(--surface-bg);
            overflow-x: clip;
        }

        /* Canonical visually-hidden utility with focus-within opt-out for skip links */
        .visually-hidden:where(:not(:focus-within, :active)) {
            position: absolute !important;
            clip-path: inset(50%) !important;
            overflow: hidden !important;
            width: 1px !important;
            height: 1px !important;
            margin: -1px !important;
            padding: 0 !important;
            border: 0 !important;
            white-space: nowrap !important;
        }

        .skip-link:focus {
            position: fixed;
            inset-block-start: 0.75rem;
            inset-inline-start: 0.75rem;
            z-index: 9999;
            background: #0f172a;
            color: #ffffff;
            padding: 0.75rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        /* High-contrast focus ring per accessibility guidelines */
        :where(a:any-link, button, input, select, textarea, [tabindex="0"]):focus-visible {
            outline: 3px solid #0d9488;
            outline-offset: 2px;
        }

        /* Comfortable tap target sizes & iOS zoom prevention */
        input:not([type="checkbox"]):not([type="radio"]):not(.compact-input),
        select:not(.compact-input) {
            font-size: 0.9375rem;
            min-block-size: 44px;
        }
        .tap-target {
            min-block-size: 44px;
        }

        /* Deferred validation feedback after user interaction (:user-invalid) */
        input:user-invalid,
        select:user-invalid,
        input.user-invalid-fallback,
        select.user-invalid-fallback {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }

        input:user-invalid ~ .field-error-msg,
        input.user-invalid-fallback ~ .field-error-msg {
            display: flex !important;
        }

        /* Responsive container-query table wrapper with sticky headers */
        .table-wrapper {
            overflow: auto;
            max-inline-size: 100%;
            container-type: inline-size;
        }
        .table-wrapper table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .table-wrapper thead {
            position: sticky;
            inset-block-start: 0;
            z-index: 2;
        }
        .table-wrapper tbody th {
            position: sticky;
            inset-inline-start: 0;
            z-index: 1;
            background: #ffffff;
        }
        .table-wrapper tbody tr:hover th {
            background: #f8fafc;
        }

        /* Native Modal <dialog> scroll containment & backdrop (dialog guide) */
        dialog:modal {
            overscroll-behavior: contain;
        }
        dialog::backdrop {
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            overflow: hidden;
            overscroll-behavior: contain;
        }

        /* Active Sidebar & Navbar Indicator (not color alone — WCAG 1.4.1) */
        .dx-nav-link {
            position: relative;
        }
        .dx-nav-link[aria-current="page"]::before {
            content: "";
            position: absolute;
            inset-block: 0.375rem;
            inset-inline-start: 0;
            border-inline-start: 3px solid #0f766e;
            border-radius: 0 3px 3px 0;
        }
        .dx-public-link {
            position: relative;
        }
        .dx-public-link[aria-current="page"]::after {
            content: "";
            position: absolute;
            inset-inline: 0.5rem;
            inset-block-end: -2px;
            border-block-end: 2.5px solid #0f766e;
        }

        /* Accessible Tabbed Interface (tabs guide) */
        .dx-tablist {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            overflow-x: auto;
            scrollbar-width: none;
            scroll-snap-type: inline proximity;
            border-block-end: 1px solid #e2e8f0;
        }
        .dx-tablist::-webkit-scrollbar {
            display: none;
        }
        .dx-tab-btn {
            position: relative;
            flex: none;
            scroll-snap-align: start;
            white-space: nowrap;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            background: transparent;
            border: 0;
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .dx-tab-btn:hover {
            color: #0f172a;
        }
        .dx-tab-btn[aria-selected="true"] {
            color: #0f766e;
        }
        .dx-tab-btn[aria-selected="true"]::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            border-block-end: 3px solid #0f766e;
        }

        /* Native Popover Menus & Anchor Positioning with Fallback (menu & navbar guides) */
        @supports not selector(:popover-open) {
            [popover]:not(.\:popover-open) {
                display: none;
            }
        }
        [popover].dx-menu-popover {
            padding: 0;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            background: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
            overflow: visible;
        }
        [popover].dx-menu-popover:is(:popover-open, .\:popover-open) {
            display: block;
        }
        @supports (position-area: block-end) {
            [popover].dx-menu-popover {
                position-area: block-end span-inline-start;
                position-try-fallbacks: flip-block, flip-inline, flip-block flip-inline;
                inset: auto;
                margin-block-start: 0.5rem;
            }
            [popover].dx-menu-popover.dx-popover-start {
                position-area: block-end span-inline-end;
            }
        }
        @supports not (position-area: block-end) {
            [popover].dx-menu-popover {
                position: fixed;
                inset: auto;
                margin: 0;
            }
        }
        @media (prefers-reduced-motion: no-preference) {
            [popover].dx-menu-popover {
                opacity: 0;
                transition: opacity 0.14s ease, display 0.14s ease, overlay 0.14s ease;
                transition-behavior: allow-discrete;
            }
            [popover].dx-menu-popover:popover-open {
                opacity: 1;
            }
            @starting-style {
                [popover].dx-menu-popover:popover-open {
                    opacity: 0;
                }
            }
        }

        /* Mobile Navigation Drawer (<dialog class="Drawer">) per navigation-drawer guide */
        .Drawer {
            --drawer-width: min(280px, 84dvw, 100dvw - 24px);
            --drawer-backdrop: 0;
            inset: 0;
            width: auto;
            height: auto;
            max-width: none;
            max-height: none;
            margin: 0;
            padding: 0;
            background: transparent;
            border: 0;
            overflow: visible;
        }
        .Drawer::backdrop {
            background: #0f172a;
            backdrop-filter: none;
            opacity: calc(var(--drawer-backdrop) * 0.52);
        }
        .Drawer-scroller {
            position: relative;
            display: grid;
            grid-template-columns: var(--drawer-width) 100%;
            overflow-x: scroll;
            overscroll-behavior: none;
            scrollbar-width: none;
            scroll-snap-type: x mandatory;
        }
        .Drawer-scroller::-webkit-scrollbar {
            display: none;
        }
        @media (prefers-reduced-motion: no-preference) {
            .Drawer-scroller {
                scroll-behavior: smooth;
            }
        }
        .Drawer-scroller::after {
            content: '';
            scroll-snap-align: end;
            scroll-initial-target: nearest;
        }
        .Drawer-sheet {
            display: flex;
            flex-direction: column;
            height: 100svh;
            background: #ffffff;
            color: #0f172a;
            overflow-y: auto;
            scroll-snap-align: start;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
        }
        @supports (animation-timeline: scroll()) {
            .Drawer {
                timeline-scope: --drawer-fade;
                animation: fade-drawer-backdrop linear both;
                animation-timeline: --drawer-fade;
            }
            .Drawer-scroller {
                scroll-timeline: --drawer-fade x;
            }
            @property --drawer-backdrop {
                syntax: '<number>';
                inherits: true;
                initial-value: 0;
            }
            @keyframes fade-drawer-backdrop {
                0% { --drawer-backdrop: 1; }
                100% { --drawer-backdrop: 0; }
            }
        }
    </style>
