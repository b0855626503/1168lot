@extends('admin::layouts.master')

@section('title')
    {{ $menu->currentName }}
@endsection

@section('content')
    <section class="content text-sm">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $menu->currentName }}</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.lotto.reports.results_by_date') }}" class="mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-3 col-sm-6">
                                <label class="mb-1">วันที่งวด</label>
                                <input
                                    type="date"
                                    name="draw_date"
                                    value="{{ $drawDate }}"
                                    class="form-control form-control-sm"
                                >
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <button type="submit" class="btn btn-primary btn-sm mt-3 mt-sm-0">
                                    ค้นหา
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="mb-3">
                        <span class="badge badge-info">วันที่: {{ $drawDate }}</span>
                        <span class="badge badge-secondary">กลุ่มหวย: {{ $summary['group_count'] }}</span>
                        <span class="badge badge-secondary">รายการหวย: {{ $summary['market_count'] }}</span>
                        <span class="badge badge-success">มีผลแล้ว: {{ $summary['result_count'] }}</span>
                        <span class="text-muted ml-2">อัปเดตล่าสุด {{ $serverTime }}</span>
                    </div>

                    @forelse($groups as $group)
                        <div class="card card-outline card-secondary mb-3">
                            <div class="card-header py-2">
                                <h4 class="card-title mb-0">
                                    {{ $group['group_name'] }}
                                    @if(!empty($group['group_code']))
                                        <small class="text-muted">({{ $group['group_code'] }})</small>
                                    @endif
                                </h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead>
                                        <tr>
                                            <th style="width: 18%">รายการหวย</th>
                                            <th style="width: 10%">งวด</th>
                                            <th style="width: 14%">เวลาออกผล</th>
                                            <th style="width: 14%">รางวัลที่ 1</th>
                                            <th style="width: 12%">3 ตัวบน</th>
                                            <th style="width: 12%">2 ตัวบน</th>
                                            <th style="width: 12%">2 ตัวล่าง</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($group['markets'] as $market)
                                            <tr>
                                                <td>
                                                    @if(!empty($market['market_logo']))
                                                        <img
                                                            src="{{ $market['market_logo'] }}"
                                                            alt="{{ $market['market_name'] }}"
                                                            style="width:22px;height:22px;object-fit:cover;border-radius:4px;margin-right:6px;"
                                                        >
                                                    @endif
                                                    {{ $market['market_name'] }}
                                                </td>
                                                <td>{{ $market['draw_date'] }}</td>
                                                <td>{{ $market['result_at'] }}</td>
                                                <td>{{ $market['first_prize'] !== '' ? $market['first_prize'] : '-' }}</td>
                                                <td>{{ $market['top_3'] !== '' ? $market['top_3'] : '-' }}</td>
                                                <td>{{ $market['top_2'] !== '' ? $market['top_2'] : '-' }}</td>
                                                <td>{{ $market['bottom_2'] !== '' ? $market['bottom_2'] : '-' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-warning mb-0">
                            ไม่พบผลรางวัลที่ออกแล้วในวันที่ {{ $drawDate }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection

