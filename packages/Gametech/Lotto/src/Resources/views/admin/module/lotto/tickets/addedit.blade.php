<b-modal ref="addedit" id="addedit" centered size="lg" title="รายละเอียดโพย" :no-stacking="true"
		 :no-close-on-backdrop="true"
		 ok-only
		 ok-title="ปิด">
	<div v-if="ticket">
		<div class="mb-3">
			<div><strong>สมาชิก:</strong> @{{ ticket.member_name }} (@{{ ticket.member_id }})</div>
			<div><strong>งวด:</strong> @{{ ticket.draw.date }} <span v-if="ticket.draw.market">(@{{ ticket.draw.market }})</span></div>
			<div><strong>สถานะ:</strong> @{{ ticket.status }}</div>
			<div><strong>ยอดแทง:</strong> @{{ formatMoney(ticket.total_bet_amount) }}</div>
			<div><strong>ส่วนลด:</strong> @{{ formatMoney(ticket.total_discount_amount) }}</div>
			<div><strong>สุทธิ:</strong> @{{ formatMoney(ticket.total_net_amount) }}</div>
			<div><strong>ยอดถูก:</strong> @{{ formatMoney(ticket.total_win_amount) }}</div>
			<div v-if="ticket.reason"><strong>สาเหตุ:</strong> @{{ ticket.reason }}</div>
			<div v-if="ticket.cancelled_by_name"><strong>ผู้ยกเลิก:</strong> @{{ ticket.cancelled_by_name }}</div>
			<div v-if="ticket.cancelled_at"><strong>เวลายกเลิก:</strong> @{{ ticket.cancelled_at }}</div>
		</div>

		<div class="table-responsive">
			<table class="table table-striped table-sm">
				<thead>
				<tr>
					<th>ประเภทเดิมพัน</th>
					<th class="text-center">เลข</th>
					<th class="text-right">ยอดแทง</th>
					<th class="text-right">อัตราจ่าย</th>
					<th class="text-right">ส่วนลด(%)</th>
					<th class="text-right">ส่วนลด(บาท)</th>
					<th class="text-right">จ่ายจริง</th>
					<th class="text-right">ยอดถูกรางวัล(อ้างอิง)</th>
					<th class="text-center">ผล</th>
					<th class="text-right">ยอดถูก</th>
				</tr>
				</thead>
				<tbody>
				<tr v-for="(item, index) in ticket.items" :key="index">
					<td>@{{ item.bet_type }} = @{{ item.bet_type_label }}</td>
					<td class="text-center">@{{ item.number }}</td>
					<td class="text-right">@{{ formatMoney(item.amount) }}</td>
					<td class="text-right">@{{ formatMoney(item.payout_at_time) }}</td>
					<td class="text-right">@{{ formatMoney(item.discount_percent_at_time) }}</td>
					<td class="text-right">@{{ formatMoney(item.discount_amount_at_time) }}</td>
					<td class="text-right">@{{ formatMoney(item.payable_amount_at_time) }}</td>
					<td class="text-right">@{{ formatMoney(item.potential_win_amount_at_time) }}</td>
					<td class="text-center">@{{ item.result_status || '-' }}</td>
					<td class="text-right">@{{ formatMoney(item.win_amount) }}</td>
				</tr>
				<tr v-if="!ticket.items || ticket.items.length === 0">
					<td colspan="10" class="text-center text-muted">ไม่พบรายการย่อย</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>
</b-modal>
<b-modal ref="cancelTicketModal" id="cancelTicketModal" centered size="md" title="ยกเลิกโพย"
		 :no-stacking="true"
		 :no-close-on-backdrop="true"
		 :ok-disabled="cancelSubmitting || !cancelForm.reason.trim()"
		 :busy="cancelSubmitting"
		 ok-title="ยืนยันยกเลิก"
		 cancel-title="ปิด"
		 @ok.prevent="submitCancelTicket">
	<div>
		<div class="mb-3" v-if="cancelForm.id">
			<div><strong>เลขโพย:</strong> #@{{ cancelForm.id }}</div>
			<div><strong>สมาชิก:</strong> @{{ cancelForm.member_name }}</div>
			<div><strong>งวด:</strong> @{{ cancelForm.draw_label }}</div>
			<div><strong>ยอดคืน:</strong> @{{ formatMoney(cancelForm.total_net_amount) }}</div>
		</div>
		<b-form-group label="สาเหตุการยกเลิก" label-for="cancel-ticket-reason">
			<b-form-textarea
				id="cancel-ticket-reason"
				v-model="cancelForm.reason"
				rows="3"
				max-rows="6"
				placeholder="ระบุสาเหตุที่ต้องยกเลิกโพย"
			></b-form-textarea>
			<small class="text-muted">บังคับกรอก และจะถูกเก็บไว้ในรายการโพยที่ถูกยกเลิก</small>
		</b-form-group>
	</div>
