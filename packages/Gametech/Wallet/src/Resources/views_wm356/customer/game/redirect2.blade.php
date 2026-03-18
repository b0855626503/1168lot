@extends('wallet::layouts.blank')


@section('title','')




@section('content')
    {!! $url !!}
@endsection

@push('scripts')
{{--    <script type="text/javascript">--}}
{{--        // $(document).ready(function () {--}}
{{--            setTimeout(function () {--}}
{{--                window.location.href = '{!! $url !!}';--}}
{{--            }, 2000);--}}
{{--        // });--}}
{{--    </script>--}}
@endpush
