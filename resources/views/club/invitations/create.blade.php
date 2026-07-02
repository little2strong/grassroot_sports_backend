@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">Select a registered player from the system to invite to {{ $club->name }}.</p>
        <a href="{{ route('club.invitations.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to invitations
        </a>
    </div>

    <div class="club-card">
        <div class="club-card-header">
            <h6 class="mb-0">Invitation Details</h6>
        </div>
        <div class="club-card-body padded">
            <form action="{{ route('club.invitations.store') }}" method="POST">
                @csrf
                @include('club.invitations._form')
                <div class="mt-4">
                    <button type="submit" class="btn btn-club-primary" @disabled($players->isEmpty())>
                        <i class="fas fa-paper-plane me-1"></i> Send Invitation
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
