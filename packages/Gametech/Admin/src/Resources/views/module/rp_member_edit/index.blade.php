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

                                <div class="form-group col-6">
                                    {!! Form::select('menu',  ['' => 'ทั้งหมด' , 'firstname' => 'แก้ไขชื่อ' ,  'lastname' => 'แก้ไขนามสกุล' ,  'user_name' => 'แก้ไขเบอร์โทร' ,  'user_pass' => 'แก้ไขรหัสผ่าน' ,  'lineid' => 'แก้ไขไลน์ไอดี' ,  'bank_code' => 'แก้ไขธนาคาร' ,  'acc_no' => 'แก้ไขเลขบัญชี' ,  'maxwithdraw_day' => 'แก้ไข ยอดถอนสูงสุด/วัน ลูกค้า' ,  'promotion' => 'แก้ไขสถานะการรับโปรลูกค้า' ,  'status_pro' => 'แก้ไขสถานะโปรสมาชิกใหม่ลูกค้า' ,  'enable' => 'แก้ไขสถานะใช้งานลูกค้า' ], '',['id' => 'menu', 'class' => 'form-control form-control-sm']) !!}

                                </div>

                                <div class="form-group col-6">
                                    <input type="text" class="form-control form-control-sm" id="user_name"
                                           placeholder="เบอร์โทร"
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

