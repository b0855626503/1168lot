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

                                {{-- Date range --}}
                                <div class="form-group col-12">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="far fa-clock"></i>
                                        </span>
                                        </div>
                                        <input type="text"
                                               class="form-control float-right"
                                               id="search_date"
                                               readonly>
                                        <input type="hidden" id="startDate" name="startDate">
                                        <input type="hidden" id="endDate" name="endDate">
                                    </div>
                                </div>

                                {{-- Keyword --}}
                                <div class="form-group col-6">
                                    <input type="text"
                                           class="form-control form-control-sm"
                                           id="keyword"
                                           name="keyword"
                                           placeholder="ค้นหา: Message ID / เบอร์ / Error code">
                                </div>

                                {{-- Provider --}}
                                <div class="form-group col-6">
                                    {!! Form::select('provider', [
                                        '' => '== Provider ทั้งหมด ==',
                                        'vonage' => 'Vonage',
                                    ], '', ['class' => 'form-control form-control-sm']) !!}
                                </div>

                                {{-- DLR Status --}}
                                <div class="form-group col-6">
                                    {!! Form::select('status', [
                                        '' => '== DLR Status ทั้งหมด ==',
                                        'delivered' => 'Delivered',
                                        'accepted' => 'Accepted',
                                        'buffered' => 'Buffered',
                                        'failed' => 'Failed',
                                        'rejected' => 'Rejected',
                                        'expired' => 'Expired',
                                        'undelivered' => 'Undelivered',
                                    ], '', ['class' => 'form-control form-control-sm']) !!}
                                </div>

                                {{-- Process Status --}}
                                <div class="form-group col-6">
                                    {!! Form::select('process_status', [
                                        '' => '== Process Status ==',
                                        'pending' => 'Pending',
                                        'processed' => 'Processed',
                                        'failed' => 'Failed',
                                        'ignored' => 'Ignored',
                                    ], '', ['class' => 'form-control form-control-sm']) !!}
                                </div>

                                {{-- Campaign ID --}}
                                <div class="form-group col-6">
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="campaign_id"
                                           placeholder="Campaign ID">
                                </div>

                                {{-- Recipient ID --}}
                                <div class="form-group col-6">
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="recipient_id"
                                           placeholder="Recipient ID">
                                </div>

                                {{-- Search --}}
                                <div class="form-group col-auto">
                                    <button class="btn btn-primary btn-sm">
                                        <i class="fa fa-search"></i> Search
                                    </button>
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
