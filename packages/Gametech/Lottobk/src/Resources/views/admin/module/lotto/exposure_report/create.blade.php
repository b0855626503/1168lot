<div class="row mb-2 align-items-end">
	<div class="col-md-3">
		<label class="mb-1">งวดหวย</label>
		<select id="filter_draw_id" class="form-control form-control-sm">
			<option value="">ทั้งหมด</option>
			@foreach(($drawOptions ?? []) as $option)
				<option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
			@endforeach
		</select>
	</div>
	<div class="col-md-3">
		<label class="mb-1">รายการหวย</label>
		<select id="filter_market_id" class="form-control form-control-sm">
			<option value="">ทั้งหมด</option>
			@foreach(($marketOptions ?? []) as $option)
				<option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
			@endforeach
		</select>
	</div>
	<div class="col-md-3">
		<label class="mb-1">ประเภทเดิมพัน</label>
		<select id="filter_bet_type" class="form-control form-control-sm">
			<option value="">ทั้งหมด</option>
			@foreach(($betTypeOptions ?? []) as $option)
				<option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
			@endforeach
		</select>
	</div>
	<div class="col-md-3 text-right">
		<button type="button" class="btn bg-gradient-primary btn-xs" onclick="applyExposureFilters()">
			<i class="fa fa-search"></i> ค้นหา
		</button>
		<button type="button" class="btn bg-gradient-secondary btn-xs" onclick="resetExposureFilters()">
			<i class="fa fa-refresh"></i> ล้างค่า
		</button>
	</div>
	<div class="col-12 mt-2 text-muted">
		แสดงยอดรับสะสมต่อเลขจากข้อมูลจริงในตาราง exposure ของแต่ละงวด
	</div>
</div>
