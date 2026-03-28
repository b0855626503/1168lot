@section('css')
	@include('admin::layouts.datatables_css')
@endsection
{!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped table-sm']) !!}
@push('scripts')
	@include('admin::layouts.datatables_js')
	{!! $dataTable->scripts() !!}
	<script>
		$(function () {
			const tableSelector = '#dataTableBuilder';
			const tableKey = 'dataTableBuilder';
			const $drawDateInput = $('#filter_draw_date');
			const $marketSelect = $('#filter_market_id');
			const $betTypeSelect = $('#filter_bet_type');
			const $numberInput = $('#filter_number_search');
			const $bulkDeleteBtn = $('#bulk-delete-number-blocks-btn');
			let numberTypingTimer = null;

			const getSelectedIds = function () {
				return Array.from(document.querySelectorAll('.js-lotto-row-selector-number-blocks:checked'))
					.map((el) => Number(el.value))
					.filter((id) => Number.isInteger(id) && id > 0);
			};

			const redrawTable = function () {
				if (!window.LaravelDataTables || !window.LaravelDataTables[tableKey]) {
					return;
				}
				window.LaravelDataTables[tableKey].draw(false);
			};

			const refreshBulkDeleteButton = function () {
				const selectedCount = getSelectedIds().length;
				if (selectedCount > 0) {
					$bulkDeleteBtn.removeClass('d-none').text('ลบที่เลือก (' + selectedCount + ')');
				} else {
					$bulkDeleteBtn.addClass('d-none').text('ลบที่เลือก');
				}
			};

			const ensureSelectAllHeader = function () {
				const th = document.querySelector('#dataTableBuilder thead th:first-child');
				if (!th) {
					return;
				}
				if (!th.querySelector('.js-lotto-select-all-number-blocks')) {
					th.innerHTML = '<input type="checkbox" class="js-lotto-select-all-number-blocks" />';
				}
			};

			$(document).off('preXhr.dt.lottoNumberBlocksFilter', tableSelector).on('preXhr.dt.lottoNumberBlocksFilter', tableSelector, function (_event, _settings, data) {
				const marketValue = String($marketSelect.val() || '');
				const isGroupValue = marketValue.indexOf('group:') === 0;
				data.draw_date = $drawDateInput.val() || '';
				data.market_id = isGroupValue ? '' : marketValue;
				data.group_id = isGroupValue ? marketValue.replace('group:', '') : '';
				data.bet_type = $betTypeSelect.val() || '';
				data.number_search = ($numberInput.val() || '').trim();
			});

			$drawDateInput.off('change.lottoNumberBlocksFilter').on('change.lottoNumberBlocksFilter', function () {
				redrawTable();
			});

			$marketSelect.off('change.lottoNumberBlocksFilter').on('change.lottoNumberBlocksFilter', function () {
				redrawTable();
			});

			$betTypeSelect.off('change.lottoNumberBlocksFilter').on('change.lottoNumberBlocksFilter', function () {
				redrawTable();
			});

			$numberInput.off('input.lottoNumberBlocksFilter').on('input.lottoNumberBlocksFilter', function () {
				if (numberTypingTimer) {
					clearTimeout(numberTypingTimer);
				}
				numberTypingTimer = setTimeout(function () {
					redrawTable();
				}, 250);
			});

			$(document).on('change', '.js-lotto-select-all-number-blocks', function () {
				const checked = $(this).is(':checked');
				document.querySelectorAll('.js-lotto-row-selector-number-blocks').forEach((checkbox) => {
					checkbox.checked = checked;
				});
				refreshBulkDeleteButton();
			});

			$(document).on('change', '.js-lotto-row-selector-number-blocks', function () {
				const total = document.querySelectorAll('.js-lotto-row-selector-number-blocks').length;
				const checked = document.querySelectorAll('.js-lotto-row-selector-number-blocks:checked').length;
				const selectAll = document.querySelector('.js-lotto-select-all-number-blocks');
				if (selectAll) {
					selectAll.checked = total > 0 && total === checked;
				}
				refreshBulkDeleteButton();
			});

			$(document).on('draw.dt', tableSelector, function () {
				ensureSelectAllHeader();
				refreshBulkDeleteButton();
			});

			$bulkDeleteBtn.on('click', function () {
				const ids = getSelectedIds();
				if (!ids.length) {
					return;
				}
				if (window.app && typeof window.app.bulkDeleteModal === 'function') {
					window.app.bulkDeleteModal(ids);
				}
			});

			ensureSelectAllHeader();
		});
	</script>
@endpush
