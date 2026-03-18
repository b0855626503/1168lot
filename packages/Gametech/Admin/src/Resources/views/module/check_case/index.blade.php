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
                    <div class="card-body">

                        <p class="text-danger">เบื้องต้น ทีมงานที่ได้รับ สลิปลูกค้า ลองสแกน QR เช็คสลิป
                            เบื้องต้นได้ด้วยตนเอง ก่อนส่งแจ้ง WildPay และทุกสลิป มีเศษ สตางค์ เสมอ</p>

                    </div>
                </div>
                <!-- /.info-box -->
            </div>
        </div>
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
                                    {!! Form::select('bank_code', (['' => '== ช่องทาง ==']+$banks->toArray()), '',['id' => 'bank_code', 'class' => 'form-control form-control-sm']) !!}
                                </div>
                                <div class="form-group col-6">
                                    {!! Form::select('method', ['' => '== ประเภท ==' , '1' => 'ฝาก', '2' => 'ถอน'], '',['id' => 'method', 'class' => 'form-control form-control-sm']) !!}

                                </div>
                                <div class="form-group col-auto">
                                    <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> อัพเดทรายการ
                                    </button>
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
                @include('admin::module.'.$menu->currentRoute.'.table')
                {{--                @includeIf('admin::module.'.$menu->currentRoute.'.addedit')--}}
            </div>
            <!-- /.card-body -->
        </div>
    </section>

@endsection

