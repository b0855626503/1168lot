@extends('admin::layouts.master')

{{-- page title --}}
@section('title')
    {{ $menu->currentName }}
@endsection


@section('content')
    <section class="content text-xs">
        <div class="card">

            <div class="card-body">
                @include('admin::module.'.$menu->currentRoute.'.table')
            </div>
            <!-- /.card-body -->
        </div>
    </section>

@endsection

