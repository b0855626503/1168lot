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

            <div class="card-body text-center">
                <p>กดเพื่อ คำนวนสรุป วันปัจจุบัน <a href="{{ route('admin.fix.sumtoday') }}" target="_blank">กดตรงนี้ โปรดกดเมื่อต้องการข้อมูล</a></p>
                <p>ระบบจะคำนวนยอด สรุปย้อนหลัง เวลา 00.05 ของทุกวัน <a href="{{ route('admin.fix.sumyesterday') }}" target="_blank">กดตรงนี้ เมื่อระบบไม่คำนวนย้อนหลังให้</a></p>
            </div>
            <!-- /.card-body -->
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

