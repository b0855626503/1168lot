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


                                {{--                            <div class="form-group col-6">--}}
                                {{--                                {!! Form::select('account_code',  (['' => '== ธนาคาร ==']+$banks->toArray()), '',['id' => 'account_code', 'class' => 'form-control form-control-sm']) !!}--}}

                                {{--                            </div>--}}

                                <div class="form-group col-6">
                                    <input type="text" class="form-control form-control-sm"
                                           id="keyword" name="keyword"
                                           placeholder="ค้นหา: ชื่อแคมเปญ / ผู้ส่ง / ข้อความ">
                                </div>

                                <div class="form-group col-6">
                                    {!! Form::select('status', [
                '' => '== สถานะทั้งหมด ==',
                'draft' => 'Draft',
                'scheduled' => 'Scheduled',
                'running' => 'Running',
                'paused' => 'Paused',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ], '', ['id' => 'status', 'class' => 'form-control form-control-sm']) !!}

                                </div>

                                {{--                                <div class="form-groๅup col-6"></div>--}}
                                <div class="form-group col-6">
                                    {!! Form::select('provider', [
                  '' => '== Provider ทั้งหมด ==',
                  'vonage' => 'Vonage',
                  // เผื่ออนาคต:
                  // 'twilio' => 'Twilio',
                  // 'infobip' => 'Infobip',
              ], '', ['id' => 'provider', 'class' => 'form-control form-control-sm']) !!}

                                </div>

                                <div class="form-group col-6">
                                    {!! Form::select('audience_mode', [
                  '' => '== กลุ่มทั้งหมด ==',
                  'member_all' => 'สมาชิกทั้งหมด',
                  'upload_only' => 'จากไฟล์',
                  'mixed' => 'ผสม (สมาชิก+ไฟล์)',
                  'member_filter' => 'กรองสมาชิก',
              ], '', ['id' => 'audience_mode', 'class' => 'form-control form-control-sm']) !!}
                                </div>
                                <div class="form-group col-6"></div>

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

