@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    @if($config->seamless == 'Y')
        @include('wallet::customer.promotion.seamless')
    @else
        @include('wallet::customer.promotion.normal')
    @endif

    
@endsection

@push('script')
    <script type="application/ld+json">
        {
            "url": "member/promotion"
        }

    </script>
@endpush

@push('scripts')
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com"/>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/fontawesome.min.css"
        integrity="sha512-shT5e46zNSD6lt4dlJHb+7LoUko9QZXTGlmWWx0qjI9UhQrElRb+Q5DM7SVte9G9ZNmovz2qIaV7IWv0xQkBkw=="
        crossorigin="anonymous"
        onload="this.onload=null;this.rel='stylesheet'"
    />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/solid.min.css"
        integrity="sha512-xIEmv/u9DeZZRfvRS06QVP2C97Hs5i0ePXDooLa5ZPla3jOgPT/w6CzoSMPuRiumP7A/xhnUBxRmgWWwU26ZeQ=="
        crossorigin="anonymous"
        onload="this.onload=null;this.rel='stylesheet'"
    />
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/regular.min.css"
        integrity="sha512-1yhsV5mlXC9Ve9GDpVWlM/tpG2JdCTMQGNJHvV5TEzAJycWtHfH0/HHSDzHFhFgqtFsm1yWyyHqssFERrYlenA=="
        crossorigin="anonymous"
        onload="this.onload=null;this.rel='stylesheet'"
    />

    <noscript>
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/regular.min.css"
            integrity="sha512-1yhsV5mlXC9Ve9GDpVWlM/tpG2JdCTMQGNJHvV5TEzAJycWtHfH0/HHSDzHFhFgqtFsm1yWyyHqssFERrYlenA=="
            crossorigin="anonymous"
        />
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/solid.min.css"
            integrity="sha512-xIEmv/u9DeZZRfvRS06QVP2C97Hs5i0ePXDooLa5ZPla3jOgPT/w6CzoSMPuRiumP7A/xhnUBxRmgWWwU26ZeQ=="
            crossorigin="anonymous"
        />
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/fontawesome.min.css"
            integrity="sha512-shT5e46zNSD6lt4dlJHb+7LoUko9QZXTGlmWWx0qjI9UhQrElRb+Q5DM7SVte9G9ZNmovz2qIaV7IWv0xQkBkw=="
            crossorigin="anonymous"
        />
    </noscript>

    <script>
        $(document).ready(function () {
            $('body').addClass('x-ez-igame-promotion-index');
        });


        function getPro(id) {


            axios.post("{{ route('customer.promotion.store') }}", {id: id})
                .then(response => {
                    if (response.data.success) {

                        Toast.fire({
                            icon: 'success',
                            title: notification.message
                        });

                    } else {

                        Toast.fire({
                            icon: 'error',
                            title: notification.message
                        });
                    }
                })
                .catch(response => {
                    $('.modal').modal('hide');
                    Toast.fire({
                        icon: 'success',
                        title: notification.message
                    });
                });

        }


    </script>

@endpush







