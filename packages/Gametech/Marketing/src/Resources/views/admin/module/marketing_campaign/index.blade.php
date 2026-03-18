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
                        <p>สำหรับ สร้างกิจกรรม หรือ โครงการ อะไรสักอย่าง ที่ต้องการได้ข้อมูล เพื่อนำไป วิเคราะห์ วางแผน เกี่ยวกับการ ตลาด</p>
                        <p>เมื่อสร้าง รายการในเมนูนี้ จะได้ลิงค์สำหรับเอาไป แปะใน สื่อต่างๆ เช่น เป็นลิงค์สำหรับ รูปภาพ AD ของเวบใด เวบหนึ่งที่เรา ลงโฆษณา</p>
                        <p>ใช้ เมื่อต้องการ ทราบข้อมูลว่า AD นั้นๆ หรือ กิจกรรม นั้น มีคนสนใจ สมัคร เติมเงิน เท่าใด จะได้วัดผลได้ว่า AD ดังกล่าว ประสบความสำเร็จ หรือไม่</p>
                        <p>เมื่อ กด สิ้นสุดแคมเปญ จะเข้า ลิงค์สมัครไม่ได้</p>
                        <p>สำหรับ ทีมงานเวบใช้งาน เป็นหลัก</p>
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

