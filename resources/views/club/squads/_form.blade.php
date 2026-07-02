<div class="row g-3">
    <div class="col-md-8">
        <label for="name" class="form-label">Squad name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror"
            id="name" name="name" value="{{ old('name', $team->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label for="short_name" class="form-label">Short name</label>
        <input type="text" class="form-control @error('short_name') is-invalid @enderror"
            id="short_name" name="short_name" value="{{ old('short_name', $team->short_name) }}" maxlength="10">
        @error('short_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="primary_color" class="form-label">Primary color</label>
        <div class="input-group">
            <input type="color" class="form-control form-control-color @error('primary_color') is-invalid @enderror"
                id="primary_color" name="primary_color" value="{{ old('primary_color', $team->primary_color ?? '#1e3a5f') }}">
            <input type="text" class="form-control" value="{{ old('primary_color', $team->primary_color ?? '#1e3a5f') }}"
                readonly id="primary_color_hex">
        </div>
        @error('primary_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label for="secondary_color" class="form-label">Secondary color</label>
        <div class="input-group">
            <input type="color" class="form-control form-control-color @error('secondary_color') is-invalid @enderror"
                id="secondary_color" name="secondary_color" value="{{ old('secondary_color', $team->secondary_color ?? '#ffffff') }}">
            <input type="text" class="form-control" value="{{ old('secondary_color', $team->secondary_color ?? '#ffffff') }}"
                readonly id="secondary_color_hex">
        </div>
        @error('secondary_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                @checked(old('is_active', $team->is_active ?? true))>
            <label class="form-check-label" for="is_active">Active squad</label>
        </div>
    </div>
</div>

@push('script')
<script>
    document.getElementById('primary_color')?.addEventListener('input', function () {
        document.getElementById('primary_color_hex').value = this.value;
    });
    document.getElementById('secondary_color')?.addEventListener('input', function () {
        document.getElementById('secondary_color_hex').value = this.value;
    });
</script>
@endpush
