<script type="text/x-template" id="member-menu-template">
	<div class="container-fluid mt-3">
		<div class="member__menubox flex-column-reversep-3 mx-auto p-3">
			<div id="member-block" style="position: relative; z-index: 1;">
				<div class="row g-2">
					<div class="col-4">
						<button
								class="btn btn-outline-secondary btn-lg btn-menu position-relative"
								@click="$emit('open-deposit')"
						>
							<img src="/assets/kimberbet/images/icon/member_menu_deposit.svg" class="icon">
							@{{ trans('app.home.deposit') }}
						</button>
					</div>

					<div class="col-4">
						<button
								class="btn btn-outline-secondary btn-lg btn-menu position-relative"
								@click="$emit('open-withdraw')"
						>
							<img src="/assets/kimberbet/images/icon/member_menu_withdraw.svg" class="icon">
							@{{ trans('app.home.withdraw') }}
						</button>
					</div>

					<div class="col-4">
						<a href="{{ route('customer.history.index') }}"
						   class="btn btn-outline-secondary btn-lg btn-menu">
							<img src="/assets/kimberbet/images/icon/member_menu_history.svg" class="icon">
							@{{ trans('app.home.history') }}
						</a>
					</div>
				</div>

				<div class="row g-2 mt-2">
					<div class="col-4">
						<a href="{{ route('customer.contributor.index') }}"
						   class="btn btn-outline-secondary btn-lg btn-menu">
							<img src="/assets/kimberbet/images/icon/member_menu_recommend.svg" class="icon">
							@{{ trans('app.home.suggest') }}
						</a>
					</div>

					<div class="col-4">
						<button
								class="btn btn-outline-secondary btn-lg btn-menu position-relative"
								@click="$emit('open-event')"
						>
							<img src="/assets/kimberbet/images/icon/member_menu_event.svg" class="icon">
							@{{ trans('app.home.event') }}
						</button>
					</div>

					<div class="col-4">
						<button
								class="btn btn-outline-secondary btn-lg btn-menu position-relative"
								@click="$emit('open-bonus')"
						>
							<img src="/assets/kimberbet/images/icon/member_menu_bonuss.svg" class="icon">
							@{{ trans('app.home.bonus') }}
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>

@push('components')
	<script type="module">
		// ป้องกันกรณี window.i18n ยังไม่ถูกเซ็ต (กัน error ช่วงแรก)
		window.i18n = window.i18n || {};

		Vue.component('member-menu', {
			template: '#member-menu-template',
		});
	</script>
@endpush
