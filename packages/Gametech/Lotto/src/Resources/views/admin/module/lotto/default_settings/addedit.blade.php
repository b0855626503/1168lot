<b-modal ref="addedit" id="addedit" centered size="sm" title="ค่าพื้นฐานหวย" :no-stacking="true"
		 :no-close-on-backdrop="true"
		 :hide-footer="true">
	<b-form @submit.prevent="addEditSubmit" v-if="show">
		<b-form-group label="ตลาด:" label-for="market_id">
			<b-form-select
				id="market_id"
				v-model="formaddedit.market_id"
				:options="markets"
				size="sm"
				required
			></b-form-select>
		</b-form-group>
		<b-form-group label="ประเภทเดิมพัน:" label-for="bet_type">
			<b-form-select
				id="bet_type"
				v-model="formaddedit.bet_type"
				:options="betTypes"
				size="sm"
				required
			></b-form-select>
		</b-form-group>
		<b-form-group label="อัตราจ่าย:" label-for="payout">
			<b-form-input id="payout" v-model="formaddedit.payout" type="number" step="0.01" min="0" size="sm" required></b-form-input>
		</b-form-group>
		<b-form-group label="ขั้นต่ำ:" label-for="min_bet">
			<b-form-input id="min_bet" v-model="formaddedit.min_bet" type="number" step="0.01" min="0" size="sm" required></b-form-input>
		</b-form-group>
		<b-form-group label="สูงสุด:" label-for="max_bet">
			<b-form-input id="max_bet" v-model="formaddedit.max_bet" type="number" step="0.01" min="0" size="sm" required></b-form-input>
		</b-form-group>
		<b-form-group label="สูงสุดสะสมต่อเลข:" label-for="max_per_number">
			<b-form-input id="max_per_number" v-model="formaddedit.max_per_number" type="number" step="0.01" min="0" size="sm" required></b-form-input>
		</b-form-group>
		<b-form-group>
			<b-form-checkbox v-model="formaddedit.is_enabled" :value="1" :unchecked-value="0">
				เปิดใช้งาน
			</b-form-checkbox>
		</b-form-group>
		<b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
	</b-form>
</b-modal>
@push('scripts')
	<script type="module">
		window.app = new Vue({
			el: '#app',
			data() {
				return {
					show: true,
					code: null,
					formmethod: 'add',
					markets: @json($marketOptions ?? []),
					betTypes: @json($betTypeOptions ?? []),
					formaddedit: {
						market_id: null,
						bet_type: null,
						payout: 0,
						min_bet: 0,
						max_bet: 0,
						max_per_number: 0,
						is_enabled: 1,
					},
				};
			},
			methods: {
				editdata(id, status, method) {
					this.$bvModal.msgBoxConfirm('ต้องการเปลี่ยนสถานะหรือไม่?', {
						title: 'ยืนยัน', size: 'sm', buttonSize: 'sm',
						okVariant: 'danger', okTitle: 'ตกลง', cancelTitle: 'ยกเลิก',
						centered: true,
					}).then(value => {
						if (!value) return;
						this.$http.post("{{ route('admin.lotto.default_settings.edit') }}", { id, status, method })
							.then(() => {
								window.LaravelDataTables['dataTableBuilder'].draw(false);
							});
					});
				},
				editModal(id) {
					this.code = id;
					this.formmethod = 'edit';
					this.show = false;
					this.$nextTick(() => {
						this.show = true;
						this.loadData();
						this.$refs.addedit.show();
					});
				},
				addModal() {
					this.code = null;
					this.formmethod = 'add';
					this.formaddedit = {
						market_id: this.markets.length > 0 ? this.markets[0].value : null,
						bet_type: this.betTypes.length > 0 ? this.betTypes[0].value : null,
						payout: 0,
						min_bet: 0,
						max_bet: 0,
						max_per_number: 0,
						is_enabled: 1,
					};
					this.show = false;
					this.$nextTick(() => {
						this.show = true;
						this.$refs.addedit.show();
					});
				},
				async loadData() {
					const response = await axios.post("{{ route('admin.lotto.default_settings.loaddata') }}", { id: this.code });
					const d = response.data.data;
					this.formaddedit = {
						market_id: d.market_id,
						bet_type: d.bet_type,
						payout: d.payout,
						min_bet: d.min_bet,
						max_bet: d.max_bet,
						max_per_number: d.max_per_number,
						is_enabled: d.is_enabled ? 1 : 0,
					};
				},
				addEditSubmit() {
					const url = this.formmethod === 'add'
						? "{{ route('admin.lotto.default_settings.create') }}"
						: "{{ route('admin.lotto.default_settings.update') }}";
					this.$http.post(url, { id: this.code, data: this.formaddedit })
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
			},
		});

		window.addModal = function () { window.app.addModal(); };
		window.editModal = function (id) { window.app.editModal(id); };
		window.editdata = function (id, status, method) { window.app.editdata(id, status, method); };
	</script>
@endpush
