@section('css')
    @include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
    @include('admin::layouts.datatables_js')
    {!! $dataTable->scripts() !!}
    <script>
        window.getSelectedMarketIds = function () {
            return Array.from(document.querySelectorAll('.js-lotto-row-selector-markets:checked'))
                .map((item) => parseInt(item.value, 10))
                .filter((id) => Number.isInteger(id) && id > 0);
        };

        document.addEventListener('change', function (event) {
            if (!event.target.classList.contains('js-lotto-select-all-markets')) {
                return;
            }

            const checked = !!event.target.checked;
            document.querySelectorAll('.js-lotto-row-selector-markets').forEach((checkbox) => {
                checkbox.checked = checked;
            });
        });
    </script>
@endpush
