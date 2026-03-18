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
                                <div class="form-group col-12 col-md-6">
                                    <label>ครั้งสุดท้ายที่เข้าระบบเมื่อ</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-clock"></i></span>
                                        </div>
                                        <input type="text" class="form-control form-control-sm float-right"
                                               id="search_lastlogin_date" readonly>
                                        <input type="hidden" class="form-control float-right" id="start_lastlogin_date"
                                               name="start_lastlogin_date">
                                        <input type="hidden" class="form-control float-right" id="end_lastlogin_date"
                                               name="end_lastlogin_date">
                                    </div>
                                </div>

                                <div class="form-group col-12 col-md-6">
                                    <label>ลงทะเบียนเมื่อ</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-clock"></i></span>
                                        </div>
                                        <input type="text" class="form-control form-control-sm float-right"
                                               id="search_regis_date" readonly>
                                        <input type="hidden" class="form-control float-right" id="start_regis_date"
                                               name="start_regis_date">
                                        <input type="hidden" class="form-control float-right" id="end_regis_date"
                                               name="end_regis_date">
                                    </div>
                                </div>


                                <div class="form-group col-12 col-md-6">
                                    <label>ครั้งสุดท้ายที่เติมเมื่อ</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-clock"></i></span>
                                        </div>
                                        <input type="text" class="form-control form-control-sm float-right"
                                               id="search_lasttopup_date" readonly>
                                        <input type="hidden" class="form-control float-right" id="start_lasttopup_date"
                                               name="start_lasttopup_date">
                                        <input type="hidden" class="form-control float-right" id="end_lasttopup_date"
                                               name="end_lasttopup_date">
                                    </div>
                                </div>


                                <div class="form-group col-6">
                                    <label>ค้นหาสมาชิก</label>
                                    <input type="text" class="form-control form-control-sm" id="user_name"
                                           placeholder="Username"
                                           name="user_name">
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

