@extends('admin.layouts.master')
@section('settings', 'active')
@section('title'){{ $title ?? '' }} @endsection

@push('style')
@endpush

@section('content')
    <!-- Main Content Area -->
    <main class="container-fluid p-3 p-lg-4">
        <form action="{{ route('admin.settings.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="page-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="mb-0">
                    <h2 class="page-content-title fw-semibold fs-5">System Configuration</h2>
                    <p class="page-subtitle">Manage global platform settings</p>
                </div>
            </div>

            <!-- Branding Section -->
            <div class="settings-section">
                <div class="settings-section-header">
                    <div class="d-flex gap-2">
                        <i class="fas fa-cog text-primary" style="color: var(--primary-blue); font-size: 20px;"></i>
                        <div>
                            <h5 class="settings-section-title">Branding</h5>
                        </div>
                    </div>
                    <p class="settings-section-subtitle">Customize platform appearance</p>
                </div>
                <div class="settings-section-body">
                    <div class="row g-4">

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">
                                Logo Upload
                                <small class="form-text">Recommended: 200x60px PNG or SVG</small>
                            </label>

                            <input type="file" name="site_logo" id="siteLogoInput" class="d-none" accept="image/*">

                            <button type="button" class="upload-logo-btn" id="uploadLogoBtn">
                                <i class="fas fa-upload me-2"></i> Upload Logo
                            </button>

                            <div class="mt-2">
                                <img id="siteLogoPreview"
                                    src="{{ !empty($settings->site_logo) ? asset($settings->site_logo) : '' }}"
                                    class="img-thumbnail {{ empty($settings->site_logo) ? 'd-none' : '' }}" width="120">
                            </div>
                        </div>
                        <!-- FAVICON -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">
                                Favicon
                                <small class="form-text">Recommended: 32x32px PNG or SVG</small>
                            </label>

                            <input type="file" name="favicon" id="faviconInput" class="d-none" accept="image/*">

                            <button type="button" class="upload-logo-btn" id="uploadFaviconBtn">
                                <i class="fas fa-upload me-2"></i> Upload Favicon
                            </button>

                            <div class="mt-2">
                                <img id="faviconPreview"
                                    src="{{ !empty($settings->favicon) ? asset($settings->favicon) : '' }}"
                                    class="img-thumbnail {{ empty($settings->favicon) ? 'd-none' : '' }}" width="60">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="siteName" class="form-label fw-medium">Site Name</label>
                            <input type="text" class="form-control" id="siteName" name="site_name"
                                value="{{ old('site_name', $settings->site_name ?? '') }}">
                        </div>

                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-section-header">
                    <div class="d-flex gap-2">
                        <i class="fas fa-cog text-primary" style="color: var(--primary-blue); font-size: 20px;"></i>
                        <div>
                            <h5 class="settings-section-title">Seo Settings</h5>
                        </div>
                    </div>
                    <p class="settings-section-subtitle">Configure Seo Setup</p>
                </div>
                <div class="settings-section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="seo_meta_title" class="form-label fw-medium">Meta Title</label>
                            <input type="text" class="form-control" id="seo_meta_title" name="seo_meta_title"
                                value="{{ old('seo_meta_title', $settings->seo_meta_title ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="seo_keywords" class="form-label fw-medium">Meta Keyword</label>
                            <input type="text" class="form-control" id="seo_keywords" name="seo_keywords"
                                value="{{ old('seo_keywords', $settings->seo_keywords ?? '') }}">
                        </div>
                        <div class="col-md-12">
                            <label for="seo_meta_description" class="form-label fw-medium">Meta Description</label>
                            <textarea name="seo_meta_description" class="form-control" id="seo_meta_description" rows="4">{{ old('support_email', $settings->seo_meta_description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <div class="settings-section-header">
                    <div class="d-flex gap-2">
                        <i class="fas fa-cog text-primary" style="color: var(--primary-blue); font-size: 20px;"></i>
                        <div>
                            <h5 class="settings-section-title">General Settings</h5>
                        </div>
                    </div>
                    <p class="settings-section-subtitle">Configure core system and account options</p>
                </div>
                <div class="settings-section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="email" class="form-label fw-medium">Email
                                <small class="text-muted">
                                    (Note: All admin notification emails will be sent to this address.)
                                </small>
                            </label>
                            <input type="text" class="form-control" id="email" name="email"
                                value="{{ old('email', $settings->email ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="support_email" class="form-label fw-medium">Support Email</label>
                            <input type="text" class="form-control" id="support_email" name="support_email"
                                value="{{ old('support_email', $settings->support_email ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="phone_no" class="form-label fw-medium">Phone Number</label>
                            <input type="text" class="form-control" id="phone_no" name="phone_no"
                                value="{{ old('phone_no', $settings->phone_no ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="office_address" class="form-label fw-medium">Office Address</label>
                            <input type="text" class="form-control" id="office_address" name="office_address"
                                value="{{ old('office_address', $settings->office_address ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="twitter_url" class="form-label fw-medium">Twitter Url</label>
                            <input type="text" class="form-control" id="twitter_url" name="twitter_url"
                                value="{{ old('twitter_url', $settings->twitter_url ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label for="linkedin_url" class="form-label fw-medium">Linkedin Url</label>
                            <input type="text" class="form-control" id="linkedin_url" name="linkedin_url"
                                value="{{ old('linkedin_url', $settings->linkedin_url ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>


            <!-- Stripe Configuration Section -->
            {{-- <div class="settings-section">
                <div class="settings-section-header">
                    <div class="d-flex gap-2">
                        <i class="fas fa-credit-card text-primary"
                            style="color: var(--primary-blue); font-size: 20px;"></i>
                        <div>
                            <h5 class="settings-section-title">Stripe Configuration</h5>
                        </div>
                    </div>
                    <p class="settings-section-subtitle">Payment processing settings</p>
                </div>
                <div class="settings-section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="stripePub" class="form-label fw-medium">Publishable Key</label>
                            <input type="text" id="stripePub" name="stripe_publishable_key" class="form-control"
                                value="{{ old('stripe_publishable_key', $settings->stripe_publishable_key) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="stripeSecret" class="form-label fw-medium">Secret Key</label>
                            <input type="password" id="stripeSecret" name="stripe_secret" class="form-control"
                                value="{{ old('stripe_secret', $settings->stripe_secret) }}">
                        </div>
                    </div>
                </div>
            </div>




            <!-- Divider -->
            <hr class="my-3"> --}}

            <!-- Save Button -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary-custom px-3" type="submit">
                            <i class="fas fa-save me-2"></i>Save All Settings
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <form action="{{ route('admin.settings.cashback.update') }}" method="POST" >
            @csrf
            <div class="settings-section">
                <div class="settings-section-header">
                    <div class="d-flex gap-2">
                        <i class="fas fa-cog text-primary" style="color: var(--primary-blue); font-size: 20px;"></i>
                        <div>
                            <h5 class="settings-section-title">Cashback Settings</h5>
                        </div>
                    </div>
                    <p class="settings-section-subtitle">Configure Cashback Setup</p>
                </div>
                <div class="settings-section-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="user_cashback_rate" class="form-label fw-medium">Use Cashback Rate</label>
                            <input type="number" class="form-control" id="user_cashback_rate" name="user_cashback_rate"
                                value="{{ old('user_cashback_rate', $payment_cashback->percentage ?? '') }}">
                        </div>

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-end">
                        <button class="btn btn-primary-custom px-3" type="submit">
                            <i class="fas fa-save me-2"></i>Save Cashback Settings
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </main>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // Color picker functionality
            $('#primaryColor').on('change', function() {
                const color = $(this).val();
                $('#primaryColorValue').val(color);
                $('.color-preview').first().css('background-color', color);
            });

            $('#secondaryColor').on('change', function() {
                const color = $(this).val();
                $('#secondaryColorValue').val(color);
                $('.color-preview').eq(1).css('background-color', color);
            });

            // Color text input functionality
            $('#primaryColorValue').on('change', function() {
                const color = $(this).val();
                if (isValidColor(color)) {
                    $('#primaryColor').val(color);
                    $('.color-preview').first().css('background-color', color);
                }
            });

            $('#secondaryColorValue').on('change', function() {
                const color = $(this).val();
                if (isValidColor(color)) {
                    $('#secondaryColor').val(color);
                    $('.color-preview').eq(1).css('background-color', color);
                }
            });

            // Maintenance toggle functionality
            $('#maintenanceToggle').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#maintenanceSettings').slideDown();
                } else {
                    $('#maintenanceSettings').slideUp();
                }
            });

            // File upload button
            // $('.upload-logo-btn').on('click', function(e) {
            //     e.preventDefault();
            //     // Create file input
            //     const fileInput = document.createElement('input');
            //     fileInput.type = 'file';
            //     fileInput.accept = '.png,.jpg,.jpeg,.svg';
            //     fileInput.style.display = 'none';

            //     fileInput.onchange = function(e) {
            //         const file = e.target.files[0];
            //         if (file) {
            //             if (file.size > 5 * 1024 * 1024) { // 5MB limit
            //                 alert('File size must be less than 5MB');
            //                 return;
            //             }

            //             const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
            //             if (!allowedTypes.includes(file.type)) {
            //                 alert('Only PNG, JPG, and SVG files are allowed');
            //                 return;
            //             }

            //             // In a real app, you would upload the file here
            //             alert('Logo uploaded successfully! (Simulated)');
            //         }
            //     };

            //     document.body.appendChild(fileInput);
            //     fileInput.click();
            //     document.body.removeChild(fileInput);
            // });

            // Helper function to validate color
            function isValidColor(color) {
                const s = new Option().style;
                s.color = color;
                return s.color !== '';
            }
        });
    </script>
    <script>
        $(function() {

            // Logo picker
            $('#uploadLogoBtn').on('click', function() {
                $('#siteLogoInput').trigger('click');
            });

            // Favicon picker
            $('#uploadFaviconBtn').on('click', function() {
                $('#faviconInput').trigger('click');
            });

            // Logo preview
            $('#siteLogoInput').on('change', function() {
                previewImage(this, '#siteLogoPreview');
            });

            // Favicon preview
            $('#faviconInput').on('change', function() {
                previewImage(this, '#faviconPreview');
            });

            function previewImage(input, previewId) {
                if (!input.files || !input.files[0]) return;

                const file = input.files[0];

                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image file');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    $(previewId)
                        .attr('src', e.target.result)
                        .removeClass('d-none');
                };
                reader.readAsDataURL(file);
            }

        });
    </script>
@endpush
