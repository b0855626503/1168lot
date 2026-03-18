@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@push('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/latest/TweenMax.min.js"></script>
@endpush


@section('content')

    <div class="sub-page sub-footer" style="display: flex; justify-content: center; align-items: center;">
        <div class="container promotion-member-container">
            <div class="ctpersonal">
            <wheel :items='@json($spins)' :spincount="{{ $profile->diamond }}"></wheel>
        </div>
        </div>
    </div>

@endsection




