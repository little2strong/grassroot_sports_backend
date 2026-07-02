@extends('club.layouts.master')

@section('title', $title)

@section('content')
<main class="club-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <p class="text-muted small mb-0">Update your club profile, branding, and privacy settings.</p>
        <a href="{{ route('club.profile.index') }}" class="btn btn-sm btn-light border">
            <i class="fas fa-arrow-left me-1"></i> Back to profile
        </a>
    </div>

    <form action="{{ route('club.profile.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="club-card mb-4">
                    <div class="club-card-header">
                        <h6 class="mb-0">Basic Information</h6>
                    </div>
                    <div class="club-card-body padded">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label">Club name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" value="{{ old('name', $club->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="short_name" class="form-label">Short name</label>
                                <input type="text" class="form-control @error('short_name') is-invalid @enderror"
                                    id="short_name" name="short_name" value="{{ old('short_name', $club->short_name) }}" maxlength="10">
                                @error('short_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror"
                                    id="city" name="city" value="{{ old('city', $club->city) }}">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control @error('country') is-invalid @enderror"
                                    id="country" name="country" value="{{ old('country', $club->country) }}">
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror"
                                    id="address" name="address" value="{{ old('address', $club->address) }}">
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control @error('website') is-invalid @enderror"
                                    id="website" name="website" value="{{ old('website', $club->website) }}" placeholder="https://">
                                @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="founded_year" class="form-label">Founded year</label>
                                <input type="number" class="form-control @error('founded_year') is-invalid @enderror"
                                    id="founded_year" name="founded_year" value="{{ old('founded_year', $club->founded_year) }}"
                                    min="1800" max="{{ date('Y') }}">
                                @error('founded_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    id="description" name="description" rows="4">{{ old('description', $club->description) }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="club-card">
                    <div class="club-card-header">
                        <h6 class="mb-0">Privacy & Visibility</h6>
                    </div>
                    <div class="club-card-body padded">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                                @checked(old('is_public', $club->is_public))>
                            <label class="form-check-label" for="is_public">Public club profile</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="show_public_profiles" name="show_public_profiles" value="1"
                                @checked(old('show_public_profiles', $club->show_public_profiles))>
                            <label class="form-check-label" for="show_public_profiles">Show public player profiles</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="hide_player_names_publicly" name="hide_player_names_publicly" value="1"
                                @checked(old('hide_player_names_publicly', $club->hide_player_names_publicly))>
                            <label class="form-check-label" for="hide_player_names_publicly">Hide player names on public pages</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="hide_player_photos_publicly" name="hide_player_photos_publicly" value="1"
                                @checked(old('hide_player_photos_publicly', $club->hide_player_photos_publicly))>
                            <label class="form-check-label" for="hide_player_photos_publicly">Hide player photos on public pages</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="club-card mb-4">
                    <div class="club-card-header">
                        <h6 class="mb-0">Club Logo</h6>
                    </div>
                    <div class="club-card-body padded text-center">
                        @if($club->logo_url)
                            <img src="{{ $club->logo_url }}" alt="{{ $club->name }}" class="rounded mb-3" width="96" height="96" style="object-fit:cover;">
                        @endif
                        <input type="file" class="form-control @error('logo') is-invalid @enderror" name="logo" accept="image/*">
                        @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <small class="text-muted d-block mt-2">Max 1 MB. JPG, PNG, or WebP.</small>
                    </div>
                </div>

                <div class="club-card mb-4">
                    <div class="club-card-header">
                        <h6 class="mb-0">Cover Image</h6>
                    </div>
                    <div class="club-card-body padded">
                        @if($club->cover_url)
                            <img src="{{ $club->cover_url }}" alt="Cover" class="rounded mb-3 w-100" style="max-height:120px;object-fit:cover;">
                        @endif
                        <input type="file" class="form-control @error('cover_image') is-invalid @enderror" name="cover_image" accept="image/*">
                        @error('cover_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <small class="text-muted d-block mt-2">Max 4 MB.</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-club-primary w-100 py-2">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </form>
</main>
@endsection
