<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
<script src="{{ asset('club_panel/assets/app.js') }}"></script>

@if (session()->has('notify'))
    @foreach (session('notify') as $msg)
        <script>
            iziToast.{{ $msg[0] }}({ message: "{{ __($msg[1]) }}", position: "topRight" });
        </script>
    @endforeach
@endif

@if (session()->has('errors'))
    @foreach (session('errors')->all() as $error)
        <script>
            iziToast.error({ message: '{{ __($error) }}', position: "topRight" });
        </script>
    @endforeach
@endif

@if (session('error'))
    <script>
        iziToast.error({ message: '{{ session("error") }}', position: "topRight" });
    </script>
@endif

@if (session('success'))
    <script>
        iziToast.success({ message: "{{ session('success') }}", position: "topRight" });
    </script>
@endif
