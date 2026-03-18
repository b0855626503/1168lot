@extends('admin::layouts.master')

{{-- page title --}}
@section('title')
    {{ $menu->currentName }}
@endsection


@section('content')
    <section class="content text-xs" id="content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $menu->currentName }} - ระบบ เปิดถอนอัตโนมัติ ปัจจุบันรองรับ ธนาคารกรุงเทพ</h3>


            </div>
            <div class="card-body">
                @includeIf('admin::module.'.$menu->currentRoute.'.create')
                @include('admin::module.'.$menu->currentRoute.'.table')
                @includeIf('admin::module.'.$menu->currentRoute.'.addedit')
            </div>
            <!-- /.card-body -->
        </div>
    </section>

@endsection

