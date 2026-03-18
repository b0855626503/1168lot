@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div class="card text-light card-trans">
                    <div class="card-body py-3 px-2">
                        @if($config->seamless == 'Y')
                            <seamless></seamless>
                        @else
                            @if($config->multigame_open == 'Y')
                                <wallet></wallet>
                            @else
                                <credit></credit>
                            @endif
                        @endif
                    </div>
                </div>

                @if($config->seamless == 'Y')
                    @include('wallet::customer.home.seamless')
                @else
                    @if($config->multigame_open == 'Y')
                        @include('wallet::customer.home.multi')
                    @else
                        @include('wallet::customer.home.single')
                    @endif
                @endif
            </div>
        </div>
    </div>

@endsection
