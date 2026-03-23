<b-modal ref="addedit" id="addedit" centered size="sm" title="เลขอั้น" :no-stacking="true"
		 :no-close-on-backdrop="true"
		 :hide-footer="true">
	<b-form @submit.prevent="addEditSubmit" v-if="show">
		<b-form-group label="งวดหวย:" label-for="draw_id">
			<b-form-select id="draw_id" v-model="formaddedit.draw_id" :options="draws" size="sm" required></b-form-select>
		</b-form-group>
		<b-form-group label="ประเภทเดิมพัน:" label-for="bet_type">
			<b-form-select id="bet_type" v-model="formaddedit.bet_type" :options="betTypes" size="sm" required></b-form-select>
		</b-form-group>
		<b-form-group label="เลข:" label-for="number" description="กรอกหลายเลขได้ โดยคั่นด้วย , เช่น 12,34,56">
			<b-form-input id="number" v-model="formaddedit.number" type="text" size="sm" autocomplete="off" placeholder="เช่น 12,34,56" required></b-form-input>
		</b-form-group>
		<b-form-group label="โหมด:" label-for="mode">
			<b-form-select id="mode" v-model="formaddedit.mode" :options="modeOptions" size="sm" required></b-form-select>
		</b-form-group>
		<b-form-group label="เหตุผล:" label-for="reason">
			<b-form-textarea id="reason" v-model="formaddedit.reason" rows="2" size="sm"></b-form-textarea>
		</b-form-group>
		<b-form-group label="เวลาอั้น:" label-for="blocked_at">
			<b-form-input id="blocked_at" v-model="formaddedit.blocked_at" type="datetime-local" size="sm"></b-form-input>
		</b-form-group>
		<b-button type="submit" variant="primary" size="sm">บันทึก</b-button>
	</b-form>
</b-modal>
@push('scripts')
	<script type="module">
		const toDateTimeLocal = (value) => {
			if (!value) return '';
			return String(value).replace(' ', 'T').substring(0, 16);
		};

		window.app = new Vue({
			el: '#app',
			data() {
				return {
					show: true,
					code: null,
					formmethod: 'add',
					draws: @json($drawOptions ?? []),
					betTypes: @json($betTypeOptions ?? []),
					modeOptions: [
						{ value: 'block', text: 'อั้น' },
						{ value: 'limit_future', text: 'จำกัดอนาคต' },
					],
					formaddedit: {
						draw_id: null,
						bet_type: null,
						number: '',
						mode: 'block',
						reason: '',
						blocked_at: '',
					},
				};
			},
			methods: {
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
						draw_id: this.draws.length > 0 ? this.draws[0].value : null,
						bet_type: this.betTypes.length > 0 ? this.betTypes[0].value : null,
						number: '',
						mode: 'block',
						reason: '',
						blocked_at: '',
					};
					this.show = false;
					this.$nextTick(() => {
						this.show = true;
						this.$refs.addedit.show();
					});
				},
				async loadData() {
					const response = await axios.post("{{ route('admin.lotto.number_blocks.loaddata') }}", { id: this.code });
					const d = response.data.data;
					this.formaddedit = {
						draw_id: d.draw_id,
						bet_type: d.bet_type,
						number: d.number,
						mode: d.mode,
						reason: d.reason || '',
						blocked_at: toDateTimeLocal(d.blocked_at),
					};
				},
				addEditSubmit() {
					const payload = {
						draw_id: this.formaddedit.draw_id,
						bet_type: this.formaddedit.bet_type,
						number: this.formaddedit.number,
						mode: this.formaddedit.mode,
						reason: this.formaddedit.reason,
						blocked_at: this.formaddedit.blocked_at ? this.formaddedit.blocked_at.replace('T', ' ') : null,
					};

					const url = this.formmethod === 'add'
						? "{{ route('admin.lotto.number_blocks.create') }}"
						: "{{ route('admin.lotto.number_blocks.update') }}";

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
			},
		});

		window.addModal = function () { window.app.addModal(); };
		window.editModal = function (id) { window.app.editModal(id); };
	</script>
@endpush
