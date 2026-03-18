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
                        <p>สำหรับ สร้างทีม โดย จะเป็น บุคคลได บุคคลหนึ่ง คล้ายๆ กับ ระบบผู้แนะนำ ใช้สำหรับหา ลูกทีม เพื่อ ที่หัวทีม จะได้รับ ค่าคอม</p>
                        <p>เหมาะสำหรับ หารายได้ เมื่อหัวทีม นำลิงค์สมัครไป แจกจ่าย หรือ สื่อโฆษณา ส่วนตัว เพื่อเชิญชวน คนอื่นมาสมัครเป็นลูกทีม</p>
                        <p>ไม่มี ผลประโยชน์ ไดๆ ที่ให้กับลูกทีม ที่สมัคร</p>
                        <p>ไม่ได้ ใช้สำหรับ การวัดผล ยอดผู้สมัคร ยอดคลิ๊ก หรือ ไดๆ เกี่ยวกับการตลาด</p>
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


                                <div class="form-group col-6">
                                    {!! Form::select('enable', ['true' => 'ใช้งาน', 'false' => 'ไม่ใช้งาน'], '', ['id' => 'enable', 'class' => 'form-control form-control-sm']) !!}
                                </div>

                                <div class="form-group col-6"></div>
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

