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
                        <p>โปรโมชั่น แนะนำเพื่อน FASTSTART รหัส (pro_faststart) เมื่อใช้งาน Auto ระบบจะ
                            ตรวจสอบยอดเติมเงินของสมาชิก
                            และมอบโบนัสให้กับ Upline โดยอัตโนมัติ </p>
                        <p>โปรโมชั่น Cashback รหัส (pro_cashback) เมื่อใช้งาน Auto ระบบจะ
                            ตรวจสอบและดำเนินการมอบโบนัสให้ทุกวัน
                            หลังเที่ยงคืน ทำงานช่วงเวลา ไม่เกินตีสอง โดยค่าที่ตั้งคือ จ่ายเป็น % และ จำนวน % เท่านั้น
                            ค่าอื่นไม่มีผล</p>
                        <p>โปรโมชั่น ชวนเพื่อนมาเป็นหุ้นส่วน (pro_ic) เมื่อใช้งาน Auto ระบบจะ
                            ตรวจสอบและดำเนินการมอบโบนัสให้ทุกวัน หลังเที่ยงคืน ไม่เกินตีสอง โดยค่าที่ตั้งคือ จ่ายเป็น %
                            และ จำนวน % เท่านั้น ค่าอื่นไม่มีผล</p>
                        <p>ระบบ โปรโมชั่น เวอชั่นนี้ รองรับแค่ โปรพื้นฐานเท่านั้น โปรเพิ่มเติมที่ขอเพิ่มจากระบบเก่า
                            จะไม่สามารถใช้งานได้ หรือใช้งานได้แต่่ผิด ขอสงวนสิทธิ์ในการแก้ไข ให้ถูกต้อง</p>
                        <p>โปรโมชั่นระบบ พื้นฐาน มีรหัส ดังนี้ pro_newuser pro_faststart pro_firstday pro_cashback
                            pro_ic</p>
                        <p>โปรโมชั่นอื่นๆ ที่เป็นแค่เนื้อหา ไม่เกี่ยวข้องกับระบบ โปรดใส่ ที่ โปรโมชั่น เพิ่มเติม</p>
                        <p class="text-danger">จำกัดวงเงินถอน หมายถึง เราอยากเซตโปร ให้ติดเทิน xxx บาท เช่น โปร เติม 50
                            ได้ 100 เทิน 3 เท่า = ต้องได้ยอด 300 ในเกม ถึงจะถอนได้ แต่ตอนถอน ให้ 150 บาทพอ
                            ก็มาใส่ค่าที่ตรงนี้</p>
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

