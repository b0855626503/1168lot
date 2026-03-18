@extends('wallet::layouts.blank')


@section('title','')




@section('content')
    <div class="ring">Not Available
        <span></span>
    </div>

@endsection

@push('scripts')
    <script type="text/javascript">
        // $(document).ready(function () {
            setTimeout(function () {
                window.close();
            }, 2000);
        // });
    </script>
@endpush
