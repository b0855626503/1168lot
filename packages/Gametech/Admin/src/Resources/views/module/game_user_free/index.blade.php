@extends('admin::layouts.master')

{{-- page title --}}
@section('title')
    {{ $menu->currentName }}
@endsection


@section('content')
    <section class="content text-xs">

{{--        <div class="row">--}}
{{--            <div class="col-12">--}}
{{--                <div class="card card-primary">--}}

{{--                    <form id="frmsearch" method="post" onsubmit="return false;">--}}
{{--                        <div class="card-body">--}}
{{--                            <div class="row">--}}
{{--                                <div class="form-group col-12">--}}
{{--                                    <div class="input-group">--}}
{{--                                        <div class="input-group-prepend">--}}
{{--                                            <span class="input-group-text"><i class="far fa-clock"></i></span>--}}
{{--                                        </div>--}}
{{--                                        <input type="text" class="form-control form-control-sm float-right"--}}
{{--                                               id="search_date" readonly>--}}
{{--                                        <input type="hidden" class="form-control float-right" id="startDate"--}}
{{--                                               name="startDate">--}}
{{--                                        <input type="hidden" class="form-control float-right" id="endDate"--}}
{{--                                               name="endDate">--}}
{{--                                    </div>--}}
{{--                                </div>--}}


{{--                                <div class="form-group col-auto">--}}
{{--                                    เมื่อกดใช้งาน แสดงข้อมูลตามช่วงวันที่ จะแสดงข้อมูลสมาชิกที่ สมัครในช่วงวันที่--}}
{{--                                    เท่านั้น ใช้สำหรับ การ Export ข้อมูล--}}
{{--                                </div>--}}


{{--                                <div class="form-group col-auto">--}}
{{--                                    <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i>--}}
{{--                                        แสดงข้อมูลตามช่วงวันที่--}}
{{--                                    </button>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </form>--}}

{{--                </div>--}}
{{--                <!-- /.info-box -->--}}
{{--            </div>--}}
{{--        </div>--}}

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