</b-modal>
@push('scripts')
	<script type="module">
		const loadDataRoute = @json(route($loadDataRouteName ?? 'admin.lotto.tickets.loaddata'));
		const cancelRouteTemplate = @json(route('admin.lotto.tickets.cancel', ['id' => '__ID__']));

		window.app = new Vue({
			el: '#app',
			data() {
				return {
					ticket: null,
					cancelSubmitting: false,
					cancelForm: {
						id: null,
						reason: '',
						member_name: '',
						draw_label: '',
						total_net_amount: 0,
					},
				};
			},
			methods: {
				async editModal(id) {
					const response = await axios.post(loadDataRoute, { id });
					this.ticket = response.data.data;
					this.$refs.addedit.show();
				},
				async openCancelTicketModal(id) {
					try {
						const response = await axios.post(loadDataRoute, { id });
						const data = response?.data?.data || null;

						if (!data) {
							await this.showSubmitErrorModal(null, 'ไม่พบข้อมูลโพยดังกล่าว');
							return;
						}

						if (!data.can_cancel) {
							await this.showSubmitErrorModal(null, 'โพยนี้ไม่สามารถยกเลิกได้');
							return;
						}

						this.cancelForm = {
							id: Number(data.id || 0),
							reason: '',
							member_name: String(data.member_name || '-'),
							draw_label: [String(data.draw?.market || '').trim(), String(data.draw?.date || '').trim()].filter(Boolean).join(' / '),
							total_net_amount: Number(data.total_net_amount || 0),
						};
						this.$refs.cancelTicketModal.show();
					} catch (error) {
						await this.showSubmitErrorModal(error, 'โหลดข้อมูลโพยไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
					}
				},
				resolveCancelRoute(id) {
					return cancelRouteTemplate.replace('__ID__', String(id));
				},
				redrawTicketTable() {
					const dataTable = window.LaravelDataTables?.lottoTicketsTable;
					if (dataTable) {
						dataTable.draw(false);
					}
				},
				resolveApiErrorMessage(error, fallbackMessage) {
					const backendMessage = error?.response?.data?.message;
					if (typeof backendMessage === 'string' && backendMessage.trim() !== '') {
						return backendMessage;
					}

					return fallbackMessage;
				},
				async showSubmitErrorModal(error, fallbackMessage = 'บันทึกไม่สำเร็จ กรุณาลองใหม่อีกครั้ง') {
					const message = this.resolveApiErrorMessage(error, fallbackMessage);
					await this.$bvModal.msgBoxOk(message, {
						title: 'เกิดข้อผิดพลาด',
						size: 'sm',
						buttonSize: 'sm',
						okVariant: 'danger',
						centered: true,
					});
				},
				async submitCancelTicket() {
					const reason = String(this.cancelForm.reason || '').trim();
					if (reason === '') {
						await this.showSubmitErrorModal(null, 'กรุณาระบุสาเหตุการยกเลิกโพย');
						return;
					}

					this.cancelSubmitting = true;
					try {
						await axios.post(this.resolveCancelRoute(this.cancelForm.id), { reason });
						this.$refs.cancelTicketModal.hide();
						if (this.ticket && Number(this.ticket.id || 0) === Number(this.cancelForm.id || 0)) {
							this.ticket = null;
							this.$refs.addedit.hide();
						}
						this.redrawTicketTable();
						await this.$bvModal.msgBoxOk('ยกเลิกโพยสำเร็จ', {
							title: 'สำเร็จ',
							size: 'sm',
							buttonSize: 'sm',
							okVariant: 'success',
							centered: true,
						});
					} catch (error) {
						await this.showSubmitErrorModal(error, 'ยกเลิกโพยไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
					} finally {
						this.cancelSubmitting = false;
					}
				},
				formatMoney(value) {
					return Number(value || 0).toLocaleString('th-TH', {
						minimumFractionDigits: 2,
						maximumFractionDigits: 2,
					});
				},
			},
		});

		window.editModal = function (id) { window.app.editModal(id); };
		window.cancelTicketModal = function (id) { window.app.openCancelTicketModal(id); };
	</script>
@endpush
