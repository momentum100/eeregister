@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-11">
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Query Results</h3>
                        <a href="{{ route('query.index') }}" class="btn btn-light">New Query</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="query-results-container">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title mb-1">Natural Language Query</h6>
                                        <p class="card-text small mb-0" x-text="results?.query || ''">{{ $userQuery }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title mb-1">Generated SQL</h6>
                                        <pre class="card-text small mb-0"><code class="language-sql" x-text="results?.sql || ''">{{ $generatedSql }}</code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12" x-show="results?.tokens" x-cloak>
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <h6 class="card-title mb-1">Token Usage & Cost</h6>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <div class="d-flex justify-content-between align-items-center small">
                                                    <span>Input Tokens:</span>
                                                    <span class="badge bg-secondary" x-text="results?.tokens?.input || 0"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                                    <span>Cost:</span>
                                                    <span x-text="'$' + ((results?.tokens?.input || 0) / 1000000 * 0.15).toFixed(6)"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex justify-content-between align-items-center small">
                                                    <span>Output Tokens:</span>
                                                    <span class="badge bg-secondary" x-text="results?.tokens?.output || 0"></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                                    <span>Cost:</span>
                                                    <span x-text="'$' + ((results?.tokens?.output || 0) / 1000000 * 0.60).toFixed(6)"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex justify-content-between align-items-center small">
                                                    <span>Total Cost:</span>
                                                    <span class="badge bg-info" x-text="'$' + (results?.tokens?.cost || 0).toFixed(6)"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($results))
                        <div class="table-responsive" style="width: 100%;">
                            <table class="table table-hover table-striped table-bordered w-100">
                                <thead class="table-dark">
                                    <tr>
                                        @foreach(array_keys($results->first()) as $column)
                                            <th class="text-nowrap">{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $row)
                                        <tr>
                                            @foreach($row as $value)
                                                <td>
                                                    @if(is_null($value))
                                                        <span class="text-muted">NULL</span>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($results->hasPages())
                            <div class="d-flex justify-content-center mt-4">
                                {{ $results->links() }}
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No results found for your query.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format SQL code
    const sqlCode = document.querySelector('code.sql-query');
    if (sqlCode && typeof hljs !== 'undefined') {
        hljs.highlightElement(sqlCode);
    }
});
</script>
@endpush

@push('styles')
<style>
.query-results-container {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    overflow: visible !important;
}
[x-cloak] {
    display: none !important;
}
pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.card-header {
    border-bottom: 0;
}
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}
.table td {
    vertical-align: middle;
}
.table-responsive {
    margin: 0 -1px;
}
.badge {
    font-size: 0.875rem;
}
</style>
@endpush
@endsection
