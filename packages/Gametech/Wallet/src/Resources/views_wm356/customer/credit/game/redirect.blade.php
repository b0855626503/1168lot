@extends('wallet::layouts.blank')


@section('title','')




@section('content')
    <div class="ring">Loading
        <span></span>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        // $(document).ready(function () {
            setTimeout(function () {
                window.location.href = '{!! $url !!}';
            }, 2000);
        // });
    </script>
@endpush
