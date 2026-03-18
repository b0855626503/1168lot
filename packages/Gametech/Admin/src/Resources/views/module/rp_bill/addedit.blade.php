@push('scripts')

    <script type="module">
        window.app = new Vue({
            el: '#app',

            created() {
                this.audio = document.getElementById('alertsound');
                this.autoCnt(false);
            }
        });

    </script>
@endpush

