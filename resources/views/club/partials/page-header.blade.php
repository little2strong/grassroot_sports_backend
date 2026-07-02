<div class="club-card mb-4">
    <div class="club-card-header">
        <h6 class="mb-0">{{ $title ?? 'Page' }}</h6>
        @isset($headerAction)
            <div>{{ $headerAction }}</div>
        @endisset
    </div>
</div>
