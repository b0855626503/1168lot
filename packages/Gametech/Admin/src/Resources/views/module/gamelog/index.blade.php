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
                                        <input type="hidden" id="hasSearched" name="hasSearched" value="0">
                                    </div>
                                </div>

                                <div class="form-group col-6">
                                    {!! Form::select('productId', ['' => 'เลือก Product'] + ($games ?? collect())->toArray(), '', ['id' => 'productId', 'class' => 'form-control form-control-sm']) !!}

                                </div>
                                <div class="form-group col-6">
                                    <input type="hidden" id="nextId" name="nextId">
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-block" id="btnLoadNext" disabled>
                                        <i class="fa fa-forward"></i> Load Next Chunk
                                    </button>
                                </div>

                                <div class="form-group col-12">
                                    <small class="text-muted">Range query must not exceed 1 hour. Summary stake/payout is for currently loaded chunk only.</small>
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
                @include('admin::module.gamelog.table')
                @includeIf('admin::module.'.$menu->currentRoute.'.addedit')
            </div>
            <!-- /.card-body -->
        </div>
    </section>

@endsection
