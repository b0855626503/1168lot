<b-modal ref="addedit" id="addedit" centered size="lg" title="รายละเอียดโพย" :no-stacking="true"
		 :no-close-on-backdrop="true"
		 ok-only
		 ok-title="ปิด">
	<div v-if="ticket">
		<div class="mb-3">
			<div><strong>สมาชิก:</strong> @{{ ticket.member_name }} (@{{ ticket.member_id }})</div>
			<div><strong>งวด:</strong> @{{ ticket.draw.date }} <span v-if="ticket.draw.market">(@{{ ticket.draw.market }})</span></div>
			<div><strong>สถานะ:</strong> @{{ ticket.status }}</div>
			<div><strong>ยอดแทง:</strong> @{{ formatMoney(ticket.total_amount) }}</div>
			<div><strong>ยอดถูก:</strong> @{{ formatMoney(ticket.total_win_amount) }}</div>
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
@push('scripts')
	<script type="module">
		window.app = new Vue({
			el: '#app',
			data() {
				return {
					ticket: null,
				};
			},
			methods: {
				async editModal(id) {
					const response = await axios.post("{{ route('admin.lotto.tickets.loaddata') }}", { id });
					this.ticket = response.data.data;
					this.$refs.addedit.show();
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
	</script>
@endpush
