@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')


@section('content')
    <div class="p-1">
        <div class="headsecion">
            <i class="far fa-gift"></i> โปรโมชั่น
        </div>
        <div class="ctpersonal promotion">

            <div class="gridingame full">
                @foreach($promotions as $i => $item)
                    <div class="ingridgame">
                        <div class="iningridgame pro">
                            <img class="accordion" src="{{  Storage::url('promotion_img/'.$item->filepic)  }}">
                            <div class="panel" style="">
                                <div class="inpanel">

                                    {!! $item->content !!}

                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @foreach($pro_contents as $i => $item)
                    <div class="ingridgame">
                        <div class="iningridgame pro">
                            <img class="accordion" src="{{  Storage::url('procontent_img/'.$item->filepic)  }}">
                            <div class="panel" style="">
                                <div class="inpanel">

                                    {!! $item->content !!}

                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>


        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/js.js?'.time()) }}"></script>
@endpush




