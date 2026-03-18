@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container mt-5">
        <h3 class="text-center text-light">รายงานตัว</h3>
        <p class="text-center text-color-fixed">Checkin</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">

                <section class="content mt-3">

                    <div class="card card-trans">
                        <div class="card-body text-center">
                            <checkin></checkin>
                        </div>
                    </div>


                </section>

                <section class="content mt-3">

                    <div class="card card-trans">
                        <div class="card-body">
                            <checkinlog ref="checkinlog"></checkinlog>
                        </div>
                    </div>


                </section>

            </div>
        </div>
    </div>

@endsection





