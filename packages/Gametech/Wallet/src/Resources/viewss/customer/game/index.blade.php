@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                @if($config->seamless == 'Y')
                    <div class="card text-light card-trans">
                        <div class="card-body py-3 px-2">
                            <seamless></seamless>
                        </div>
                    </div>

                    @include('wallet::customer.game.seamless')
                @else
                    @include('wallet::customer.game.single')
                @endif

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function copy(id) {
            var copyText = document.getElementById(id);
            var input = document.createElement("textarea");
            input.value = copyText.textContent;
            this.copycontent = copyText.textContent;
            document.body.appendChild(input);
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand("copy");
            input.remove();
        }
    </script>
@endpush

