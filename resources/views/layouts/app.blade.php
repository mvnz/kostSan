<!doctype html>
<html
  lang="id"
  class="layout-menu-fixed layout-compact"
  data-assets-path="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/"
  data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>{{ ($pageTitle ?? 'Dashboard') . ' - ' . $appBrandName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Iconify / Boxicons Icons (via Sneat CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/fonts/iconify-icons.css" />

    <!-- Sneat Core CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/css/demo.css" />

    <!-- Perfect Scrollbar -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- jQuery DataTables + Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" />
    <style>
        /* ═══ DataTables — Sneat style overrides ═══ */

        /* card-datatable: no extra padding */
        .card-datatable { padding: 0; overflow: visible !important; }

        /* Top / bottom bar layout */
        div.dataTables_wrapper div.dataTables_length,
        div.dataTables_wrapper div.dataTables_filter {
            padding: .75rem 1.5rem;
        }
        div.dataTables_wrapper div.dataTables_length { border-bottom: 0; }
        div.dataTables_wrapper div.dataTables_filter { border-bottom: 0; }

        div.dataTables_wrapper div.dataTables_info,
        div.dataTables_wrapper div.dataTables_paginate {
            padding: .75rem 1.5rem;
        }

        /* Top row: flex between length (left) and filter (right) */
        div.dataTables_wrapper > div.row:first-child {
            border-bottom: 1px solid #dbdfe9;
            margin: 0;
        }
        div.dataTables_wrapper > div.row:last-child {
            border-top: 1px solid #dbdfe9;
            margin: 0;
        }

        /* Length select ("Show X entries") */
        div.dataTables_wrapper div.dataTables_length label {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .875rem;
            color: #566a7f;
            margin: 0;
        }
        div.dataTables_wrapper div.dataTables_length select.form-select {
            width: auto;
            min-width: 70px;
            font-size: .875rem;
            color: #566a7f;
            border-color: #d9dee3;
            padding: .25rem 2rem .25rem .75rem;
            height: calc(1.5em + .5rem + 2px);
        }
        div.dataTables_wrapper div.dataTables_length select.form-select:focus {
            border-color: #696cff;
            box-shadow: 0 0 0 .15rem rgba(105,108,255,.2);
        }

        /* Search filter ("Search: [input]") */
        div.dataTables_wrapper div.dataTables_filter label {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: .875rem;
            color: #566a7f;
            margin: 0;
            justify-content: flex-end;
        }
        div.dataTables_wrapper div.dataTables_filter input.form-control {
            width: 200px;
            font-size: .875rem;
            color: #566a7f;
            border-color: #d9dee3;
            height: calc(1.5em + .5rem + 2px);
            padding: .25rem .75rem;
            margin-left: 0;
        }
        div.dataTables_wrapper div.dataTables_filter input.form-control:focus {
            border-color: #696cff;
            box-shadow: 0 0 0 .15rem rgba(105,108,255,.2);
        }

        /* Table headers */
        table.dataTable thead > tr > th {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #566a7f;
            padding: .75rem 1.5rem;
            border-bottom: 1px solid #dbdfe9 !important;
            white-space: nowrap;
            background: transparent;
        }
        table.dataTable thead > tr > th.sorting,
        table.dataTable thead > tr > th.sorting_asc,
        table.dataTable thead > tr > th.sorting_desc {
            padding-right: 2rem;
        }

        /* Table body */
        table.dataTable tbody > tr > td {
            padding: .875rem 1.5rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f2f8;
            font-size: .875rem;
            color: #566a7f;
        }
        table.dataTable tbody > tr:last-child > td { border-bottom: none; }
        table.dataTable tbody > tr:hover > td { background-color: #f9f9ff; }
        table.dataTable { margin-top: 0 !important; margin-bottom: 0 !important; }

        /* Info text */
        div.dataTables_wrapper div.dataTables_info {
            font-size: .8125rem;
            color: #697a8d;
            padding-top: .75rem;
        }

        /* Pagination */
        div.dataTables_wrapper div.dataTables_paginate ul.pagination {
            margin: 0;
            gap: .2rem;
        }
        div.dataTables_wrapper div.dataTables_paginate .page-link {
            border-color: #d9dee3;
            color: #566a7f;
            font-size: .875rem;
            padding: .35rem .65rem;
            border-radius: .375rem !important;
            transition: background .15s, border-color .15s, color .15s;
        }
        div.dataTables_wrapper div.dataTables_paginate .page-item.active .page-link {
            background-color: #696cff;
            border-color: #696cff;
            color: #fff;
        }
        div.dataTables_wrapper div.dataTables_paginate .page-item.disabled .page-link {
            opacity: .5;
        }

        /* Unified action buttons across listing pages */
        .btn-action-menu {
            border: 1px solid #d7dff0;
            background: #f8faff;
            color: #5d6b82;
            border-radius: 999px;
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .18s ease;
        }
        .btn-action-menu:hover,
        .btn-action-menu.show {
            background: #eef2ff;
            border-color: #b7c4ff;
            color: #4d5ad1;
            box-shadow: 0 6px 14px rgba(77, 90, 209, .2);
        }

        .btn-edit-fancy {
            border: 1px solid #f5b563;
            background: linear-gradient(135deg, #fff7eb 0%, #ffeed8 100%);
            color: #b45309;
            font-weight: 600;
            border-radius: .55rem;
            padding: .35rem .8rem;
            box-shadow: 0 2px 10px rgba(180, 83, 9, .14);
            transition: all .18s ease;
        }
        .btn-edit-fancy:hover {
            color: #fff;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-color: #d97706;
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(217, 119, 6, .28);
        }

        .action-dropdown-menu {
            border: 1px solid #e2e8f5;
            border-radius: .75rem;
            box-shadow: 0 12px 30px rgba(16, 32, 67, .12);
            padding: .4rem;
            min-width: 220px;
        }
        .action-dropdown-menu .dropdown-item {
            border-radius: .55rem;
            display: flex;
            align-items: center;
            gap: .45rem;
            padding: .5rem .65rem;
            transition: background-color .15s ease, color .15s ease;
        }
        .action-dropdown-menu .dropdown-item:hover {
            background: #f4f7ff;
        }
        .action-dropdown-menu .dropdown-divider {
            margin: .4rem 0;
            border-top-color: #e7ecf6;
        }

        .action-item-detail i { color: #4f46e5; }
        .action-item-payment i { color: #0891b2; }
        .action-item-link i { color: #2563eb; }
        .action-item-archive i { color: #64748b; }
        .action-item-delete,
        .action-item-delete i { color: #dc2626; }

        .btn-action-detail,
        .btn-action-payment,
        .btn-action-link {
            border-radius: .5rem;
            padding: .34rem .64rem;
            font-size: .79rem;
            line-height: 1.1;
            font-weight: 600;
            transition: all .16s ease;
        }

        .btn-action-detail {
            border: 1px solid #cfd7f8;
            background: #f3f6ff;
            color: #374bb8;
        }
        .btn-action-detail:hover {
            background: #e8eeff;
            color: #263b9f;
            border-color: #afbef5;
        }

        .btn-action-payment {
            border: 1px solid #b8e9cb;
            background: #edfcf2;
            color: #157f45;
        }
        .btn-action-payment:hover {
            background: #def8e8;
            color: #116738;
            border-color: #8fdbaf;
        }

        .btn-action-link {
            border: 1px solid #b6e4ec;
            background: #ecfbfe;
            color: #0f6f82;
        }
        .btn-action-link:hover {
            background: #ddf6fb;
            color: #0c5a6a;
            border-color: #8dd4e1;
        }
        .btn-action-danger {
            border: 1px solid #f3c9c9;
            background: #fff5f5;
            color: #cf2f2f;
            border-radius: .5rem;
            padding: .34rem .64rem;
            font-size: .79rem;
            line-height: 1.1;
            font-weight: 600;
            transition: all .16s ease;
        }
        .btn-action-danger:hover {
            background: #ffe9e9;
            color: #b91c1c;
            border-color: #efb4b4;
        }

        /* Global page header style */
        .container-p-y > .page-head,
        .container-p-y > h4.py-3.mb-4 {
            background: linear-gradient(135deg, #143364 0%, #1f5ca8 45%, #3b82f6 100%);
            color: #fff;
            border-radius: 1rem;
            padding: 1.3rem 1.5rem;
            margin-bottom: 1.25rem !important;
            position: relative;
            overflow: hidden;
        }

        .container-p-y > .page-head::after,
        .container-p-y > h4.py-3.mb-4::after {
            content: '';
            position: absolute;
            inset: auto -46px -86px auto;
            width: 230px;
            height: 230px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            pointer-events: none;
        }

        .container-p-y > .page-head h2 {
            color: #ffd370;
            margin: 0;
            font-weight: 700;
            line-height: 1.3;
        }

        .container-p-y > h4.py-3.mb-4 {
            color: #fff;
            margin: 0;
            font-weight: 700;
            line-height: 1.3;
            font-size: 1.7rem;
        }

        .container-p-y > h4.py-3.mb-4 > .text-muted.fw-light {
            display: block;
            margin-bottom: .4rem;
            font-size: .92rem;
            font-weight: 500 !important;
            line-height: 1.2;
        }

        .container-p-y > .page-head p {
            margin: .45rem 0 0;
            color: rgba(255, 255, 255, .9);
            font-size: .93rem;
        }

        .container-p-y > h4.py-3.mb-4 .text-muted,
        .container-p-y > h4.py-3.mb-4 .text-muted.fw-light,
        .container-p-y > h4.py-3.mb-4 .text-muted.fw-light a,
        .container-p-y > h4.py-3.mb-4 a.text-muted {
            color: rgba(255, 255, 255, .85) !important;
        }

        /* Icon box inside page-head */
        .page-head-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, .18);
            border-radius: .9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.55rem;
            color: #fff;
            flex-shrink: 0;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,.22);
        }

        .page-head h2 { font-size: 1.45rem; }
        .page-head p  { font-size: .93rem; }

        /* Form section dividers */
        .form-section {
            border: 1px solid #e8edf6;
            border-radius: .9rem;
            padding: 1.1rem 1.15rem;
            background: #fff;
            margin-bottom: 1.1rem;
        }

        .form-section-title {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .55px;
            color: #8596ab;
            padding-bottom: .6rem;
            margin-bottom: .9rem;
            border-bottom: 1px solid #edf2fb;
            display: flex;
            align-items: center;
            gap: .45rem;
        }

        .form-section-title i {
            font-size: 1rem;
            color: #6e87d4;
        }

        /* Label polish */
        .form-label {
            font-size: .84rem;
            font-weight: 600;
            color: #516079;
            margin-bottom: .35rem;
        }

        /* Nav pills (settings sub-menu) */
        .nav-pills .nav-link {
            border-radius: .6rem;
            font-weight: 500;
            font-size: .875rem;
            padding: .48rem .9rem;
            color: #566a7f;
            transition: all .18s ease;
        }

        .nav-pills .nav-link:hover {
            background: #f0f3ff;
            color: #4467cc;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #295fcb 0%, #3b82f6 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(47, 103, 212, .25);
        }

        /* Alert polish */
        .alert {
            border-radius: .85rem;
            border: none;
            box-shadow: 0 4px 14px rgba(0,0,0,.07);
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%);
            color: #065f46;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fff5f5 100%);
            color: #7f1d1d;
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%);
            color: #1e3a6e;
        }

        /* Stat mini cards (inline) */
        .stat-mini-card {
            border: 1px solid #e4ecf8;
            border-radius: .85rem;
            padding: .9rem 1rem;
            background: #fff;
            box-shadow: 0 4px 14px rgba(16, 32, 75, .045);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .stat-mini-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 32, 75, .08);
        }

        /* Global UI polish for all pages */
        .container-p-y > .card,
        .container-p-y > form > .card,
        .container-p-y > .row > div > .card {
            border: 1px solid #e4ebf7;
            border-radius: 1rem;
            box-shadow: 0 10px 24px rgba(16, 34, 75, .05);
            overflow: hidden;
        }

        .container-p-y > .card .card-header,
        .container-p-y > form > .card .card-header,
        .container-p-y > .row > div > .card .card-header {
            background: linear-gradient(180deg, #fbfdff 0%, #f4f8ff 100%);
            border-bottom: 1px solid #e5edf9;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .container-p-y .btn.btn-primary {
            background: linear-gradient(135deg, #295fcb 0%, #3b82f6 100%);
            border-color: #2f67d4;
            box-shadow: 0 8px 16px rgba(47, 103, 212, .2);
        }

        .container-p-y .btn.btn-primary:hover,
        .container-p-y .btn.btn-primary:focus {
            background: linear-gradient(135deg, #1f52b7 0%, #2f70df 100%);
            border-color: #265fc8;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(39, 96, 197, .26);
        }

        .container-p-y .btn {
            border-radius: .65rem;
        }

        .container-p-y .badge.rounded-pill {
            font-weight: 600;
            letter-spacing: .2px;
            padding: .45rem .62rem;
        }

        .container-p-y > .page-head,
        .container-p-y > h4.py-3.mb-4,
        .container-p-y > .card {
            animation: mkostFadeUp .3s ease-out;
        }

        @keyframes mkostFadeUp {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 767.98px) {
            .container-p-y > .page-head,
            .container-p-y > h4.py-3.mb-4 {
                padding: 1rem 1.1rem;
                border-radius: .85rem;
            }

            .container-p-y > .page-head > h2,
            .container-p-y > h4.py-3.mb-4 {
                font-size: 1.2rem;
            }

            .container-p-y > h4.py-3.mb-4 > .text-muted.fw-light {
                font-size: .8rem;
                margin-bottom: .3rem;
            }

            .container-p-y > .page-head > p {
                font-size: .86rem;
            }

            .container-p-y > .card,
            .container-p-y > form > .card,
            .container-p-y > .row > div > .card {
                border-radius: .85rem;
            }

            .card-datatable.table-responsive {
                overflow-x: hidden;
            }

            .card .card-header {
                align-items: stretch !important;
            }

            .card .card-header .d-flex.gap-2 {
                width: 100%;
                flex-wrap: wrap;
            }

            .card .card-header .d-flex.gap-2 > a.btn,
            .card .card-header .d-flex.gap-2 > button.btn,
            .card .card-header .d-flex.gap-2 > form {
                flex: 1 1 calc(50% - .5rem);
                min-width: 0;
            }

            .card .card-header .d-flex.gap-2 > form > .btn {
                width: 100%;
            }

            .card .card-header .btn {
                white-space: normal;
                line-height: 1.15;
                padding: .55rem .65rem;
            }

            div.dataTables_wrapper > div.row:first-child,
            div.dataTables_wrapper > div.row:last-child {
                margin: 0;
                row-gap: .4rem;
            }

            div.dataTables_wrapper div.dataTables_length,
            div.dataTables_wrapper div.dataTables_filter,
            div.dataTables_wrapper div.dataTables_info,
            div.dataTables_wrapper div.dataTables_paginate {
                padding: .55rem .85rem;
            }

            div.dataTables_wrapper div.dataTables_length label,
            div.dataTables_wrapper div.dataTables_filter label {
                width: 100%;
                justify-content: flex-start;
                gap: .45rem;
                flex-wrap: wrap;
            }

            div.dataTables_wrapper div.dataTables_length select.form-select {
                min-width: 86px;
            }

            div.dataTables_wrapper div.dataTables_filter input.form-control {
                width: 100%;
                min-width: 0;
                margin-top: .1rem;
            }

            div.dataTables_wrapper div.dataTables_info {
                text-align: center;
                font-size: .78rem;
                padding-bottom: .25rem;
            }

            div.dataTables_wrapper div.dataTables_paginate {
                display: flex;
                justify-content: center;
                padding-top: .2rem;
            }

            div.dataTables_wrapper div.dataTables_paginate .page-link {
                min-width: 34px;
                text-align: center;
                padding: .35rem .55rem;
            }

            table.dataTable thead > tr > th,
            table.dataTable tbody > tr > td {
                padding: .65rem .75rem;
                font-size: .8rem;
            }

            .btn-action-detail,
            .btn-edit-fancy,
            .btn-action-danger,
            .btn-action-payment,
            .btn-action-link {
                width: 30px;
                height: 30px;
                padding: 0 !important;
                font-size: 0 !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: .45rem;
            }

            .btn-action-detail i,
            .btn-edit-fancy i,
            .btn-action-danger i,
            .btn-action-payment i,
            .btn-action-link i {
                margin: 0 !important;
                font-size: .95rem;
            }
        }

        /* Global form control consistency */
        .form-control,
        .form-select {
            min-height: calc(1.5em + 0.9rem + 2px);
            border-radius: .5rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #8ca9ea;
            box-shadow: 0 0 0 .15rem rgba(78, 120, 216, .18);
        }

        /* Bootstrap dropdown replacement for native selects */
        .bs-select-dropdown {
            position: relative;
            width: 100%;
        }

        .bs-select-dropdown .bs-select-toggle {
            border: 1px solid #d9dee3;
            border-radius: .5rem;
            background: #fff;
            color: #566a7f;
            min-height: calc(1.5em + 0.9rem + 2px);
            padding: .45rem .85rem;
        }

        .bs-select-dropdown .bs-select-toggle:hover {
            border-color: #b8c6d8;
            background: #fff;
            color: #566a7f;
        }

        .bs-select-dropdown .bs-select-toggle:focus,
        .bs-select-dropdown .bs-select-toggle.show {
            border-color: #8ca9ea;
            box-shadow: 0 0 0 .15rem rgba(78, 120, 216, .18);
            background: #fff;
            color: #566a7f;
        }

        .bs-select-dropdown .bs-select-toggle.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 .15rem rgba(220, 53, 69, .15);
        }

        .bs-select-dropdown .dropdown-menu {
            width: 100%;
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid #d9dee3;
            border-radius: .6rem;
            box-shadow: 0 12px 26px rgba(16, 32, 67, .12);
            padding: .35rem;
        }

        .bs-select-dropdown .bs-select-search-wrap {
            position: sticky;
            top: -.35rem;
            z-index: 2;
            background: #fff;
            padding-bottom: .35rem;
        }

        .bs-select-dropdown .bs-select-search {
            min-height: calc(1.5em + 0.55rem + 2px);
            font-size: .82rem;
        }

        .bs-select-dropdown .dropdown-item {
            border-radius: .45rem;
            padding: .45rem .65rem;
            font-size: .875rem;
        }

        .bs-select-dropdown .dropdown-item.active,
        .bs-select-dropdown .dropdown-item:active {
            background: #696cff;
            color: #fff;
        }

        .bs-select-native {
            position: absolute !important;
            inset: auto auto auto auto !important;
            width: 1px !important;
            height: 1px !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
    </style>

    <!-- Template Helpers & Config (must be in <head>) -->
    <script src="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/js/helpers.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <!-- ─── SIDEBAR MENU ─── -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <!-- Brand -->
                <div class="app-brand demo">
                    <a href="{{ route('dashboard') }}" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <span class="text-primary">
                                <svg width="25" viewBox="0 0 25 42" version="1.1" xmlns="http://www.w3.org/2000/svg">
                                    <text x="0" y="32" font-size="32" fill="currentColor">🏠</text>
                                </svg>
                            </span>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bold ms-2">{{ $appBrandName }}</span>
                    </a>
                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                        <i class="bx bx-chevron-left d-block d-xl-none align-middle"></i>
                    </a>
                </div>

                <div class="menu-divider mt-0"></div>
                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">

                    <!-- Dashboard -->
                    @canMenu('dashboard', 'view')
                    <li class="menu-item {{ request()->routeIs('dashboard') ? 'active open' : '' }}">
                        <a href="{{ route('dashboard') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-home-smile"></i>
                            <div class="text-truncate">Dashboard</div>
                        </a>
                    </li>
                    @endCanMenu

                    <!-- Manajemen Section -->
                    @php
                        $showManajemen = auth()->user()?->hasMenuPermission('manajemen_sewa.sewa_kamar')
                                      || auth()->user()?->hasMenuPermission('manajemen_sewa.data_sewa');
                    @endphp
                    @if($showManajemen)
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Manajemen</span>
                    </li>

                    <li class="menu-item {{ request()->routeIs('kamars.sewa', 'sewas.*', 'pembayarans.*') ? 'open active' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-home-circle"></i>
                            <div class="text-truncate">Manajemen Sewa</div>
                        </a>
                        <ul class="menu-sub">
                            @canMenu('manajemen_sewa.sewa_kamar', 'view')
                            <li class="menu-item {{ request()->routeIs('kamars.sewa') ? 'active' : '' }}">
                                <a href="{{ route('kamars.sewa') }}" class="menu-link">
                                    <div class="text-truncate">Sewa Kamar</div>
                                </a>
                            </li>
                            @endCanMenu
                            @canMenu('manajemen_sewa.data_sewa', 'view')
                            <li class="menu-item {{ request()->routeIs('sewas.*') ? 'active' : '' }}">
                                <a href="{{ route('sewas.index') }}" class="menu-link">
                                    <div class="text-truncate">Data Sewa</div>
                                </a>
                            </li>
                            @endCanMenu
                            @canMenu('manajemen_sewa.data_sewa', 'create')
                            <li class="menu-item {{ request()->routeIs('pembayarans.bulk-billing*') ? 'active' : '' }}">
                                <a href="{{ route('pembayarans.bulk-billing') }}" class="menu-link">
                                    <div class="text-truncate">Bulk Billing</div>
                                </a>
                            </li>
                            @endCanMenu
                        </ul>
                    </li>
                    @endif

                    <!-- Keuangan Section -->
                    @php
                        $showKeuangan = auth()->user()?->hasMenuPermission('keuangan.data_keuangan')
                                     || auth()->user()?->hasMenuPermission('keuangan.laporan_keuangan')
                                     || auth()->user()?->hasMenuPermission('keuangan.laporan_hunian')
                                     || auth()->user()?->hasMenuPermission('keuangan.invoice');
                    @endphp
                    @if($showKeuangan)
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Keuangan</span>
                    </li>

                    <li class="menu-item {{ request()->routeIs('keuangans.*', 'laporan-keuangan.*', 'laporan-hunian.*', 'invoices.*') ? 'open active' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-wallet"></i>
                            <div class="text-truncate">Keuangan</div>
                        </a>
                        <ul class="menu-sub">
                            @canMenu('keuangan.data_keuangan', 'view')
                            <li class="menu-item {{ request()->routeIs('keuangans.*') ? 'active' : '' }}">
                                <a href="{{ route('keuangans.index') }}" class="menu-link">
                                    <div class="text-truncate">Data Keuangan</div>
                                </a>
                            </li>
                            @endCanMenu
                            @canMenu('keuangan.laporan_keuangan', 'view')
                            <li class="menu-item {{ request()->routeIs('laporan-keuangan.*') ? 'active' : '' }}">
                                <a href="{{ route('laporan-keuangan.index') }}" class="menu-link">
                                    <div class="text-truncate">Laporan Keuangan</div>
                                </a>
                            </li>
                            @endCanMenu
                            @canMenu('keuangan.laporan_hunian', 'view')
                            <li class="menu-item {{ request()->routeIs('laporan-hunian.*') ? 'active' : '' }}">
                                <a href="{{ route('laporan-hunian.index') }}" class="menu-link">
                                    <div class="text-truncate">Laporan Hunian</div>
                                </a>
                            </li>
                            @endCanMenu
                            @canMenu('keuangan.invoice', 'view')
                            <li class="menu-item {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                                <a href="{{ route('invoices.index') }}" class="menu-link">
                                    <div class="text-truncate">Invoice</div>
                                </a>
                            </li>
                            @endCanMenu
                        </ul>
                    </li>
                    @endif

                    <!-- Master Data Section -->
                    @php
                        $showMasterData = auth()->user()?->hasMenuPermission('master_data.data_penghuni')
                                       || auth()->user()?->hasMenuPermission('master_data.data_kamar')
                                       || auth()->user()?->hasMenuPermission('master_data.data_lantai');
                    @endphp
                    @if($showMasterData)
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Master Data</span>
                    </li>

                    @canMenu('master_data.data_penghuni', 'view')
                    <li class="menu-item {{ request()->routeIs('penghunis.*') ? 'active' : '' }}">
                        <a href="{{ route('penghunis.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user"></i>
                            <div class="text-truncate">Penghuni</div>
                        </a>
                    </li>
                    @endCanMenu

                    @canMenu('master_data.data_kamar', 'view')
                    <li class="menu-item {{ request()->routeIs('kamars.create', 'kamars.edit', 'kamars.show', 'kamars.index') ? 'active' : '' }}">
                        <a href="{{ route('kamars.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-door-open"></i>
                            <div class="text-truncate">Kamar</div>
                        </a>
                    </li>
                    @endCanMenu

                    @canMenu('master_data.data_lantai', 'view')
                    <li class="menu-item {{ request()->routeIs('kamar-floors.*') ? 'active' : '' }}">
                        <a href="{{ route('kamar-floors.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-building-house"></i>
                            <div class="text-truncate">Data Lantai</div>
                        </a>
                    </li>
                    @endCanMenu
                    @endif

                    <!-- Sistem Section -->
                    @php
                        $showPengaturan = auth()->user()?->hasMenuPermission('pengaturan.profil_kost')
                                       || auth()->user()?->hasMenuPermission('pengaturan.tipe_kamar')
                                       || auth()->user()?->hasMenuPermission('pengaturan.notifikasi_wa');
                        $showAkses = auth()->user()?->hasMenuPermission('manajemen_akses.role')
                                  || auth()->user()?->hasMenuPermission('manajemen_akses.user');
                    @endphp
                    @if($showPengaturan || $showAkses)
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Sistem</span>
                    </li>
                    @endif

                    @if($showPengaturan)
                    <li class="menu-item {{ request()->routeIs('profil-kost.*') ? 'open active' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-cog"></i>
                            <div class="text-truncate">Pengaturan</div>
                        </a>
                        <ul class="menu-sub">
                            @canMenu('pengaturan.profil_kost', 'view')
                            <li class="menu-item {{ request()->routeIs('profil-kost.profile') ? 'active' : '' }}">
                                <a href="{{ route('profil-kost.profile') }}" class="menu-link">
                                    <div class="text-truncate">Profil</div>
                                </a>
                            </li>
                            @endCanMenu
                            @canMenu('pengaturan.tipe_kamar', 'view')
                            <li class="menu-item {{ request()->routeIs('kamar-tipe-hargas.*') ? 'active' : '' }}">
                                <a href="{{ route('kamar-tipe-hargas.index') }}" class="menu-link">
                                    <div class="text-truncate">Tipe Kamar</div>
                                </a>
                            </li>
                            @endCanMenu
                            @canMenu('pengaturan.notifikasi_wa', 'view')
                            <li class="menu-item {{ request()->routeIs('profil-kost.notification') ? 'active' : '' }}">
                                <a href="{{ route('profil-kost.notification') }}" class="menu-link">
                                    <div class="text-truncate">Notifikasi</div>
                                </a>
                            </li>
                            @endCanMenu
                        </ul>
                    </li>
                    @endif

                    @if($showAkses)
                    <li class="menu-item {{ request()->routeIs('roles.*', 'users.*') ? 'open active' : '' }}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                            <div class="text-truncate">Manajemen Akses</div>
                        </a>
                        <ul class="menu-sub">
                            @canMenu('manajemen_akses.role', 'view')
                            <li class="menu-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                <a href="{{ route('roles.index') }}" class="menu-link">
                                    <div class="text-truncate">Role</div>
                                </a>
                            </li>
                            @endCanMenu
                            @canMenu('manajemen_akses.user', 'view')
                            <li class="menu-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <a href="{{ route('users.index') }}" class="menu-link">
                                    <div class="text-truncate">User</div>
                                </a>
                            </li>
                            @endCanMenu
                        </ul>
                    </li>
                    @endif

                    @canMenu('sistem.log_aktivitas', 'view')
                    <li class="menu-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                        <a href="{{ route('activity-logs.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-history"></i>
                            <div class="text-truncate">Log Aktivitas</div>
                        </a>
                    </li>
                    @endCanMenu

                    <li class="menu-item {{ request()->routeIs('bantuan.*') ? 'active' : '' }}">
                        <a href="{{ route('bantuan.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-help-circle"></i>
                            <div class="text-truncate">Bantuan</div>
                        </a>
                    </li>

                </ul>
            </aside>
            <!-- / Menu -->

            <!-- Layout page -->
            <div class="layout-page">

                <!-- ─── NAVBAR ─── -->
                <nav
                    class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
                    id="layout-navbar">

                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                            <i class="icon-base bx bx-menu icon-md"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                        <!-- Search -->
                        <div class="navbar-nav align-items-center me-auto">
                            <div class="nav-item d-flex align-items-center">
                                <span class="w-px-22 h-px-22"><i class="icon-base bx bx-search icon-md"></i></span>
                                <input
                                    type="text"
                                    class="form-control border-0 shadow-none ps-1 ps-sm-2 d-md-block d-none"
                                    placeholder="Cari..."
                                    aria-label="Search..." />
                            </div>
                        </div>

                        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                            <!-- User Dropdown -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <span class="avatar-initial rounded-circle bg-primary">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <span class="avatar-initial rounded-circle bg-primary">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0">{{ auth()->user()->name ?? 'Admin' }}</h6>
                                                    <small class="text-body-secondary">{{ auth()->user()->email ?? '' }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li><div class="dropdown-divider my-1"></div></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                                            <i class="icon-base bx bx-user-circle icon-md me-3"></i>
                                            <span>Profil Saya</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profil-kost.profile') }}">
                                            <i class="icon-base bx bx-cog icon-md me-3"></i>
                                            <span>Pengaturan</span>
                                        </a>
                                    </li>
                                    <li><div class="dropdown-divider my-1"></div></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="icon-base bx bx-log-out icon-md me-3"></i>
                                                <span>Keluar</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- / Navbar -->

                <!-- ─── CONTENT WRAPPER ─── -->
                <div class="content-wrapper">

                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible mb-4" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible mb-4" role="alert">
                                <strong>Validasi gagal:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @yield('content')

                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl">
                            <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
                                <div class="mb-2 mb-md-0">
                                    © {{ date('Y') }}, {{ $appBrandName }}
                                </div>
                            </div>
                        </div>
                    </footer>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- / Content wrapper -->

            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- ─── CORE JS (order matters) ─── -->
    <script src="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/libs/popper/popper.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/js/bootstrap.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/vendor/js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/themeselection/sneat-bootstrap-html-admin-template-free@main/assets/js/main.js"></script>

    <!-- jQuery DataTables + Bootstrap 5 -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            const isMobileView = window.matchMedia('(max-width: 767.98px)').matches;

            const mobileImportantMatchers = [
                /aksi|action/,
                /status|jenis|role/,
                /jumlah|total|harga|nominal|uang|tagihan|saldo/,
                /penghuni|kamar|transaksi|invoice|nama|user|lantai|periode|tanggal|kontak/
            ];

            $('table.datatable').each(function () {
                const tableEl = this;
                const headerTexts = Array.from(tableEl.querySelectorAll('thead th')).map(function (th) {
                    return (th.textContent || '').trim().toLowerCase();
                });

                const hiddenTargets = [];

                if (isMobileView && headerTexts.length > 4) {
                    const explicitKeep = (tableEl.dataset.mobileCols || '')
                        .split(',')
                        .map(function (value) {
                            return parseInt(value.trim(), 10);
                        })
                        .filter(function (value) {
                            return Number.isInteger(value);
                        });
                    const keep = new Set(
                        explicitKeep.filter(function (idx) {
                            return idx >= 0 && idx < headerTexts.length;
                        })
                    );

                    if (!keep.size) {
                        const actionIdx = headerTexts.findIndex(function (text) {
                            return /aksi|action/.test(text);
                        });
                        if (actionIdx >= 0) {
                            keep.add(actionIdx);
                        }

                        mobileImportantMatchers.forEach(function (matcher) {
                            const idx = headerTexts.findIndex(function (text) {
                                return matcher.test(text);
                            });
                            if (idx >= 0) {
                                keep.add(idx);
                            }
                        });

                        const firstMeaningfulIdx = headerTexts.findIndex(function (text) {
                            return text && text !== '#' && !/check|pilih|select/.test(text);
                        });
                        if (firstMeaningfulIdx >= 0) {
                            keep.add(firstMeaningfulIdx);
                        }

                        const fallbackIdx = [2, 3].filter(function (idx) {
                            return idx < headerTexts.length;
                        });
                        fallbackIdx.forEach(function (idx) {
                            if (keep.size < 3) {
                                keep.add(idx);
                            }
                        });
                    }

                    headerTexts.forEach(function (_text, idx) {
                        if (!keep.has(idx)) {
                            hiddenTargets.push(idx);
                        }
                    });
                }

                $(tableEl).DataTable({
                    pageLength: 10,
                    autoWidth: false,
                    scrollX: false,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    columnDefs: [
                        { orderable: false, targets: [0, 1, -1] },
                        ...(hiddenTargets.length ? [{ visible: false, targets: hiddenTargets }] : [])
                    ],
                    language: {
                        search: '',
                        searchPlaceholder: 'Search...',
                        lengthMenu: 'Show _MENU_ entries',
                        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                        infoEmpty: 'Showing 0 to 0 of 0 entries',
                        infoFiltered: '(filtered from _MAX_ total entries)',
                        zeroRecords: 'No matching records found',
                        emptyTable: 'No data available',
                        paginate: { previous: '‹', next: '›' }
                    }
                });
            });

            // Select all checkboxes
            $('[id^="chkAll-"]').on('change', function () {
                $(this).closest('.card').find('tbody input[type="checkbox"]').prop('checked', this.checked);
            });

            function normalizeFormClasses(root = document) {
                root.querySelectorAll('input, select, textarea').forEach(function (el) {
                    if (el.closest('.dataTables_filter') || el.closest('.dataTables_length')) {
                        return;
                    }

                    if (el.tagName === 'SELECT') {
                        if (!el.classList.contains('form-select')) {
                            el.classList.add('form-select');
                        }
                        return;
                    }

                    if (el.tagName === 'TEXTAREA') {
                        if (!el.classList.contains('form-control')) {
                            el.classList.add('form-control');
                        }
                        return;
                    }

                    const type = (el.getAttribute('type') || 'text').toLowerCase();

                    if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset') {
                        return;
                    }

                    if (type === 'checkbox' || type === 'radio') {
                        if (!el.classList.contains('form-check-input')) {
                            el.classList.add('form-check-input');
                        }
                        return;
                    }

                    if (type === 'range') {
                        if (!el.classList.contains('form-range')) {
                            el.classList.add('form-range');
                        }
                        return;
                    }

                    if (!el.classList.contains('form-control')) {
                        el.classList.add('form-control');
                    }
                });
            }

            function setupBootstrapDropdownSelects(root = document) {
                root.querySelectorAll('select').forEach(function (select) {
                    if (select.closest('.dataTables_filter') || select.closest('.dataTables_length')) {
                        return;
                    }

                    if (select.dataset.dropdownified === '1') {
                        return;
                    }

                    if (select.multiple || Number(select.getAttribute('size') || 1) > 1) {
                        return;
                    }

                    if (select.closest('.input-group')) {
                        return;
                    }

                    if (select.classList.contains('no-bs-dropdown')) {
                        return;
                    }

                    const wrapper = document.createElement('div');
                    wrapper.className = 'dropdown bs-select-dropdown';

                    if (select.getAttribute('style')) {
                        wrapper.setAttribute('style', select.getAttribute('style'));
                    }

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn bs-select-toggle dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center';
                    button.setAttribute('data-bs-toggle', 'dropdown');
                    button.setAttribute('aria-expanded', 'false');
                    button.setAttribute('data-bs-auto-close', 'outside');

                    const menu = document.createElement('ul');
                    menu.className = 'dropdown-menu';

                    select.parentNode.insertBefore(wrapper, select);
                    wrapper.appendChild(button);
                    wrapper.appendChild(menu);
                    wrapper.appendChild(select);

                    select.classList.add('bs-select-native');
                    select.dataset.dropdownified = '1';

                    const escapeHtml = function (str) {
                        return String(str)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                    };

                    const getPlaceholder = function () {
                        const selected = select.options[select.selectedIndex];
                        if (selected && selected.value !== '') {
                            return selected.text;
                        }
                        const emptyOption = Array.from(select.options).find(function (opt) {
                            return opt.value === '';
                        });
                        return (emptyOption && emptyOption.text) || 'Pilih opsi';
                    };

                    const renderMenu = function () {
                        const currentValue = select.value;
                        const items = [];
                        const selectableOptions = Array.from(select.options).filter(function (opt) {
                            return opt.value !== '' && !opt.hidden;
                        });
                        const useSearch = selectableOptions.length >= 8;

                        if (useSearch) {
                            items.push(
                                '<li class="bs-select-search-wrap"><input type="search" class="form-control form-control-sm bs-select-search" placeholder="Cari opsi..."></li>' +
                                '<li><hr class="dropdown-divider my-1"></li>'
                            );
                        }

                        Array.from(select.options).forEach(function (opt) {
                            if (opt.value === '' || opt.hidden) {
                                return;
                            }

                            const active = currentValue === opt.value ? ' active' : '';
                            const disabled = opt.disabled ? ' disabled' : '';
                            items.push(
                                '<li><button type="button" class="dropdown-item' + active + disabled + '" data-value="' + escapeHtml(opt.value) + '" data-label="' + escapeHtml(opt.text) + '"' + (opt.disabled ? ' disabled' : '') + '>' + escapeHtml(opt.text) + '</button></li>'
                            );
                        });

                        if (!items.length) {
                            items.push('<li><span class="dropdown-item-text text-body-secondary">Tidak ada opsi</span></li>');
                        }

                        if (useSearch) {
                            items.push('<li class="d-none bs-select-empty-state"><span class="dropdown-item-text text-body-secondary">Opsi tidak ditemukan</span></li>');
                        }

                        menu.innerHTML = items.join('');
                        button.textContent = getPlaceholder();

                        menu.querySelectorAll('button[data-value]').forEach(function (itemBtn) {
                            itemBtn.addEventListener('click', function () {
                                select.value = this.getAttribute('data-value');
                                select.dispatchEvent(new Event('change', { bubbles: true }));
                                button.classList.remove('is-invalid');
                                bootstrap.Dropdown.getOrCreateInstance(button).hide();
                                renderMenu();
                            });
                        });

                        const searchInput = menu.querySelector('.bs-select-search');
                        const emptyState = menu.querySelector('.bs-select-empty-state');
                        if (searchInput) {
                            searchInput.addEventListener('click', function (event) {
                                event.stopPropagation();
                            });
                            searchInput.addEventListener('input', function () {
                                const keyword = this.value.trim().toLowerCase();
                                let visibleCount = 0;

                                menu.querySelectorAll('button[data-label]').forEach(function (optBtn) {
                                    const label = optBtn.getAttribute('data-label').toLowerCase();
                                    const row = optBtn.closest('li');
                                    const visible = label.includes(keyword);
                                    row.classList.toggle('d-none', !visible);
                                    if (visible) {
                                        visibleCount += 1;
                                    }
                                });

                                if (emptyState) {
                                    emptyState.classList.toggle('d-none', visibleCount > 0);
                                }
                            });
                        }
                    };

                    const observer = new MutationObserver(function () {
                        renderMenu();
                    });
                    observer.observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['selected', 'disabled', 'hidden', 'value'] });

                    select.addEventListener('change', function () {
                        button.classList.remove('is-invalid');
                        renderMenu();
                    });

                    if (select.form) {
                        select.form.addEventListener('submit', function (event) {
                            if (select.required && !select.value) {
                                event.preventDefault();
                                button.classList.add('is-invalid');
                            }
                        });
                    }

                    renderMenu();
                });
            }

            normalizeFormClasses(document);
            setupBootstrapDropdownSelects(document);
        });
    </script>

    @yield('scripts')
</body>
</html>
