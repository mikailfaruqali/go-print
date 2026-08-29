<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PDF Templates</title>

    <!-- Vector SVG Favicon Matching sn-kit Brand Exactly -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3CradialGradient id='sg' cx='30%25' cy='30%25' r='70%25'%3E%3Cstop offset='0%25' stop-color='%232ecc71'/%3E%3Cstop offset='40%25' stop-color='%23249658'/%3E%3Cstop offset='100%25' stop-color='%231a6b3f'/%3E%3C/radialGradient%3E%3C/defs%3E%3Crect x='4' y='4' width='92' height='92' rx='8' fill='url(%23sg)'/%3E%3Cg fill='%23fff' transform='translate(28,28)'%3E%3Crect x='0' y='0' width='19' height='19' rx='2'/%3E%3Crect x='23' y='0' width='19' height='19' rx='2'/%3E%3Crect x='0' y='23' width='19' height='19' rx='2'/%3E%3Crect x='23' y='23' width='19' height='19' rx='2'/%3E%3C/g%3E%3C/svg%3E">

    <!-- Bootstrap 5 & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css">

    <style>
        /* ═════════════════════════════════════════════════════════════════
           DESIGN TOKENS — sn-kit GitHub Dark & GitHub Light Palettes
           ═════════════════════════════════════════════════════════════════ */
        :root {
            color-scheme: dark;
            --sn-bg: #151b24;
            --sn-surface: #1b222b;
            --sn-surface-rgb: 27, 34, 43;
            --sn-card: #262d36;
            --sn-border: #3a424c;
            --sn-hover: #343c46;
            --sn-active: #2a313a;
            --sn-text: #e6edf3;
            --sn-text-muted: #8b949e;
            --sn-accent: #58a6ff;
            --sn-accent-rgb: 88, 166, 255;
            --sn-accent-dark: #2f81f7;

            --sn-ic-primary: #58a6ff;
            --sn-ic-success: #3fb950;
            --sn-ic-warning: #f0883e;
            --sn-ic-danger: #ff7b72;
            --sn-ic-purple: #c084fc;

            --sn-btn-on-color: #ffffff;
            --sn-btn-primary: #1f6feb;
            --sn-btn-primary-hover: #388bfd;
            --sn-btn-danger: #da3633;
            --sn-btn-danger-hover: #e24545;
            --sn-btn-success: #238636;
            --sn-btn-success-hover: #2ea043;

            --sn-form-input-bg: transparent;
            --sn-form-border: #586270;
            --sn-form-hover-border: #6a7380;
            --sn-form-height: 42px;

            --sn-radius: 4px;
            --sn-radius-sm: 2px;
            --sn-radius-lg: 8px;
            --sn-radius-pill: 20px;

            --sn-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            --sn-font-mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        }

        [data-bs-theme="light"] {
            color-scheme: light;
            --sn-bg: #fffefb;
            --sn-surface: #fbf8f2;
            --sn-surface-rgb: 251, 248, 242;
            --sn-card: #fffefc;
            --sn-border: #e4dbcf;
            --sn-hover: #d7e8f3;
            --sn-active: #c4d9ea;
            --sn-text: #302b24;
            --sn-text-muted: #746c61;
            --sn-accent: #0969da;
            --sn-accent-rgb: 9, 105, 218;
            --sn-accent-dark: #0547a0;

            --sn-ic-primary: #0969da;
            --sn-ic-success: #1a7f37;
            --sn-ic-warning: #9a6700;
            --sn-ic-danger: #d1242f;
            --sn-ic-purple: #8250df;

            --sn-btn-on-color: #ffffff;
            --sn-btn-primary: #0969da;
            --sn-btn-primary-hover: #0860ca;
            --sn-btn-danger: #d1242f;
            --sn-btn-danger-hover: #a40e26;
            --sn-btn-success: #1a7f37;
            --sn-btn-success-hover: #116329;

            --sn-form-input-bg: #ffffff;
            --sn-form-border: #d5c8b5;
            --sn-form-hover-border: #c4b59f;
        }

        body {
            background-color: var(--sn-bg);
            color: var(--sn-text);
            font-family: var(--sn-font);
            font-size: 14px;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* ── Semantic Icon Colors Matching sn-kit ── */
        i, svg {
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
        }

        .ic-primary { color: var(--sn-ic-primary) !important; }
        .ic-success { color: var(--sn-ic-success) !important; }
        .ic-warning { color: var(--sn-ic-warning) !important; }
        .ic-danger  { color: var(--sn-ic-danger) !important; }
        .ic-purple  { color: var(--sn-ic-purple) !important; }

        /* ── Navbar ── */
        .sn-navbar {
            background: var(--sn-surface);
            border-bottom: 1px solid var(--sn-border);
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sn-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 600;
            color: var(--sn-text);
            text-decoration: none;
        }

        .sn-brand-icon-svg {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
        }

        /* ── Buttons ── */
        .sn-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 38px;
            min-width: 90px;
            padding: 0 18px;
            font-size: 13px;
            font-weight: 500;
            border-radius: var(--sn-radius);
            border: 1px solid var(--sn-border);
            background: var(--sn-card);
            color: var(--sn-text);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .sn-btn:hover {
            background: var(--sn-hover);
            color: var(--sn-text);
            border-color: var(--sn-form-hover-border);
        }

        .sn-btn-primary {
            background-color: var(--sn-btn-primary) !important;
            border-color: var(--sn-btn-primary) !important;
            color: var(--sn-btn-on-color) !important;
        }
        .sn-btn-primary:hover {
            background-color: var(--sn-btn-primary-hover) !important;
            border-color: var(--sn-btn-primary-hover) !important;
            color: var(--sn-btn-on-color) !important;
        }

        .sn-btn-primary i,
        .sn-btn-primary .fa-plus {
            color: var(--sn-btn-on-color) !important;
        }

        .sn-btn-danger {
            color: var(--sn-ic-danger);
        }
        .sn-btn-danger:hover {
            background: var(--sn-btn-danger);
            border-color: var(--sn-btn-danger);
            color: var(--sn-btn-on-color);
        }

        .sn-btn-icon {
            min-width: 34px;
            width: 34px;
            height: 34px;
            padding: 0;
            background: transparent;
        }

        /* ── Container & Table ── */
        .sn-container {
            max-width: 1180px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .sn-table-box {
            background: var(--sn-surface);
            border: 1px solid var(--sn-border);
            border-radius: var(--sn-radius);
            overflow: hidden;
        }

        .sn-table-header {
            padding: 12px 18px;
            background: var(--sn-surface);
            border-bottom: 1px solid var(--sn-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Integrated Search Box matching sn-kit */
        .sn-search-group {
            width: 260px;
            height: 32px;
            display: flex;
            align-items: center;
            border: 1px solid var(--sn-border);
            border-radius: var(--sn-radius);
            background-color: var(--sn-bg);
            transition: border-color 0.15s, box-shadow 0.15s;
            position: relative;
        }

        .sn-search-group:focus-within {
            border-color: var(--sn-accent);
            box-shadow: 0 0 0 1px var(--sn-accent);
        }

        .sn-search-group .sn-search-icon {
            position: absolute;
            inset-inline-start: 10px;
            color: var(--sn-text-muted);
            font-size: 13px;
            pointer-events: none;
        }

        .sn-search-group input {
            width: 100%;
            height: 100%;
            padding-inline-start: 32px;
            padding-inline-end: 10px;
            font-size: 13px;
            background: transparent;
            border: none;
            outline: none;
            color: var(--sn-text);
        }

        @media (max-width: 576px) {
            .sn-search-group {
                width: 100%;
            }
        }

        /* Table */
        .sn-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            background: transparent;
        }

        .sn-table th {
            background: var(--sn-surface);
            color: var(--sn-text-muted);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 18px;
            border-bottom: 1px solid var(--sn-border);
            white-space: nowrap;
        }

        .sn-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--sn-border);
            vertical-align: middle;
            color: var(--sn-text);
            font-size: 13px;
        }

        .sn-table tbody tr {
            transition: background-color 0.1s;
        }

        .sn-table tbody tr:hover {
            background-color: var(--sn-hover);
        }

        .sn-table tbody tr:last-child td {
            border-bottom: none;
        }

        .sn-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 500;
            border-radius: var(--sn-radius-sm);
            background: var(--sn-card);
            border: 1px solid var(--sn-border);
            color: var(--sn-text-muted);
            margin-inline-end: 4px;
        }

        .sn-badge-locale {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 22px;
            padding: 0 7px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            border-radius: var(--sn-radius-sm);
            background: rgba(var(--sn-accent-rgb), 0.15);
            color: var(--sn-accent);
            border: 1px solid rgba(var(--sn-accent-rgb), 0.3);
        }

        /* ── Fullscreen Modal Layout ── */
        .modal.modal-fullscreen .modal-dialog {
            width: 100vw;
            height: 100vh;
            max-width: none;
            margin: 0;
        }

        .modal.modal-fullscreen .modal-content {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--sn-bg);
            border: none;
            border-radius: 0;
            color: var(--sn-text);
            overflow: hidden;
        }

        .modal.modal-fullscreen .modal-header {
            flex-shrink: 0;
            height: 52px;
            padding: 0 16px;
            background-color: var(--sn-surface);
            border-bottom: 1px solid var(--sn-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal.modal-fullscreen .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 24px 20px;
        }

        @media (max-width: 576px) {
            .modal.modal-fullscreen .modal-header {
                padding: 0 12px;
            }
            .modal.modal-fullscreen .modal-body {
                padding: 12px 10px;
            }
            .sn-form-card {
                padding: 14px 12px !important;
                margin-bottom: 12px !important;
            }
        }

        .modal-body-inner {
            max-width: 920px;
            margin: 0 auto;
        }

        .modal.modal-fullscreen .modal-footer {
            flex-shrink: 0;
            height: 56px;
            padding: 0 12px;
            background-color: var(--sn-surface);
            border-top: 1px solid var(--sn-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 15px;
            font-weight: 600;
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
            opacity: 0.7;
        }
        .btn-close:hover { opacity: 1; }
        [data-bs-theme="light"] .btn-close {
            filter: none;
        }

        /* Form Card Containers */
        .sn-form-card {
            background-color: var(--sn-surface);
            border: 1px solid var(--sn-border);
            border-radius: var(--sn-radius);
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .sn-form-card__title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--sn-accent);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--sn-text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .form-control {
            background-color: var(--sn-form-input-bg);
            border: 1px solid var(--sn-form-border);
            border-radius: var(--sn-radius);
            color: var(--sn-text);
            font-size: 13.5px;
            padding: 8px 14px;
            height: var(--sn-form-height);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-control:focus {
            background-color: var(--sn-form-input-bg);
            border-color: var(--sn-accent);
            color: var(--sn-text);
            box-shadow: 0 0 0 2px rgba(var(--sn-accent-rgb), 0.25);
        }

        textarea.form-control {
            height: auto;
            min-height: 95px;
            font-family: var(--sn-font-mono);
            font-size: 13px;
            line-height: 1.5;
            padding: 10px 14px;
            resize: vertical;
        }

        /* ── Exact sn-kit Select2 Stylesheet ── */
        .select2-container { 
            width: 100% !important; 
        }

        .select2-container .select2-selection--single {
            position: relative;
            height: var(--sn-form-height) !important;
            background: var(--sn-form-input-bg) !important;
            border: 1px solid var(--sn-form-border) !important;
            border-radius: var(--sn-radius) !important;
            display: flex !important;
            align-items: center !important;
            padding-inline: 12px 36px !important;
            padding-block: 0 !important;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
            cursor: pointer;
            box-shadow: none !important;
            outline: none !important;
            overflow: hidden !important;
        }

        .select2-container--open .select2-selection--single,
        .select2-container--focus .select2-selection--single {
            border-color: transparent !important;
            box-shadow: inset 0 0 0 1.5px var(--sn-accent) !important;
            outline: none !important;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            color: var(--sn-text) !important;
            font-size: 13.5px !important;
            line-height: 1.4 !important;
            padding: 0 !important;
            flex: 1;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .select2-container .select2-selection--single .select2-selection__placeholder {
            color: var(--sn-text-muted) !important;
        }

        /* sn-kit SVG Chevron */
        .select2-container .select2-selection__arrow { display: none !important; }

        .select2-container .select2-selection--single::after {
            content: '';
            position: absolute;
            inset-inline-end: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            background-color: var(--sn-text-muted);
            pointer-events: none;
            transition: transform 0.2s ease, background-color 0.18s;
            flex-shrink: 0;
            -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath d='M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z'/%3E%3C/svg%3E");
            mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cpath d='M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z'/%3E%3C/svg%3E");
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-size: contain;
            mask-size: contain;
        }

        .select2-container--open .select2-selection--single::after {
            transform: translateY(-50%) rotate(180deg);
            background-color: var(--sn-accent);
        }

        /* sn-kit Select2 Dropdown Panel */
        .select2-dropdown {
            background: var(--sn-card) !important;
            border: 1px solid var(--sn-border) !important;
            border-radius: var(--sn-radius) !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3) !important;
            overflow: hidden;
            z-index: 9999 !important;
            margin-top: 4px;
        }

        .select2-search--dropdown {
            padding: 10px 10px 0 !important;
            border-bottom: none !important;
            background: var(--sn-card) !important;
        }

        .select2-search--dropdown .select2-search__field {
            width: 100% !important;
            height: 36px;
            background: var(--sn-surface) !important;
            border: none !important;
            box-shadow: inset 0 0 0 1px var(--sn-form-border) !important;
            border-radius: var(--sn-radius) !important;
            padding-inline: 12px !important;
            font-size: 13px !important;
            color: var(--sn-text) !important;
            outline: none !important;
            transition: box-shadow 0.18s;
            display: block;
        }

        .select2-search--dropdown .select2-search__field:focus {
            box-shadow: inset 0 0 0 1.5px var(--sn-accent) !important;
        }

        /* ── Select2 Dropdown Options List ── */
        .select2-results__options {
            max-height: 240px;
            overflow-y: auto;
            padding: 4px;
        }

        .select2-results__options::-webkit-scrollbar {
            width: 6px;
        }
        .select2-results__options::-webkit-scrollbar-track {
            background: transparent;
        }
        .select2-results__options::-webkit-scrollbar-thumb {
            background: var(--sn-border);
            border-radius: var(--sn-radius-pill);
        }
        .select2-results__options::-webkit-scrollbar-thumb:hover {
            background: var(--sn-form-hover-border);
        }

        .select2-container--default .select2-results__option {
            padding: 7px 12px;
            font-size: 13px;
            color: var(--sn-text) !important;
            background-color: transparent !important;
            border-radius: var(--sn-radius-sm);
            margin-bottom: 2px;
            cursor: pointer;
            transition: background-color 0.12s ease, color 0.12s ease;
        }

        /* Hovered / Keyboard Active Option */
        .select2-container--default .select2-results__option--highlighted,
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: var(--sn-hover) !important;
            color: var(--sn-text) !important;
        }

        /* Selected (Active) Option */
        .select2-container--default .select2-results__option--selected,
        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: rgba(var(--sn-accent-rgb), 0.16) !important;
            color: var(--sn-accent) !important;
            font-weight: 500 !important;
        }

        /* Selected + Hovered / Highlighted Option */
        .select2-container--default .select2-results__option--selected.select2-results__option--highlighted,
        .select2-container--default .select2-results__option--highlighted[aria-selected="true"] {
            background-color: rgba(var(--sn-accent-rgb), 0.28) !important;
            color: var(--sn-accent) !important;
            font-weight: 500 !important;
        }

        /* Disabled options */
        .select2-container--default .select2-results__option[aria-disabled="true"] {
            color: var(--sn-text-muted) !important;
            cursor: not-allowed;
            background-color: transparent !important;
        }

        /* Clear button inside select2 */
        .select2-container--default .select2-selection--single .select2-selection__clear {
            position: absolute;
            inset-inline-end: 32px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            line-height: 1;
            color: var(--sn-text-muted);
            margin: 0;
            padding: 2px 4px;
            border-radius: var(--sn-radius-sm);
            transition: color 0.15s ease, background-color 0.15s ease;
            cursor: pointer;
            z-index: 2;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear:hover {
            color: var(--sn-ic-danger);
            background-color: rgba(255, 123, 114, 0.15);
        }

        /* Custom Scrollbar for Modal Body */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }
        .modal-body::-webkit-scrollbar-track {
            background: transparent;
        }
        .modal-body::-webkit-scrollbar-thumb {
            background: var(--sn-border);
            border-radius: var(--sn-radius-pill);
        }
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: var(--sn-form-hover-border);
        }

        /* ── Modal Close Button (Clean SVG × without focus outline box) ── */
        .btn-close {
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
            opacity: 0.6;
            transition: opacity 0.15s;
            filter: invert(1) grayscale(100%) brightness(200%);
            padding: 0;
            width: 28px;
            height: 28px;
        }
        .btn-close:hover,
        .btn-close:focus,
        .btn-close:active {
            opacity: 1 !important;
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
        }
        [data-bs-theme="light"] .btn-close {
            filter: none;
        }

        /* ── SweetAlert2 Theme Matching sn-kit ── */
        div.swal2-container {
            z-index: 99999 !important;
        }
        div.swal2-popup.sn-dialog,
        div.swal2-popup {
            background: var(--sn-card) !important;
            color: var(--sn-text) !important;
            border: 1px solid var(--sn-border) !important;
            border-radius: var(--sn-radius-lg) !important;
            font-family: var(--sn-font) !important;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.4) !important;
            padding: 24px !important;
        }
        .swal2-title {
            color: var(--sn-text) !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            padding: 0 0 8px !important;
        }
        .swal2-html-container {
            color: var(--sn-text-muted) !important;
            font-size: 13px !important;
            line-height: 1.5 !important;
            margin: 0 0 16px !important;
        }
        .swal2-close {
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
            color: var(--sn-text-muted) !important;
            font-family: inherit !important;
            font-size: 24px !important;
        }
        .swal2-close:hover,
        .swal2-close:focus {
            color: var(--sn-text) !important;
            box-shadow: none !important;
            outline: none !important;
            border: none !important;
        }
        .swal2-actions {
            gap: 8px !important;
            margin-top: 16px !important;
        }
        .swal2-actions button.swal2-confirm {
            background-color: var(--sn-btn-primary) !important;
            border: 1px solid var(--sn-btn-primary) !important;
            color: var(--sn-btn-on-color) !important;
            border-radius: var(--sn-radius) !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            padding: 8px 18px !important;
            box-shadow: none !important;
            outline: none !important;
        }
        .swal2-actions button.swal2-confirm:hover {
            background-color: var(--sn-btn-primary-hover) !important;
        }
        .swal2-actions button.swal2-cancel {
            background-color: var(--sn-card) !important;
            border: 1px solid var(--sn-border) !important;
            color: var(--sn-text) !important;
            border-radius: var(--sn-radius) !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            padding: 8px 18px !important;
            box-shadow: none !important;
            outline: none !important;
        }
        .swal2-actions button.swal2-cancel:hover {
            background-color: var(--sn-hover) !important;
        }
    </style>
