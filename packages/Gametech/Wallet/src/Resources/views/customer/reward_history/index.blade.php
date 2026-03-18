@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container">
        <h3 class="text-center text-light">ประวัติการแลกรางวัล</h3>
        <p class="text-center text-color-fixed">Exchange Reward History</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div id="result">

                    <div class="card text-black">
                        <ul class="list-group list-group-flush">
                            @foreach($histories as $n => $item)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $item['date_create'] }} รายการที่แลก : {{ $item['reward']['name'] }}<span
                                        class="float-right">{{ $item['point'] }} Point</span></li>
                            @endforeach
                        </ul>
                    </div>

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





