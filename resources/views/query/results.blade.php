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
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">Your Question</h5>
                                    <p class="card-text">{{ $userQuery }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title text-primary">Generated SQL</h5>
                                    <pre class="card-text"><code class="language-sql">{{ $generatedSql }}</code></pre>
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

@push('styles')
<style>
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .table td {
        vertical-align: middle;
    }
    pre {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-bottom: 0;
    }
    .card-header {
        border-bottom: 0;
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
