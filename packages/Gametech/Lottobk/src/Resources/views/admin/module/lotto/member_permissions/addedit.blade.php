<b-modal ref="addedit" id="addedit" centered size="sm" title="สิทธิ์การเล่นสมาชิก" :no-stacking="true"
		 :no-close-on-backdrop="true"
		 :hide-footer="true">
	<b-form @submit.prevent="addEditSubmit" v-if="show">
		<b-form-group label="รหัสสมาชิก:" label-for="member_id">
			<b-form-input
				id="member_id"
				v-model="formaddedit.member_id"
				type="number"
				min="1"
				size="sm"
				placeholder="เช่น 10001"
				required
			></b-form-input>
		</b-form-group>
		<b-form-group label="กลุ่มหวย:" label-for="group_id">
			<b-form-select
				id="group_id"
				v-model="formaddedit.group_id"
				:options="groupOptions"
				size="sm"
				required
				@change="onGroupChanged"
			></b-form-select>
		</b-form-group>
		<b-form-group label="รายการหวย:" label-for="market_id">
			<b-form-select
				id="market_id"
				v-model="formaddedit.market_id"
				:options="filteredMarketOptions"
				size="sm"
				required
			></b-form-select>
		</b-form-group>
		<b-form-group>
			<b-form-checkbox v-model="formaddedit.is_allowed" :value="1" :unchecked-value="0">
				อนุญาตให้เล่น
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
					groupOptions: @json($groupOptions ?? []),
					marketOptions: @json($marketOptions ?? []),
					formaddedit: {
						member_id: null,
						group_id: null,
						market_id: null,
						is_allowed: 1,
					},
				};
			},
			computed: {
				filteredMarketOptions() {
					if (!this.formaddedit.group_id) {
						return [];
					}

					return this.marketOptions.filter((item) => parseInt(item.group_id, 10) === parseInt(this.formaddedit.group_id, 10));
				},
			},
			methods: {
				editdata(id, status, method) {
					this.$bvModal.msgBoxConfirm('ต้องการเปลี่ยนสถานะหรือไม่?', {
						title: 'ยืนยัน', size: 'sm', buttonSize: 'sm',
						okVariant: 'danger', okTitle: 'ตกลง', cancelTitle: 'ยกเลิก',
						centered: true,
					}).then(value => {
						if (!value) return;
						this.$http.post("{{ route('admin.lotto.member_permissions.edit') }}", { id, status, method })
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
						member_id: null,
						group_id: this.groupOptions.length > 0 ? this.groupOptions[0].value : null,
						market_id: null,
						is_allowed: 1,
					};
					this.onGroupChanged();
					this.show = false;
					this.$nextTick(() => {
						this.show = true;
						this.$refs.addedit.show();
					});
				},
				onGroupChanged() {
					const options = this.filteredMarketOptions;
					const exists = options.some((item) => parseInt(item.value, 10) === parseInt(this.formaddedit.market_id, 10));

					if (!exists) {
						this.formaddedit.market_id = options.length > 0 ? options[0].value : null;
					}
				},
				async loadData() {
					const response = await axios.post("{{ route('admin.lotto.member_permissions.loaddata') }}", { id: this.code });
					const d = response.data.data;
					this.formaddedit = {
						member_id: d.member_id,
						group_id: d.group_id,
						market_id: d.market_id,
						is_allowed: d.is_allowed ? 1 : 0,
					};
					this.onGroupChanged();
				},
				addEditSubmit() {
					const url = this.formmethod === 'add'
						? "{{ route('admin.lotto.member_permissions.create') }}"
						: "{{ route('admin.lotto.member_permissions.update') }}";
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
