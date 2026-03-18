@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container">
        <h3 class="text-center text-light">ประวัติวงล้อ</h3>
        <p class="text-center text-color-fixed">Spin Reward History</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div id="result">
                    @foreach($histories as $i => $history)
                        <div class="card text-black">
                            <ul class="list-group list-group-flush">
                                <li class="text-right list-group-item list-group-item-primary"
                                    style="background-color: #28a745 !important"> {{ $histories[$i]['date'] }}</li>
                                @foreach($histories[$i]['data'] as $n => $item)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $item['credit'] }} <span class="float-right">{{ $item['time'] }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
                <br>
                <a id="back-to-top" @click.prevent="topFunction" class="btn btn-primary back-to-top" role="button"
                   aria-label="Scroll to top">
                    <i class="fas fa-chevron-up"></i>
                </a>
                {{--                <button @click.prevent="topFunction" class="text-center btn btn-round btn-info mx-auto d-block"><i class="fas fa-arrow-up"></i> กลับขึ้นด้านบน </button>--}}
            </div>
        </div>
    </div>

@endsection





