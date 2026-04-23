<b-modal ref="addedit" id="addedit" centered size="sm" title="งวดหวย" :no-stacking="true"
		 :no-close-on-backdrop="true"
		 :hide-footer="true">
	<b-form @submit.prevent="addEditSubmit" v-if="show">
		<b-form-group label="ตลาด:" label-for="market_id">
			<b-form-select id="market_id" v-model="formaddedit.market_id" :options="markets" size="sm" required></b-form-select>
		</b-form-group>
		<b-form-group label="วันงวด:" label-for="draw_date">
			<b-form-input id="draw_date" v-model="formaddedit.draw_date" type="date" size="sm" required></b-form-input>
		</b-form-group>
		<b-form-group label="เปิดรับ:" label-for="open_at">
			<b-form-input id="open_at" v-model="formaddedit.open_at" type="text" size="sm" required placeholder="YYYY-MM-DD HH:mm" autocomplete="off"></b-form-input>
		</b-form-group>
		<b-form-group label="ปิดรับ:" label-for="close_at">
			<b-form-input id="close_at" v-model="formaddedit.close_at" type="text" size="sm" required placeholder="YYYY-MM-DD HH:mm" autocomplete="off"></b-form-input>
		</b-form-group>
		<b-form-group label="สถานะ:" label-for="status">
			<b-form-select id="status" v-model="formaddedit.status" :options="statusOptions" size="sm" required></b-form-select>
		</b-form-group>
		<b-form-group label="ผล 3 ตัวบน:" label-for="result_top_3">
			<b-form-input id="result_top_3" v-model="formaddedit.result_number.top_3" type="text" maxlength="3" size="sm" placeholder="เช่น 123"></b-form-input>
		</b-form-group>
		<b-form-group label="ผล 2 ตัวล่าง:" label-for="result_bottom_2">
			<b-form-input id="result_bottom_2" v-model="formaddedit.result_number.bottom_2" type="text" maxlength="2" size="sm" placeholder="เช่น 45"></b-form-input>
		</b-form-group>
		<b-form-group label="ประกาศผลเมื่อ:" label-for="result_at">
			<b-form-input id="result_at" v-model="formaddedit.result_at" type="text" size="sm" placeholder="YYYY-MM-DD HH:mm" autocomplete="off"></b-form-input>
		</b-form-group>
		<b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
	</b-form>
