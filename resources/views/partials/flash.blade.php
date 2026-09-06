@if (session('success'))
    <div class="mb-4 flex items-center gap-2 rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
        {{ session('success') }}
    </div>
@endif
