@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">Update squad details and status.</p>
        <a href="{{ route('club.squads.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to squads
        </a>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0">Edit: {{ $team->name }}</h6>
        </div>
        <div class="club-card-body padded">
            <form action="{{ route('club.squads.update', $team) }}" method="POST">
                @csrf
                @method('PUT')
                @include('club.squads._form')
                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-club-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
