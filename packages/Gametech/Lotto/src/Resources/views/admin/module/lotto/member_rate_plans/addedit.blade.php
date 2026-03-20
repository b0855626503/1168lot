<b-modal ref="addedit" id="addedit" centered size="sm" title="อัตราจ่ายสมาชิก" :no-stacking="true"
		 :no-close-on-backdrop="true"
		 :hide-footer="true">
	<b-form @submit.prevent="addEditSubmit" v-if="show">
		<b-form-group label="รหัสสมาชิก:" label-for="member_id" description="ใช้ code ของสมาชิกจากตาราง members">
			<b-form-input
				id="member_id"
				v-model="formaddedit.member_id"
				type="number"
				min="1"
				size="sm"
				placeholder="เช่น 10001"
				autocomplete="off"
				required
			></b-form-input>
		</b-form-group>
		<b-form-group label="อัตราจ่าย:" label-for="rate_plan_id">
			<b-form-select
				id="rate_plan_id"
				v-model="formaddedit.rate_plan_id"
				:options="ratePlans"
				size="sm"
				required
			></b-form-select>
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
					ratePlans: @json($ratePlanOptions ?? []),
					formaddedit: {
						member_id: null,
						rate_plan_id: null,
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
						member_id: null,
						rate_plan_id: this.ratePlans.length > 0 ? this.ratePlans[0].value : null,
					};
					this.show = false;
					this.$nextTick(() => {
						this.show = true;
						this.$refs.addedit.show();
					});
				},
				async loadData() {
					const response = await axios.post("{{ route('admin.lotto.member_rate_plans.loaddata') }}", { id: this.code });
					const d = response.data.data;
					this.formaddedit = {
						member_id: d.member_id,
						rate_plan_id: d.rate_plan_id,
					};
				},
				addEditSubmit() {
					const url = this.formmethod === 'add'
						? "{{ route('admin.lotto.member_rate_plans.create') }}"
						: "{{ route('admin.lotto.member_rate_plans.update') }}";
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
	</script>
@endpush
