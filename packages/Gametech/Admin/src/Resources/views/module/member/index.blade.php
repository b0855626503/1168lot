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
                                    {!! Form::select('legacy', ['' => 'ประเภทลูกค้าทั้งหมด','0' => 'ใหม่', '1' => 'เก่า'], '',['id' => 'legacy', 'class' => 'form-control form-control-sm']) !!}

                                </div>

                                <div class="form-group col-auto">
                                    <p>รับโปร เป็นสีแดง หมายถึง ลุกค้าเลือกไม่รับโปร / ถ้าสีเขียวแสดงว่า รับโปรได้</p>
                                    <p>โปรสมาชิกใหม่ เป็นสีแดง หมายถึง ลุกค้ายังไม่ได้รับโปรสมาชิกใหม่ /
                                        ถ้าสีเขียวแสดงว่า รับโปรสมาชิกใหม่แล้ว สีจะปรับเองถ้าลุกค้ากดรับโปร
                                        ทีมงานไม่ต้องไปกด </p>
                                </div>


                                <div class="form-group col-auto">
                                    <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i>
                                        Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
                <!-- /.info-box -->
            </div>
        </div>

        {{--        <div class="card">--}}

        {{--            <div class="card-body text-center">--}}

        {{--                <p>1. ต้องการให้ สมาชิกทุกคน รับโปร สมาชิกใหม่ได้อีกครัั้ง แก้ได้ด้วยการ copy url <a href="{{ route('admin.cmd.resetpro') }}" target="_blank" onclick="return confirm('แน่ใจใช่ไหม ที่ต้องการ รีโปร ให้สมาชิกรับใหม่ได้')">กดที่นี่เพื่อดำเนินการ</a> </p>--}}

        {{--            </div>--}}
        {{--            <!-- /.card-body -->--}}
        {{--        </div>--}}

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

