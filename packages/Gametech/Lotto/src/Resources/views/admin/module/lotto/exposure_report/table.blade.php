@section('css')
	@include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
	@include('admin::layouts.datatables_js')
	{!! $dataTable->scripts() !!}
	<script>
		const syncExposureFilterUi = function (element) {
			if (!element || typeof window.jQuery !== 'function') {
				return;
			}

			const $element = window.jQuery(element);
			if ($element.hasClass('select2-hidden-accessible')) {
				$element.trigger('change.select2');
			}
		};

		$(document).on('preXhr.dt', '#dataTableBuilder', function (e, settings, data) {
			data.draw_id = $('#filter_draw_id').val() || '';
			data.market_id = $('#filter_market_id').val() || '';
			data.bet_type = $('#filter_bet_type').val() || '';
		});

		window.applyExposureFilters = function () {
			window.LaravelDataTables['dataTableBuilder'].draw();
		};

		window.resetExposureFilters = function () {
			['filter_draw_id', 'filter_market_id', 'filter_bet_type'].forEach((id) => {
				const element = document.getElementById(id);
				if (element) {
					element.value = '';
					syncExposureFilterUi(element);
				}
			});

			window.LaravelDataTables['dataTableBuilder'].draw();
		};
	</script>
@endpush
