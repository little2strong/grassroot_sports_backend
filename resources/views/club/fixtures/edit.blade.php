@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">
            {{ $fixture->home_display_name }} vs {{ $fixture->away_display_name }}
        </p>
        <a href="{{ route('club.fixtures.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to fixtures
        </a>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0">Edit Fixture</h6>
            <span class="club-badge {{ $fixture->status === 'published' ? 'success' : 'muted' }}">{{ ucfirst($fixture->status) }}</span>
        </div>
        <div class="club-card-body padded">
            <form action="{{ route('club.fixtures.update', $fixture) }}" method="POST">
                @csrf
                @method('PUT')
                @include('club.fixtures._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-club-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
