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
                        <p>สำหรับเพิ่มโปรหรือข้อมูลรายละเอียดต่างๆ ที่ไม่ได้เกี่ยวข้องกับโปรโมชั่นที่มีในระบบ
                            ซึ่งทางผู้ดูแล ต้องดำเนินการ ตรวจสอบเอง</p>

                    </div>
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

