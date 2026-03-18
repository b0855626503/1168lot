{{-- extend layout --}}
@extends('wallet::layouts.app')

{{-- page title --}}
@section('title','')

@push('styles')
    <link rel="stylesheet" href="css/style.css"/>
@endpush

@section('content')
    <div class="headregislogin">
        <div class="row m-0">
            <div class="col-6 p-1" onclick="location.href='{{ route('customer.home.index') }}'">
                <img class="gif" src="images/icon/เข้าสู่ระบบ-1.gif">
                <img class="png" src="images/icon/login.png">
            </div>
            <div class="col-6 p-1 active">
                <img class="gif" src="images/icon/regisbtn 2.gif">
                <img class="png" src="images/icon/regis.png">
            </div>
        </div>


    </div>

    <div class="px-1">

        <section class="sectionpage login">
            <div class="inbgbeforelogin">
                <div class="logopopup">
                    {!! core()->showImg($config->logo,'img','','','') !!}
                </div>
                <h1>สมัครสมาชิก</h1>

                <div class="main">

                    <div class="container">
                        <form method="POST" id="signup-form" class="signup-form" enctype="multipart/form-data">
                            <h3>
                                Account Setup
                            </h3>
                            <fieldset>
                                <h2>Creat your account</h2>
                                <div class="form-group">
                                    <input type="email" name="email" id="email" placeholder="Eg: aucreative@gmail.com"/>
                                </div>
                                <div class="form-group">
                                    <input type="password" name="password" id="password" placeholder="Password"/>
                                </div>
                                <div class="form-group">
                                    <input type="password" name="repassword" id="repassword" placeholder="Confirm Password"/>
                                </div>
                            </fieldset>

                            <h3>
                                Social Profiles
                            </h3>
                            <fieldset>
                                <h2>Social profiles</h2>
                                <div class="form-group">
                                    <input type="text" name="socials_twitter" id="socials_twitter" placeholder="Twitter"/>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="socials_facebook" id="socials_facebook" placeholder="Facebook"/>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="socials_google" id="socials_google" placeholder="Google Plus"/>
                                </div>
                            </fieldset>

                            <h3>
                                Personal Details
                            </h3>
                            <fieldset>
                                <h2>Personal Details</h2>
                                <div class="form-group">
                                    <input type="text" name="your_name" id="your_name" placeholder="Your name"/>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="your_phone" id="your_phone" placeholder="Phone"/>
                                </div>
                                <div class="form-group">
                                    <input type="text" name="your_addr" id="your_addr" placeholder="Address"/>
                                </div>
                            </fieldset>
                        </form>
                    </div>

                </div>

                <div class="mt-4">
                    <div class="modalspanbox">เป็นสมาชิกอยู่แล้ว? <a class="loginbtn"
                                                                     href="{{ route('customer.home.index') }}">เข้าสู่ระบบ</a>
                    </div>
                </div>
            </div>

        </section>

    </div>
@endsection
@once
    @push('scripts')
        <script src="vendor/jquery-steps/jquery.steps.min.js"></script>
        <script src="js/main.js"></script>
    @endpush
@endonce
