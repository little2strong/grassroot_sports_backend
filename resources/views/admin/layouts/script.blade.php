<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<!-- Custom Script -->
<script src="{{ asset('admin_panel/assets/app.js') }}"></script>

{{-- <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote.min.js"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- IziToast Notify --}}
@if (session()->has('notify'))
    @foreach (session('notify') as $msg)
        <script>
            "use strict";
            iziToast.{{ $msg[0] }}({
                message: "{{ __($msg[1]) }}",
                position: "topRight"
            });
        </script>
    @endforeach
@endif

{{-- IziToast Error Notify --}}
@if (session()->has('errors'))
    @foreach (session('errors')->all() as $error)
        <script>
            iziToast.error({
                message: '{{ __($error) }}',
                position: "topRight"
            });
        </script>
    @endforeach
@endif
@if (session('error'))
    <script>
        iziToast.error({
            message: '{{ session("error") }}',
            position: "topRight"
        });
    </script>
@endif
{{-- IziToast Success Notify --}}
@if (session('success'))
    <script>
        iziToast.success({
            message: "{{ session('success') }}",
            position: "topRight"
        });
    </script>
@endif

<script>
    $('.summernote').summernote({
        height: 200,
        disableDragAndDrop: true,
    })
    $('.select2').select2()
</script>
