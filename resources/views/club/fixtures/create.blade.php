@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">Schedule a new match for your club.</p>
        <a href="{{ route('club.fixtures.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to fixtures
        </a>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0">Fixture Details</h6>
        </div>
        <div class="club-card-body padded">
            <form action="{{ route('club.fixtures.store') }}" method="POST">
                @csrf
                @include('club.fixtures._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-club-primary">
                        <i class="fas fa-calendar-plus me-1"></i> Schedule Fixture
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
