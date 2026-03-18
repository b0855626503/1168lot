{{-- extend layout --}}
@extends('wallet::layouts.register')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .homeregis{
            display: none !important;
        }
    </style>
@endpush

@section('content')

    <div class="form-container">
        <div class="form-content">
            <div class="form-header">
                Multi-Step Form
            </div>
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <!-- Step 1 -->
                    <div class="swiper-slide">
                        <h3>Step 1: Personal Info</h3>
                        <label for="name">Name</label>
                        <input type="text" id="name" placeholder="Enter your name">
                        <label for="email">Email</label>
                        <input type="email" id="email" placeholder="Enter your email">
                    </div>

                    <!-- Step 2 -->
                    <div class="swiper-slide">
                        <h3>Step 2: Address</h3>
                        <label for="address">Address</label>
                        <input type="text" id="address" placeholder="Enter your address">
                        <label for="city">City</label>
                        <input type="text" id="city" placeholder="Enter your city">
                    </div>

                    <!-- Step 3 -->
                    <div class="swiper-slide">
                        <h3>Step 3: Account</h3>
                        <label for="username">Username</label>
                        <input type="text" id="username" placeholder="Choose a username">
                        <label for="password">Password</label>
                        <input type="password" id="password" placeholder="Choose a password">
                    </div>
                </div>
            </div>
            <div class="form-footer">
                <button class="prev-btn" disabled>Previous</button>
                <button class="next-btn">Next</button>
                <button class="submit-btn">Submit</button>
            </div>
        </div>
    </div>

@endsection
