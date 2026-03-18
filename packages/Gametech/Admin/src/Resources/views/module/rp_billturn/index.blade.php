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
                                    {!! Form::select('game_code', (['' => '== เกมส์ ==']+$games->toArray()), '',['id' => 'game_code', 'class' => 'form-control form-control-sm']) !!}

                                </div>

                                <div class="form-group col-6">
                                    <input type="text" class="form-control form-control-sm" id="user_name"
                                           placeholder="Username"
                                           name="user_name">
                                </div>

                                <div class="form-group col-6">
                                    {{--                                    {!! Form::select('turn', [ '' => '== รายการทั้งหมด ==' , '1' => 'ติดเทรินโปร' , '2' => 'ไม่ติดเทรินโปร' ], '',['id' => 'turn', 'class' => 'form-control form-control-sm']) !!}--}}

                                </div>

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

