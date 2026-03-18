@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')


@section('content')
    <div class="p-1">
        <div class="headsecion">
            <i class="fas fa-bullseye"></i> วงล้อ
        </div>
        <div class="ctpersonal">
            <wheel :items="{{ json_encode($spins) }}" :spincount="{{ $profile->diamond }}"></wheel>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/latest/TweenMax.min.js"></script>
@endpush





