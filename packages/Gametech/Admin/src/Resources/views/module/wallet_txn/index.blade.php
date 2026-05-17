@extends('admin::layouts.master')

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
                                    {!! Form::select('direction', ['' => '== Direction ==', 'CREDIT' => 'CREDIT', 'DEBIT' => 'DEBIT'], '', ['id' => 'direction', 'class' => 'form-control form-control-sm']) !!}
                                </div>

                                <div class="form-group col-6">
                                    <input type="text" class="form-control form-control-sm" id="user_name"
                                           placeholder="Username"
                                           name="user_name">
                                </div>

                                <div class="form-group col-auto">
                                    <button class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @include('admin::module.'.$menu->currentRoute.'.table')
            </div>
        </div>
    </section>

@endsection
