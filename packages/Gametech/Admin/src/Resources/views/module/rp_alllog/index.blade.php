@extends('admin::layouts.master')

{{-- page title --}}
@section('title')
    {{ $menu->currentName }}
@endsection


@section('content')
    <section class="content text-xs">

        <div class="row">
            <div class="col-12">
                <div class="card card-primary">

                    <form id="frmsearch" method="post" onsubmit="return false;">
                        <div class="card-body">
                            <div class="row">
                                <div class="form-group col-12">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-clock"></i></span>
                                        </div>
                                        <input type="text" class="form-control form-control-sm float-right"
                                               id="search_date" readonly>
                                        <input type="hidden" class="form-control float-right" id="startDate"
                                               name="startDate">
                                        <input type="hidden" class="form-control float-right" id="endDate"
                                               name="endDate">
                                    </div>
                                </div>

                                <div class="form-group col-6">
                                    @if($webconfig->seamless == 'Y')
                                        {!! Form::select('kind',  ['' => '== ประเภท ==' , 'PROMOTION' => 'รับโปร' , 'TOPUP' => 'ฝากเงิน' , 'WITHDRAW' => 'แจ้งถอนเงิน' , 'SETWALLET' => 'เพิ่มยอดโดยทีมงาน' , 'FASTSTART' => 'แนะนำเพื่อน (มอบโดยระบบ)', 'FASTSTARTS' => 'แนะนำเพื่อน (แจ้งเตือน)' , 'ROLLBACK' => 'คืนยอด', 'FREE' => 'รับเครดิตฟรี'], '',['id' => 'kind', 'class' => 'form-control form-control-sm']) !!}
                                    @else
                                        {!! Form::select('kind',  ['' => '== ประเภท ==' , 'TRANSFER' => 'โยกเงิน' , 'TOPUP' => 'ฝากเงิน' , 'WITHDRAW' => 'แจ้งถอนเงิน' , 'SETWALLET' => 'เพิ่มยอดโดยทีมงาน' , 'FASTSTART' => 'แนะนำเพื่อน (มอบโดยระบบ)', 'FASTSTARTS' => 'แนะนำเพื่อน (แจ้งเตือน)' , 'ROLLBACK' => 'คืนยอด', 'FREE' => 'รับเครดิตฟรี', 'MONEY' => 'ฝาก - ถอน', 'CASHBACK' => 'การคำนวนยอดเสีย'], '',['id' => 'kind', 'class' => 'form-control form-control-sm']) !!}

                                    @endif
                                </div>

                                <div class="form-group col-6">
                                    {!! Form::select('game_code', (['' => '== เกมส์ ==']+$games->toArray()), '',['id' => 'game_code', 'class' => 'form-control form-control-sm']) !!}

                                </div>

                                <div class="form-group col-6">
                                    <input type="text" class="form-control form-control-sm" id="user_name"
                                           placeholder="Username"
                                           name="user_name">
                                </div>
                                <div class="form-group col-6">
                                    <input type="text" class="form-control form-control-sm" id="member_code"
                                           placeholder="Code สมาชิก"
                                           name="member_code">
                                </div>
                                <div class="form-group col-6">

                                </div>

                                <div class="form-group col-auto">
                                    <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
                <!-- /.info-box -->
            </div>
        </div>

        <div class="card">

            <div class="card-body">
                @includeIf('admin::module.'.$menu->currentRoute.'.create')
                @include('admin::module.'.$menu->currentRoute.'.table')
                @includeIf('admin::module.'.$menu->currentRoute.'.addedit')
            </div>
            <!-- /.card-body -->
        </div>
    </section>

@endsection

