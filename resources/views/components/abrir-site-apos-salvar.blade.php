@if (session('abrir_site_url'))
    <script>
        (function () {
            const url = @js(session('abrir_site_url'));
            const win = window.open(url, 'memorias_ver_no_site');

            if (win) {
                win.opener = null;
                win.focus();
            }
        })();
    </script>
@endif
