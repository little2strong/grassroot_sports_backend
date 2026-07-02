@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">Create a new squad for your club.</p>
        <a href="{{ route('club.squads.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to squads
        </a>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0">Squad Details</h6>
        </div>
        <div class="club-card-body padded">
            <form action="{{ route('club.squads.store') }}" method="POST">
                @csrf
                @include('club.squads._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-club-primary">
                        <i class="fas fa-plus me-1"></i> Create Squad
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
