@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection


@section('content')
    <div class="container mt-5">
        <h3 class="text-center text-light">ดาวน์โหลด</h3>
        <p class="text-center text-color-fixed">Download</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">

                <section class="content mt-3">

                    @foreach($games as $i => $game)

                        <div class="card card-trans">
                            <div class="card-body">
                                <h5 class="content-heading">{{ ucfirst($i) }}</h5>
                                <div class="row">

                                    @foreach($games[$i] as $k => $item)
                                        <div class="col-6 mb-4 col-md-3">
                                            {!! core()->showImg($item['filepic'],'game_img','','','d-block mx-auto rounded-circle transfer-slide-img h-90 w-90') !!}
                                            <p class="text-main text-center mb-0 cut-text">{{ $item['name'] }}</p>
                                            <p class="mb-0">

                                            </p>
                                            @if($item['link_ios'])
                                                <button class="btn btn-sm btn-success btn-block"
                                                        @click.prevent="onOpen('{{ $item['link_ios'] }}')"><i
                                                        class="fa fa-apple"></i> iOS
                                                </button>
                                            @endif
                                            @if($item['link_android'])
                                                <button class="btn btn-sm btn-primary btn-block"
                                                        @click.prevent="onOpen('{{ $item['link_android'] }}')"><i
                                                        class="fa fa-android"></i> Android
                                                </button>
                                            @endif
                                            @if($item['link_web'])
                                                <button class="btn btn-sm btn-theme btn-block"
                                                        @click.prevent="onOpen('{{ $item['link_web'] }}')"><i
                                                        class="fa fa-link"></i> Web
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    @endforeach


                </section>

            </div>
        </div>
    </div>

@endsection





