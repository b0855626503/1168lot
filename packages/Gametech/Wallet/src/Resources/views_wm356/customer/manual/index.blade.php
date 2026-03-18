@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container">
        <h3 class="text-center text-light">คู่มือ</h3>
        <p class="text-center text-color-fixed">Guide</p>
        <div class="row">
            <div class="col-md-12 col-sm-12" id="accordion">

                @foreach($manuals as $i => $item)
                    <div class="my-1 owl-expansion-panel">

                        <div class="card-header">
                            <p class="my-auto align-middle owl-expansion-panel-header-title with-arrow"
                               data-toggle="collapse" href="#collapse{{ $i }}">
                                {{ $item->question }}
                            </p>
                        </div>

                        <div id="collapse{{ $i }}" class="collapse" data-parent="#accordion">
                            <div class="card-body img100">
                                {!! $item->answer !!}
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>

@endsection





