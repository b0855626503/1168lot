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
                                    {!! Form::select('game_code',  (['' => '== เกมส์ ==']+$games->toArray()) , '',['id' => 'game_code', 'class' => 'form-control form-control-sm']) !!}

                                </div>

                                <div class="form-group col-6">
                                    {!! Form::select('freecredit', [ '' => 'ทั้งหมด' , 'N' => 'ปกติ' , 'Y' => 'ฟรีเครดิต'], '',['id' => 'freecredit', 'class' => 'form-control form-control-sm']) !!}

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
                <p>แนะนำการ ตั้ง PREFIX แต่ละเกม ไม่ควรซ้ำกัน โดยเฉพาะ JOKER กับ SLOTXO และควรแยก ระหว่าง ปกติ กับ ฟรีเครดิต</p>
                <p>1. ชื่อย่อเวบของเรา หรือ ตั้งรหัสสักอย่าง 2 ตัวที่สื่อถึงเวบที่กำลังใช้งาน เช่น XX </p>
                <p>2. แยกรหัส ระหว่าง ปกติ อาจจะใช้ N และ ใช้ F สำหรับฟรีเครดิต หรืออื่นๆตามต้องการ </p>
                <p>3. แต่ละค่ายเกม ต้องแยกรหัสกันให้ชัดเจน เช่น JOKER ก็ J หรือ JK </p>
                <p>4. อาจจะมี ตัวเลข สักตัวที่เราจะให้ระบบ Batch Auto Gen รันให้เวลามาเพิ่มครั้งหน้า ปกติเริ่มที่ 0 หรือ 1 </p>
                <p>5. สุดท้าย PREFIX ที่จะใช้ สำหรับ ค่ายเกม JOKER ประเภทปกติ ของเวบ ก็จะประมาณ XX0NJ หรือจะใช้ XX0NJK (สามารถสลับตำแหน่งตามต้องการ เช่น 0NJKXX)   </p>

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

