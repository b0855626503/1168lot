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
                                <div class="form-group col-6">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="far fa-clock"></i></span>
                                        </div>
                                        <input type="text" class="form-control form-control-sm float-right"
                                               id="search_date" readonly>
                                        <input type="hidden" class="form-control float-right" id="startDate"
                                               name="startDate">

                                    </div>
                                </div>


                                <div class="form-group col-6">
                                    <input type="text" class="form-control form-control-sm" id="user_name"
                                           placeholder="Username"
                                           name="user_name">
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

            <div class="card-body text-center">
                <p>1. ระบบจะคำนวนแล้ว มอบ ช่วงเวลา 00.00-01.00</p>
                <p>2. ระบบมอบไม่ครบ แก้ได้ด้วยการ copy url <a href="{{ route('admin.fix.cashbacktopup') }}" target="_blank">{{ route('admin.fix.cashbacktopup') }}</a> สามารถ f5 ที่ลิงค์ดังกล่าว ได้ จน ยอดเป็น 0%</p>
                <p>3. กดข้อที่ 2 แล้วยังไม่มอบ แสดงว่า ขั้นตอนการคำนวนมีปัญหา แก้ได้ด้วยการ copy url <a href="{{ route('admin.fix.cashback') }}" target="_blank">{{ route('admin.fix.cashback') }}</a> กดทีเดียวพอ แล้วไปกด ข้อ 2 ใหม่</p>
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