</head>
<body>

    <!-- ── Navbar ── -->
    <header class="sn-navbar">
        <a href="{{ route('pdf.templates.index') }}" class="sn-brand">
            <svg viewBox="0 0 100 100" class="sn-brand-icon-svg">
                <defs>
                    <radialGradient id="sg" cx="30%" cy="30%" r="70%">
                        <stop offset="0%" stop-color="#2ecc71" />
                        <stop offset="40%" stop-color="#249658" />
                        <stop offset="100%" stop-color="#1a6b3f" />
                    </radialGradient>
                </defs>
                <rect x="4" y="4" width="92" height="92" rx="8" fill="url(#sg)" />
                <g fill="#fff" transform="translate(28,28)">
                    <rect x="0" y="0" width="19" height="19" rx="2" />
                    <rect x="23" y="0" width="19" height="19" rx="2" />
                    <rect x="0" y="23" width="19" height="19" rx="2" />
                    <rect x="23" y="23" width="19" height="19" rx="2" />
                </g>
            </svg>
            <span>PDF Templates</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <button class="sn-btn sn-btn-icon sn-btn-primary" id="btn-create-template" title="New Template">
                <i class="fa-solid fa-plus"></i>
            </button>
            <button class="sn-btn sn-btn-icon" id="btn-toggle-theme" title="Toggle Theme">
                <i class="fa-solid fa-moon" id="theme-icon"></i>
            </button>
        </div>
    </header>

    <!-- ── Main Content Container ── -->
    <main class="sn-container">
        <div class="sn-table-box">
            <div class="sn-table-header">
                <span class="fw-semibold" style="font-size: 13px; color: var(--sn-text);">Configured View Templates</span>
                <div class="sn-search-group">
                    <i class="fa-solid fa-magnifying-glass sn-search-icon"></i>
                    <input type="text" id="table-search" placeholder="Search view or locale...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="sn-table">
                    <thead>
                        <tr>
                            <th>Blade View</th>
                            <th style="width: 100px;">Locale</th>
                            <th>Configured Features</th>
                            <th style="width: 140px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="template-tbody">
                        @forelse($templates as $t)
                            <tr class="template-row" data-id="{{ $t->id }}" data-view="{{ $t->view }}" data-locale="{{ $t->locale }}">
                                <td>
                                    <div class="fw-semibold text-truncate" style="max-width: 380px;" title="{{ $t->view }}">
                                        @if($t->view === '*' || $t->view === 'all')
                                            <span class="sn-badge-locale me-1" style="background: var(--sn-hover); border-color: var(--sn-border);">*</span> <span class="text-muted">All Views (Fallback)</span>
                                        @else
                                            {{ $t->view }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="sn-badge-locale">{{ strtoupper($t->locale) }}</span>
                                </td>
                                <td>
                                    @if(!empty($t->options['pageWidth']) && !empty($t->options['pageHeight']))
                                        <span class="sn-tag"><i class="fa-regular fa-file"></i> {{ $t->options['pageWidth'] }} × {{ $t->options['pageHeight'] }}</span>
                                    @else
                                        <span class="sn-tag"><i class="fa-regular fa-file"></i> {{ $t->options['paper'] ?? 'A4' }}</span>
                                    @endif
                                    <span class="sn-tag"><i class="fa-solid fa-arrows-up-down-left-right"></i> {{ $t->options['orientation'] ?? 'portrait' }}</span>
                                    @if(!empty($t->options['headerHtml']))
                                        <span class="sn-tag ic-primary"><i class="fa-solid fa-heading"></i> Header</span>
                                    @endif
                                    @if(!empty($t->options['footerHtml']))
                                        <span class="sn-tag ic-primary"><i class="fa-solid fa-shoe-prints"></i> Footer</span>
                                    @endif
                                    @if(!empty($t->options['watermarkHtml']))
                                        <span class="sn-tag ic-warning"><i class="fa-solid fa-stamp"></i> Watermark</span>
                                    @endif
                                    @if(!empty($t->options['contentHtml']))
                                        <span class="sn-tag ic-warning"><i class="fa-solid fa-code"></i> Override</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button class="sn-btn sn-btn-icon btn-preview-row" data-id="{{ $t->id }}" title="Preview PDF">
                                            <i class="fa-solid fa-eye ic-primary"></i>
                                        </button>
                                        <button class="sn-btn sn-btn-icon btn-edit-row" data-id="{{ $t->id }}" title="Edit">
                                            <i class="fa-solid fa-pen-to-square ic-primary"></i>
                                        </button>
                                        <button class="sn-btn sn-btn-icon sn-btn-danger btn-delete-row" data-id="{{ $t->id }}" title="Delete">
                                            <i class="fa-solid fa-trash ic-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="no-data-row">
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-file-pdf fa-2x mb-2 d-block opacity-50"></i>
                                    No custom view templates configured yet.<br>
                                    Click <strong>+</strong> above to create one.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="search-no-results-row" style="display: none;">
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block opacity-40"></i>
                                <span>No templates found matching your search.</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ════════════════════════════════════ FULLSCREEN CREATE / EDIT MODAL ════════════════════════════════════ -->
    <div class="modal fade modal-fullscreen" id="templateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">New Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-body-inner">
                        <form id="templateForm">
                            <input type="hidden" id="template_id" value="">

                            <!-- 1. Target -->
                            <div class="sn-form-card">
                                <div class="sn-form-card__title">
                                    <i class="fa-solid fa-tag ic-primary"></i> Target View & Locale
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-8">
                                        <label class="form-label" for="select_view">Blade View</label>
                                        <select id="select_view" class="form-select sn-select2">
                                            <option value="">-- Select or type view name --</option>
                                            @foreach($availableViews as $v)
                                                <option value="{{ $v }}">{{ $v === '*' ? '* (All Views / Global Fallback)' : $v }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="select_locale">Locale</label>
                                        <select id="select_locale" class="form-select sn-select2">
                                            @foreach($supportedLocales as $key => $loc)
                                                @php
                                                    $code = is_numeric($key) ? (string) $loc : (string) $key;
                                                    $label = is_numeric($key)
                                                        ? ($loc === '*' ? '* (All / Fallback)' : strtoupper((string) $loc))
                                                        : ($code === '*' ? '* (All / Fallback)' : ($loc === $code ? strtoupper($code) : "{$loc} (" . strtoupper($code) . ")"));
                                                @endphp
                                                <option value="{{ $code }}" {{ $code === '*' ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Paper & Margins -->
                            <div class="sn-form-card">
                                <div class="sn-form-card__title">
                                    <i class="fa-solid fa-file-lines ic-primary"></i> Page Dimensions & Margins
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-3">
                                        <label class="form-label" for="opt_paper">Paper Size</label>
                                        <select id="opt_paper" class="form-select sn-select2">
                                            @foreach($paperSizes as $p)
                                                <option value="{{ $p }}" {{ $p === 'A4' ? 'selected' : '' }}>{{ $p }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label" for="opt_orientation">Orientation</label>
                                        <select id="opt_orientation" class="form-select sn-select2">
                                            <option value="portrait" selected>Portrait</option>
                                            <option value="landscape">Landscape</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" for="opt_page_width">Custom Width</label>
                                        <input type="text" id="opt_page_width" class="form-control" placeholder="e.g. 210mm, 8.5in">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" for="opt_page_height">Custom Height</label>
                                        <input type="text" id="opt_page_height" class="form-control" placeholder="e.g. 297mm, 11in">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="opt_scale">Scale (0.1 - 2.0)</label>
                                        <input type="number" step="0.05" min="0.1" max="2" id="opt_scale" class="form-control" placeholder="1.0">
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" for="opt_margin_top">Top Margin</label>
                                        <input type="text" id="opt_margin_top" class="form-control" placeholder="5mm">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" for="opt_margin_bottom">Bottom Margin</label>
                                        <input type="text" id="opt_margin_bottom" class="form-control" placeholder="5mm">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" for="opt_margin_left">Left Margin</label>
                                        <input type="text" id="opt_margin_left" class="form-control" placeholder="5mm">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" for="opt_margin_right">Right Margin</label>
                                        <input type="text" id="opt_margin_right" class="form-control" placeholder="5mm">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Header & Footer -->
                            <div class="sn-form-card">
                                <div class="sn-form-card__title">
                                    <i class="fa-solid fa-heading ic-primary"></i> Dynamic Header & Footer (HTML)
                                </div>
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0" for="opt_header_html">Header HTML Fragment</label>
                                        <small class="text-muted" style="font-size: 11px;">Placeholders: <code>{page}</code>, <code>{pages}</code></small>
                                    </div>
                                    <textarea id="opt_header_html" class="form-control mb-3" rows="3" placeholder="<div style='text-align: center;'>Invoice Header</div>"></textarea>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="opt_header_height">Height</label>
                                            <input type="text" id="opt_header_height" class="form-control" placeholder="20mm">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="opt_header_spacing">Spacing</label>
                                            <input type="text" id="opt_header_spacing" class="form-control" placeholder="4mm">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="opt_header_offset">Offset</label>
                                            <input type="text" id="opt_header_offset" class="form-control" placeholder="0mm">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0" for="opt_footer_html">Footer HTML Fragment</label>
                                        <small class="text-muted" style="font-size: 11px;">Placeholders: <code>{page} of {pages}</code></small>
                                    </div>
                                    <textarea id="opt_footer_html" class="form-control mb-3" rows="3" placeholder="<div style='text-align: right; font-size: 10px;'>Page {page} of {pages}</div>"></textarea>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="opt_footer_height">Height</label>
                                            <input type="text" id="opt_footer_height" class="form-control" placeholder="15mm">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="opt_footer_spacing">Spacing</label>
                                            <input type="text" id="opt_footer_spacing" class="form-control" placeholder="4mm">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label" for="opt_footer_offset">Offset</label>
                                            <input type="text" id="opt_footer_offset" class="form-control" placeholder="0mm">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Watermark & Override -->
                            <div class="sn-form-card">
                                <div class="sn-form-card__title">
                                    <i class="fa-solid fa-stamp ic-warning"></i> Watermark & Content Override
                                </div>
                                <div class="row g-3 mb-3 align-items-end">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="opt_watermark_html">Watermark HTML / Text</label>
                                        <input type="text" id="opt_watermark_html" class="form-control" placeholder="<h1 style='color:red;'>PAID</h1>">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" for="opt_watermark_opacity">Opacity (0.0 - 1.0)</label>
                                        <input type="number" step="0.05" min="0" max="1" id="opt_watermark_opacity" class="form-control" placeholder="0.15">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label" for="opt_watermark_behind">Watermark Layer</label>
                                        <select id="opt_watermark_behind" class="form-select sn-select2">
                                            <option value="">Default (Behind)</option>
                                            <option value="1">Behind Content</option>
                                            <option value="0">Above Content</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label" for="opt_content_html">Content HTML Override (Optional)</label>
                                    <textarea id="opt_content_html" class="form-control" rows="4" placeholder="Optional raw HTML to replace entire view content"></textarea>
                                </div>
                            </div>

                            <!-- 5. Metadata & Page Offsets -->
                            <div class="sn-form-card">
                                <div class="sn-form-card__title">
                                    <i class="fa-solid fa-circle-info ic-primary"></i> Document Metadata & Page Offsets
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="opt_title">Document Title</label>
                                        <input type="text" id="opt_title" class="form-control" placeholder="e.g. Sales Invoice #1002">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="opt_author">Author</label>
                                        <input type="text" id="opt_author" class="form-control" placeholder="e.g. Accounting Dept">
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="opt_subject">Subject</label>
                                        <input type="text" id="opt_subject" class="form-control" placeholder="e.g. Monthly Statement">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="opt_keywords">Keywords</label>
                                        <input type="text" id="opt_keywords" class="form-control" placeholder="e.g. invoice, report, finance">
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="opt_page_offset">Page Number Offset</label>
                                        <input type="number" id="opt_page_offset" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="opt_total_offset">Total Pages Offset</label>
                                        <input type="number" id="opt_total_offset" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="opt_base_url">Base URL</label>
                                        <input type="text" id="opt_base_url" class="form-control" placeholder="e.g. https://example.com">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="sn-btn" id="btn-modal-preview">
                        <i class="fa-solid fa-play ic-success"></i>
                        <span>Preview</span>
                    </button>
                    <button type="button" class="sn-btn sn-btn-primary" id="btn-modal-save">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>

    <script>
        (function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            var BASE_URL = '{{ route("pdf.templates.index") }}';
            var PREVIEW_URL = '{{ route("pdf.templates.preview") }}';

            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
            });

            // Initialize all dropdowns as Select2 inside Modal
            $('#select_view').select2({
                dropdownParent: $('#templateModal'),
                tags: true,
                placeholder: 'Choose or type view name...',
                allowClear: true
            });

            $('#select_locale').select2({
                dropdownParent: $('#templateModal'),
                minimumResultsForSearch: 8,
                allowClear: false
            });

            $('#opt_paper').select2({
                dropdownParent: $('#templateModal'),
                minimumResultsForSearch: 8,
                allowClear: false
            });

            $('#opt_orientation').select2({
                dropdownParent: $('#templateModal'),
                minimumResultsForSearch: Infinity,
                allowClear: false
            });

            // Theme Management
            var currentTheme = localStorage.getItem('sn-pdf-theme') || 'dark';
            applyTheme(currentTheme);

            $('#btn-toggle-theme').on('click', function() {
                var newTheme = $('html').attr('data-bs-theme') === 'light' ? 'dark' : 'light';
                applyTheme(newTheme);
            });

            function applyTheme(theme) {
                $('html').attr('data-bs-theme', theme);
                localStorage.setItem('sn-pdf-theme', theme);
                if (theme === 'light') {
                    $('#theme-icon').removeClass('fa-moon').addClass('fa-sun');
                } else {
                    $('#theme-icon').removeClass('fa-sun').addClass('fa-moon');
                }
            }

            // Quick Filter in Table
            $('#table-search').on('input', function() {
                var q = $(this).val().toLowerCase().trim();
                var matched = 0;
                var total = $('.template-row').length;

                $('.template-row').each(function() {
                    var v = String($(this).data('view') || '').toLowerCase();
                    var l = String($(this).data('locale') || '').toLowerCase();
                    if (v.indexOf(q) !== -1 || l.indexOf(q) !== -1) {
                        $(this).show();
                        matched++;
                    } else {
                        $(this).hide();
                    }
                });

                if (total > 0) {
                    if (matched === 0) {
                        $('#search-no-results-row').show();
                    } else {
                        $('#search-no-results-row').hide();
                    }
                }
            });

            // Collect options
            function collectOptions() {
                var opts = {};
                function add(k, val) {
                    if (val !== undefined && val !== null && String(val).trim() !== '') opts[k] = val;
                }

                add('paper', $('#opt_paper').val());
                add('pageWidth', $('#opt_page_width').val());
                add('pageHeight', $('#opt_page_height').val());
                add('orientation', $('#opt_orientation').val());
                if ($('#opt_scale').val()) opts['scale'] = parseFloat($('#opt_scale').val());
                add('marginTop', $('#opt_margin_top').val());
                add('marginBottom', $('#opt_margin_bottom').val());
                add('marginLeft', $('#opt_margin_left').val());
                add('marginRight', $('#opt_margin_right').val());

                add('headerHtml', $('#opt_header_html').val());
                add('headerHeight', $('#opt_header_height').val());
                add('headerSpacing', $('#opt_header_spacing').val());
                add('headerOffset', $('#opt_header_offset').val());

                add('footerHtml', $('#opt_footer_html').val());
                add('footerHeight', $('#opt_footer_height').val());
                add('footerSpacing', $('#opt_footer_spacing').val());
                add('footerOffset', $('#opt_footer_offset').val());

                add('watermarkHtml', $('#opt_watermark_html').val());
                if ($('#opt_watermark_opacity').val()) opts['watermarkOpacity'] = parseFloat($('#opt_watermark_opacity').val());
                if ($('#opt_watermark_behind').val() !== '') opts['watermarkBehind'] = $('#opt_watermark_behind').val() === '1';

                add('title', $('#opt_title').val());
                add('author', $('#opt_author').val());
                add('subject', $('#opt_subject').val());
                add('keywords', $('#opt_keywords').val());
                add('baseUrl', $('#opt_base_url').val());
                if ($('#opt_page_offset').val()) opts['pageOffset'] = parseInt($('#opt_page_offset').val(), 10);
                if ($('#opt_total_offset').val()) opts['totalOffset'] = parseInt($('#opt_total_offset').val(), 10);

                add('contentHtml', $('#opt_content_html').val());

                return opts;
            }

            // Fill Form
            function populateModal(t) {
                if (t) {
                    $('#template_id').val(t.id);
                    $('#modalTitle').text('Edit Template (#' + t.id + ')');
                    $('#select_view').val(t.view).trigger('change');
                    $('#select_locale').val(t.locale).trigger('change');

                    var opts = t.options || {};
                    $('#opt_paper').val(opts.paper || 'A4').trigger('change');
                    $('#opt_page_width').val(opts.pageWidth || '');
                    $('#opt_page_height').val(opts.pageHeight || '');
                    $('#opt_orientation').val(opts.orientation || 'portrait').trigger('change');
                    $('#opt_scale').val(opts.scale !== undefined ? opts.scale : '');
                    $('#opt_margin_top').val(opts.marginTop || '');
                    $('#opt_margin_bottom').val(opts.marginBottom || '');
                    $('#opt_margin_left').val(opts.marginLeft || '');
                    $('#opt_margin_right').val(opts.marginRight || '');

                    $('#opt_header_html').val(opts.headerHtml || '');
                    $('#opt_header_height').val(opts.headerHeight || '');
                    $('#opt_header_spacing').val(opts.headerSpacing || '');
                    $('#opt_header_offset').val(opts.headerOffset || '');

                    $('#opt_footer_html').val(opts.footerHtml || '');
                    $('#opt_footer_height').val(opts.footerHeight || '');
                    $('#opt_footer_spacing').val(opts.footerSpacing || '');
                    $('#opt_footer_offset').val(opts.footerOffset || '');

                    $('#opt_watermark_html').val(opts.watermarkHtml || '');
                    $('#opt_watermark_opacity').val(opts.watermarkOpacity !== undefined ? opts.watermarkOpacity : '');
                    var wb = opts.watermarkBehind !== undefined ? (opts.watermarkBehind ? '1' : '0') : '';
                    $('#opt_watermark_behind').val(wb).trigger('change');

                    $('#opt_title').val(opts.title || '');
                    $('#opt_author').val(opts.author || '');
                    $('#opt_subject').val(opts.subject || '');
                    $('#opt_keywords').val(opts.keywords || '');
                    $('#opt_base_url').val(opts.baseUrl || '');
                    $('#opt_page_offset').val(opts.pageOffset !== undefined ? opts.pageOffset : '');
                    $('#opt_total_offset').val(opts.totalOffset !== undefined ? opts.totalOffset : '');

                    $('#opt_content_html').val(opts.contentHtml || '');
                } else {
                    $('#template_id').val('');
                    $('#modalTitle').text('New Template');
                    $('#templateForm')[0].reset();
                    $('#select_view').val('').trigger('change');
                    $('#select_locale').val('*').trigger('change');
                    $('#opt_paper').val('A4').trigger('change');
                    $('#opt_page_width').val('');
                    $('#opt_page_height').val('');
                    $('#opt_orientation').val('portrait').trigger('change');
                    $('#opt_watermark_behind').val('').trigger('change');
                }
            }

            // Open Create Modal instantly
            $('#btn-create-template').on('click', function() {
                populateModal(null);
                $('#templateModal').modal('show');
            });

            // Edit row click
            $(document).on('click', '.btn-edit-row', function() {
                var id = $(this).data('id');
                var $btn = $(this);
                $btn.prop('disabled', true);

                $.getJSON(BASE_URL + '/' + id, function(res) {
                    $btn.prop('disabled', false);
                    if (res.success) {
                        populateModal(res.data);
                        $('#templateModal').modal('show');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false);
                });
            });

            // Save Template
            $('#btn-modal-save').on('click', function() {
                var id = $('#template_id').val();
                var view = $('#select_view').val();
                var locale = $('#select_locale').val();

                if (!view) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'View Required',
                        text: 'Please choose or type a Blade view name.',
                        customClass: { popup: 'sn-dialog' }
                    });
                    return;
                }

                var pageWidth = $('#opt_page_width').val();
                var pageHeight = $('#opt_page_height').val();

                if ((pageWidth && !pageHeight) || (!pageWidth && pageHeight)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Custom Dimensions',
                        text: 'Both width and height must be provided together.',
                        customClass: { popup: 'sn-dialog' }
                    });
                    return;
                }

                var options = collectOptions();
                var payload = {
                    view: view,
                    locale: locale,
                    options: options
                };

                var url = id ? (BASE_URL + '/' + id) : BASE_URL;
                var method = id ? 'PUT' : 'POST';

                var $saveBtn = $('#btn-modal-save');
                $saveBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: url,
                    type: method,
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(res) {
                        $('#templateModal').modal('hide');
                        $saveBtn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk"></i> Save');
                        location.reload();
                    },
                    error: function(xhr) {
                        $saveBtn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk"></i> Save');
                        var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save template.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: msg,
                            customClass: { popup: 'sn-dialog' }
                        });
                    }
                });
            });

            // Delete Template
            $(document).on('click', '.btn-delete-row', function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Delete Template?',
                    text: 'Are you sure you want to delete this template?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel',
                    customClass: { popup: 'sn-dialog' }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: BASE_URL + '/' + id,
                            type: 'DELETE',
                            success: function(res) {
                                location.reload();
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to delete template.',
                                    customClass: { popup: 'sn-dialog' }
                                });
                            }
                        });
                    }
                });
            });

            // Preview
            $(document).on('click', '.btn-preview-row', function() {
                var id = $(this).data('id');
                $.getJSON(BASE_URL + '/' + id, function(res) {
                    if (res.success) {
                        runPreview(res.data.view, res.data.options || {});
                    }
                });
            });

            $('#btn-modal-preview').on('click', function() {
                var pageWidth = $('#opt_page_width').val();
                var pageHeight = $('#opt_page_height').val();

                if ((pageWidth && !pageHeight) || (!pageWidth && pageHeight)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Custom Dimensions',
                        text: 'Both width and height must be provided together.',
                        customClass: { popup: 'sn-dialog' }
                    });
                    return;
                }

                var view = $('#select_view').val();
                var options = collectOptions();
                runPreview(view, options);
            });

            function runPreview(view, options) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = PREVIEW_URL;
                form.target = '_blank';

                var csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = CSRF_TOKEN;
                form.appendChild(csrfInput);

                var viewInput = document.createElement('input');
                viewInput.type = 'hidden';
                viewInput.name = 'view';
                viewInput.value = view || '';
                form.appendChild(viewInput);

                var optInput = document.createElement('input');
                optInput.type = 'hidden';
                optInput.name = 'options';
                optInput.value = JSON.stringify(options);
                form.appendChild(optInput);

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
        })();
    </script>
</body>
</html>
