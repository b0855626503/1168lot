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
                        <p class="text-danger">เปิดบัญชีอัตโนมัติ แนะนำให้ปิด การใช้งาน</p>
                    </div>
                </div>
                <!-- /.info-box -->
            </div>
        </div>

        <div class="card">

            <div class="card-body">
                @include('admin::module.'.$menu->currentRoute.'.table')
                @includeIf('admin::module.'.$menu->currentRoute.'.addedit')
            </div>
            <!-- /.card-body -->
        </div>
    </section>

@endsection

