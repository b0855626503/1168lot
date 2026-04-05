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

		$(function () {
			const redrawExposureTable = function () {
				if (!window.LaravelDataTables || !window.LaravelDataTables['dataTableBuilder']) {
					return;
				}

				window.LaravelDataTables['dataTableBuilder'].draw(false);
			};

			$(document).off('preXhr.dt.exposureFilter', '#dataTableBuilder').on('preXhr.dt.exposureFilter', '#dataTableBuilder', function (_e, _settings, data) {
				data.draw_id = $('#filter_draw_id').val() || '';
				data.market_id = $('#filter_market_id').val() || '';
				data.bet_type = $('#filter_bet_type').val() || '';
			});

			$('#filter_draw_id, #filter_market_id, #filter_bet_type')
				.off('change.exposureFilter')
				.on('change.exposureFilter', function () {
					redrawExposureTable();
				});

			window.resetExposureFilters = function () {
				['filter_draw_id', 'filter_market_id', 'filter_bet_type'].forEach((id) => {
					const element = document.getElementById(id);
					if (element) {
						element.value = '';
						syncExposureFilterUi(element);
					}
				});

				redrawExposureTable();
			};
		});
	</script>
@endpush
