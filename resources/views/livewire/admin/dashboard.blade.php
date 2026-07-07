<div>
    <section class="tl-hero">
        <div class="tl-eyebrow">Admin command board</div>
        <h2 class="display-6 fw-bold">Operations comes first.</h2>
        <p class="tl-muted mb-0">Users, coaches, athletes, programs, invitations, and progress are visible without hunting.</p>
    </section>

    <div class="row g-3 mb-3">
        @foreach($stats as $label => $value)
            <div class="col-6 col-lg-3">
                <div class="tl-stat">
                    <span class="tl-muted text-capitalize">{{ str($label)->headline() }}</span>
                    <strong>{{ $value }}</strong>
                </div>
            </div>
        @endforeach
    </div>

    <div class="tl-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="tl-eyebrow">Audit</div>
                <h3 class="h5 mb-0">Recent activity</h3>
            </div>
            <a class="btn btn-outline-tl btn-sm" href="{{ route('admin.audit') }}">Open logs</a>
        </div>
        <div class="tl-table-wrap">
            <table class="table tl-table align-middle">
                <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Summary</th></tr></thead>
                <tbody>
                @forelse($recentAudits as $log)
                    <tr>
                        <td>{{ $log->created_at->format('M j, H:i') }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td><span class="tl-badge gray">{{ $log->action }}</span></td>
                        <td>{{ $log->summary }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="tl-muted">No audit activity yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