</b-modal>
@push('scripts')
	<script type="module">
		const toDateTimeValue = (value) => {
			if (!value) return '';
			return String(value).replace('T', ' ').substring(0, 16);
		};

		const toDateTimePayload = (value) => {
			if (!value) return null;
			const normalized = String(value).trim().replace('T', ' ').substring(0, 16);
			return `${normalized}:00`;
		};

		window.app = new Vue({
			el: '#app',
			data() {
				return {
					show: true,
					code: null,
					formmethod: 'add',
					markets: @json($marketOptions ?? []),
					statusOptions: [
						{ value: 'draft', text: 'ร่าง' },
						{ value: 'open', text: 'เปิดรับ' },
						{ value: 'closed', text: 'ปิดรับ' },
					],
					formaddedit: {
						market_id: null,
						draw_date: '',
						open_at: '',
						close_at: '',
						status: 'draft',
						result_number: {
							top_3: '',
							bottom_2: '',
						},
						result_at: '',
					},
				};
			},
			methods: {
				initDateTimePickers() {
					const bindings = [
						{ id: '#open_at', field: 'open_at' },
						{ id: '#close_at', field: 'close_at' },
						{ id: '#result_at', field: 'result_at' },
					];

					bindings.forEach(({ id, field }) => {
						const $input = window.jQuery(id);
						if (!$input.length) {
							return;
						}

						if ($input.data('datetimepicker')) {
							$input.datetimepicker('destroy');
						}

						$input.datetimepicker({
							format: 'YYYY-MM-DD HH:mm',
							stepping: 1,
							useCurrent: false,
							icons: {
								time: 'far fa-clock',
								date: 'far fa-calendar',
								up: 'fas fa-chevron-up',
								down: 'fas fa-chevron-down',
								previous: 'fas fa-chevron-left',
								next: 'fas fa-chevron-right',
								today: 'far fa-calendar-check',
								clear: 'far fa-trash-alt',
								close: 'far fa-times-circle',
							},
						});

						$input.off('change.datetimepicker');
						$input.on('change.datetimepicker', (event) => {
							this.formaddedit[field] = event.target.value || '';
						});

						$input.datetimepicker('date', this.formaddedit[field] ? window.moment(this.formaddedit[field], 'YYYY-MM-DD HH:mm') : null);
					});
				},
				editModal(id) {
					this.code = id;
					this.formmethod = 'edit';
					this.show = false;
					this.$nextTick(() => {
						this.show = true;
						this.loadData();
						this.initDateTimePickers();
						this.$refs.addedit.show();
					});
				},
				addModal() {
					this.code = null;
					this.formmethod = 'add';
					this.formaddedit = {
						market_id: this.markets.length > 0 ? this.markets[0].value : null,
						draw_date: '',
						open_at: '',
						close_at: '',
						status: 'draft',
						result_number: {
							top_3: '',
							bottom_2: '',
						},
						result_at: '',
					};
					this.show = false;
					this.$nextTick(() => {
						this.show = true;
						this.initDateTimePickers();
						this.$refs.addedit.show();
					});
				},
				settleModal(id) {
					this.code = id;
					this.formmethod = 'settle';
					this.show = false;
					this.$nextTick(() => {
						this.show = true;
						this.loadData();
						this.initDateTimePickers();
						this.$refs.addedit.show();
					});
				},
				async loadData() {
					const response = await axios.post("{{ route('admin.lotto.draws.loaddata') }}", { id: this.code });
					const d = response.data.data;
					this.formaddedit = {
						market_id: d.market_id,
						draw_date: d.draw_date ? String(d.draw_date).substring(0, 10) : '',
						open_at: toDateTimeValue(d.open_at),
						close_at: toDateTimeValue(d.close_at),
						status: d.status || 'draft',
						result_number: {
							top_3: d.result_number?.top_3 || '',
							bottom_2: d.result_number?.bottom_2 || '',
						},
						result_at: toDateTimeValue(d.result_at),
					};

					this.$nextTick(() => this.initDateTimePickers());
				},
				addEditSubmit() {
					const shouldSettle = this.formmethod === 'settle'
						&& this.formaddedit.result_number.top_3
						&& this.formaddedit.result_number.bottom_2;

					const payload = {
						market_id: this.formaddedit.market_id,
						draw_date: this.formaddedit.draw_date,
						open_at: toDateTimePayload(this.formaddedit.open_at),
						close_at: toDateTimePayload(this.formaddedit.close_at),
						status: shouldSettle ? 'resulted' : this.formaddedit.status,
						result_number: {
							top_3: this.formaddedit.result_number.top_3,
							bottom_2: this.formaddedit.result_number.bottom_2,
						},
						result_at: toDateTimePayload(this.formaddedit.result_at),
					};

					const url = this.formmethod === 'add'
						? "{{ route('admin.lotto.draws.create') }}"
						: (shouldSettle
							? "{{ route('admin.lotto.draws.settle') }}"
							: "{{ route('admin.lotto.draws.update') }}");

					this.$http.post(url, { id: this.code, data: payload })
						.then(response => {
							this.$bvModal.msgBoxOk(response.data.message, {
								title: 'ผลการดำเนินการ',
								size: 'sm',
								buttonSize: 'sm',
								okVariant: 'success',
								centered: true,
							});
							this.$refs.addedit.hide();
							window.LaravelDataTables['dataTableBuilder'].draw(false);
						});
				},
				openDraw(id) {
					this.$http.post("{{ route('admin.lotto.draws.open') }}", { id })
						.then(response => {
							this.$bvModal.msgBoxOk(response.data.message, {
								title: 'ผลการดำเนินการ',
								size: 'sm',
								buttonSize: 'sm',
								okVariant: 'success',
								centered: true,
							});
							window.LaravelDataTables['dataTableBuilder'].draw(false);
						});
				},
				closeDraw(id) {
					this.$http.post("{{ route('admin.lotto.draws.close') }}", { id })
						.then(response => {
							this.$bvModal.msgBoxOk(response.data.message, {
								title: 'ผลการดำเนินการ',
								size: 'sm',
								buttonSize: 'sm',
								okVariant: 'success',
								centered: true,
							});
							window.LaravelDataTables['dataTableBuilder'].draw(false);
						});
				},
			},
		});

		window.addModal = function () { window.app.addModal(); };
		window.editModal = function (id) { window.app.editModal(id); };
		window.settleModal = function (id) { window.app.settleModal(id); };
		window.openDraw = function (id) { window.app.openDraw(id); };
		window.closeDraw = function (id) { window.app.closeDraw(id); };
	</script>
@endpush
