@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" x-data="queryForm()">
    <div class="row justify-content-center">
        <div class="col-11">
            <div class="card shadow-sm">
                <div class="card-header py-2">
                    <h5 class="mb-0">AI Query Interface</h5>
                </div>
                <div class="card-body py-3">
                    <div x-ref="form">
                        @csrf
                        <div class="form-group">
                            <textarea class="form-control" 
                                    id="user_query" 
                                    name="user_query" 
                                    rows="3" 
                                    x-model="query"
                                    placeholder="Enter your query here..."
                                    required></textarea>
                            <div x-show="error" x-cloak class="alert alert-danger mt-2 py-2 small" x-text="error"></div>
                        </div>
                        <div class="d-grid mt-2">
                            <button type="button" 
                                    class="btn btn-dark" 
                                    :disabled="loading || submitting" 
                                    @click="submitQuery">
                                <span class="spinner-border spinner-border-sm" x-show="loading" role="status"></span>
                                <span x-text="loading ? 'Processing...' : 'Submit Query'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div x-show="results !== null" x-cloak class="mt-3">
                <div class="card shadow-sm">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Results</h5>
                        <span class="badge" :class="results?.results?.length > 0 ? 'bg-success' : 'bg-warning'" x-text="results?.results?.length > 0 ? `${results.results.length} result${results.results.length === 1 ? '' : 's'}` : 'No results'"></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="accordion" id="queryDetails">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#queryInfo">
                                        Query Details
                                        <template x-if="results?.tokens">
                                            <span class="badge bg-info ms-2">
                                                <span x-text="'$' + results.tokens.cost.toFixed(6)"></span>
                                            </span>
                                        </template>
                                    </button>
                                </h2>
                                <div id="queryInfo" class="accordion-collapse collapse show" data-bs-parent="#queryDetails">
                                    <div class="accordion-body pt-0">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body py-2">
                                                        <h6 class="card-title mb-1">Natural Language Query</h6>
                                                        <p class="card-text small mb-0" x-text="results?.query || ''"></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card bg-light">
                                                    <div class="card-body py-2">
                                                        <h6 class="card-title mb-1">Generated SQL</h6>
                                                        <pre class="card-text small mb-0"><code class="language-sql" x-text="results?.sql || ''"></code></pre>
                                                    </div>
                                                </div>
                                            </div>
                                            <template x-if="results?.tokens">
                                                <div class="col-12">
                                                    <div class="card bg-light">
                                                        <div class="card-body py-2">
                                                            <h6 class="card-title mb-1">Token Usage & Cost</h6>
                                                            <div class="row g-2">
                                                                <div class="col-md-4">
                                                                    <div class="d-flex justify-content-between align-items-center small">
                                                                        <span>Input Tokens:</span>
                                                                        <span class="badge bg-secondary" x-text="results.tokens.input"></span>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between align-items-center small text-muted">
                                                                        <span>Cost:</span>
                                                                        <span x-text="'$' + ((results.tokens.input / 1000000) * 0.15).toFixed(6)"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="d-flex justify-content-between align-items-center small">
                                                                        <span>Output Tokens:</span>
                                                                        <span class="badge bg-secondary" x-text="results.tokens.output"></span>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between align-items-center small text-muted">
                                                                        <span>Cost:</span>
                                                                        <span x-text="'$' + ((results.tokens.output / 1000000) * 0.60).toFixed(6)"></span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="d-flex justify-content-between align-items-center small">
                                                                        <span>Total Cost:</span>
                                                                        <span class="badge bg-info" x-text="'$' + results.tokens.cost.toFixed(6)"></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <template x-if="results?.results && Array.isArray(results.results) && results.results.length > 0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-bordered mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="text-nowrap text-center" style="width: 50px;">#</th>
                                            <template x-for="(value, key) in results.results[0]" :key="key">
                                                <th class="text-nowrap" x-text="String(key).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())"></th>
                                            </template>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(row, rowIndex) in results.results" :key="rowIndex">
                                            <tr>
                                                <td class="text-center" x-text="rowIndex + 1"></td>
                                                <template x-for="(value, key) in row" :key="key">
                                                    <td>
                                                        <template x-if="value === null">
                                                            <span class="text-muted">NULL</span>
                                                        </template>
                                                        <template x-if="value !== null && (key.toLowerCase() === 'registration_code' || key.toLowerCase() === 'registration code')">
                                                            <a :href="'https://ariregister.rik.ee/est/company/' + value" 
                                                               target="_blank" 
                                                               class="text-decoration-none">
                                                               <span x-text="value"></span>
                                                            </a>
                                                        </template>
                                                        <template x-if="value !== null && key.toLowerCase() !== 'registration_code' && key.toLowerCase() !== 'registration code'">
                                                            <span x-text="value"></span>
                                                        </template>
                                                    </td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <template x-if="!results?.results || !Array.isArray(results.results) || results.results.length === 0">
                            <div class="alert alert-warning m-3 py-2 small">
                                <i class="fas fa-exclamation-circle"></i> Please try modifying your query or check if the table contains the data you're looking for.
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function queryForm() {
    return {
        query: '',
        loading: false,
        submitting: false,
        error: null,
        results: null,
        hasResults: false,

        async submitQuery() {
            if (this.loading || this.submitting || !this.query.trim()) return;
            
            this.loading = true;
            this.submitting = true;
            this.error = null;
            this.hasResults = false;
            this.results = null;

            try {
                const response = await fetch('{{ route('query.execute') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        user_query: this.query
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'An error occurred while processing your query.');
                }

                await this.$nextTick();
                this.results = data;
                this.hasResults = Boolean(data?.results?.length);

                console.log('Results:', this.results); // Debug log
            } catch (e) {
                this.error = e.message;
                console.error('Error:', e); // Debug log
            } finally {
                this.loading = false;
                this.submitting = false;
            }
        }
    }
}
</script>
@endpush

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    #queryInfo {
        visibility: visible !important;
        display: block !important;
        opacity: 1 !important;
    }
    #queryInfo.show {
        visibility: visible !important;
        display: block !important;
        opacity: 1 !important;
    }
    .accordion-collapse:not(.collapsing) {
        visibility: visible !important;
        display: block !important;
        opacity: 1 !important;
    }
    .accordion-button:not(.collapsed)::after {
        transform: rotate(-180deg);
    }
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
