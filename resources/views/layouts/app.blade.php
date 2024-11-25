<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <!-- Styles -->
        @stack('styles')
        <style>
            .table-responsive {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
            .table {
                margin-bottom: 0;
                white-space: nowrap;
            }
            .table td, .table th {
                padding: 0.5rem 0.75rem;
                max-width: 300px;
                overflow: hidden;
                text-overflow: ellipsis;
                font-size: 0.9rem;
            }
            .table thead.table-dark th {
                background-color: #212529 !important;
                color: white !important;
                font-weight: 500;
                border-bottom: none;
                font-size: 0.9rem;
            }
            .table tbody tr:nth-of-type(odd) {
                background-color: rgba(0, 0, 0, 0.02);
            }
            .table tbody tr:hover {
                background-color: rgba(0, 0, 0, 0.05);
            }
            .table td a {
                color: #0d6efd;
                text-decoration: none;
            }
            .table td a:hover {
                color: #0a58ca;
                text-decoration: underline;
            }
            pre {
                margin-bottom: 0;
            }
            code {
                white-space: pre-wrap;
                word-wrap: break-word;
                font-size: 0.85rem;
            }
            .card {
                border: none;
            }
            .card-header {
                background-color: #212529 !important;
                color: white !important;
                padding: 0.5rem 1rem;
            }
            .card-header h2, .card-header h3, .card-header h5 {
                color: white !important;
                font-weight: 500;
                font-size: 1rem;
                margin: 0;
            }
            .bg-dark {
                background-color: #212529 !important;
            }
            .form-control {
                font-size: 0.9rem;
            }
            .btn {
                font-size: 0.9rem;
            }
            .accordion-button {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
            .accordion-body {
                padding: 1rem;
            }
            .badge {
                font-weight: 500;
                font-size: 0.8rem;
            }
            .alert {
                margin-bottom: 0;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')
            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif
            <!-- Page Content -->
            <main>
                @yield('content')
            </main>
        </div>
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Additional Scripts -->
        @stack('scripts')
    </body>
</html>