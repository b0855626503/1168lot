@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@section('back')
    <a class="nav-link p-2 text-light mx-auto hand-point" href="{{ route('customer.home.index') }}">
        <i class="fas fa-chevron-left"></i> กลับ</a>
@endsection

@push('styles')
    <style>
        .section {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .img-select {
            border: 2px solid #fff;
            border-radius: 10%;
            background-color: #ffffff3a;
        }

        .img-bank {
            width: 40px;
            height: 40px;
        }

        .hidden {
            display: none;
        }

        a {
            color: #007bff;
            text-decoration: none;
            background-color: transparent;
        }
    </style>
@endpush

@section('content')
    <div class="container text-light mt-5">
        <h3 class="text-center text-light">ข้อมูลส่วนตัว</h3>
        <p class="text-center text-color-fixed">Profile</p>
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">

                <div class="card card-trans profile">
                    <div class="card-body">
                        <div class="align-items-center">
                            <div class="row">
                                <div class="col-6 col-sm-6">
                                    {!! (!is_null($profile->bank) ? core()->showImg($profile->bank->filepic,'bank_img','48px','48px','img-thumbnail rounded-circle m-2') : '') !!}
                                    <span>{{ (!is_null($profile->bank) ? $profile->bank->shortcode : '') }}</span>

                                </div>
                                <div class="col-5 col-sm-6">
                                    <div class="profile-txt mb-0">
                                        <p class="text-center mt-2 mb-0 align-middle text-color-fixed">
                                            <i class="far fa-user-check"></i> เลขที่บัญชี</p>
                                        <h6 class="text-center mb-0 align-middle ">{{ $profile->acc_no }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card card-trans profile">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="profile-txt"><p
                                        class=" text-color-fixed mb-0"><i
                                            class="fas fa-user"></i> ชื่อ - นามสกุล</p>
                                    <p>{{ $profile->name }}</p></div>
                                <div class="profile-txt"><p
                                        class=" text-color-fixed mb-0"><i
                                            class="fab fa-line"></i> Line ID</p>
                                    <p>{{ $profile->lineid }}</p></div>
                            </div>
                            <div class="col-6">
                                <div class="profile-txt"><p
                                        class=" text-color-fixed mb-0"><i
                                            class="fas fa-phone"></i> เบอร์โทรศัพท์</p>
                                    <p>{{ $profile->tel }}</p></div>
                                <div class="mt-3">
                                    <button class="btn btn-sm btn-theme btn-block" data-toggle="modal"
                                            data-target="#exampleModal">
                                        <i class="far fa-key"></i> แก้ไขรหัสผ่าน
                                    </button>
                                </div>
                            </div>
                            <div class="col-6">
                                @if($config->seamless == 'Y')
                                    <div class="profile-txt mb-3"><p
                                            class=" text-color-fixed mb-0"><i
                                                class="fas fa-gift"></i> การรับโปรโมชั่น</p>

                                        <btn-changepro></btn-changepro>
                                    </div>
                                @else
                                @if($config->multigame_open == 'N')
                                    <div class="profile-txt mb-3"><p
                                            class=" text-color-fixed mb-0"><i
                                                class="fas fa-gift"></i> การรับโปรโมชั่น</p>

                                        <btn-changepro></btn-changepro>
                                    </div>
                                @endif
                                @endif

                                <div class="profile-txt"><p
                                        class=" text-color-fixed mb-0"><i
                                            class="fas fa-user-plus"></i> สมัครสมาชิกเมื่อ</p>
                                    <p>{{ core()->formatDate($profile->date_regis,'d/m/Y') }}</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="profile-txt"><p
                                        class=" text-color-fixed mb-0"><i
                                            class="fas fa-user-check"></i> เข้าสู่ระบบล่าสุดเมื่อ</p>
                                    <p>{{ core()->formatDate($profile->lastlogin,'d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($config->seamless == 'Y')
                    @include('wallet::customer.profile.seamless')
                @else
                    @if($config->multigame_open == 'Y')
                        @include('wallet::customer.profile.multi')
                    @else
                        @include('wallet::customer.profile.single')
                    @endif
                @endif

            </div>
        </div>
    </div>

    <example-modal ref="modal"></example-modal>

@endsection

@push('scripts')
    <script type="text/x-template" id="example-modal">
        <div class="modal fade" style="color: black !important;" id="exampleModal" tabindex="-1"
             aria-labelledby="exampleModalLabel" data-backdrop="static" data-keyboard="false" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-sm">
                    <div class="modal-header">
                        <h5 class="modal-title">เปลี่ยนรหัสผ่าน</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <form id="frmchangepass" ref="form" @submit.prevent="submit" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="old_password">รหัสผ่านเก่า</label>
                                <input type="password" class="form-control"
                                       :class="[errors.has('old_password') ? 'is-invalid' : '']"
                                       data-vv-as="&quot;Present Password&quot;"
                                       id="old_password" name="old_password" v-validate="'required|min:6'"
                                       v-model="old_password">
                            </div>
                            <div class="form-group">
                                <label for="password">รหัสผ่านใหม่</label>
                                <input type="password" class="form-control"
                                       :class="[errors.has('password') ? 'is-invalid' : '']"
                                       data-vv-as="&quot;Password&quot;" ref="password"
                                       id="password" name="password" v-validate="'required|min:6'" v-model="password">
                            </div>
                            <div class="form-group">
                                <label for="password_confirmation">รหัสผ่านใหม่ (อีกครั้ง)</label>
                                <input type="password" class="form-control"
                                       :class="[errors.has('password_confirmation') ? 'is-invalid' : '']"
                                       id="password_confirmation" name="password_confirmation"
                                       data-vv-as="&quot;Password&quot;" data-vv-name="password_confirmation"
                                       v-validate="'required|min:6|confirmed:password'" v-model="password_confirmation">
                            </div>

                            <div class="row">

                                <!-- /.col -->
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary btn-block" style="border: none"
                                            @click="validateBeforeSubmit"><i
                                            class="fas fa-sign-in-alt"></i> ยืนยันการเปลี่ยนรหัสผ่าน
                                    </button>
                                </div>
                                <!-- /.col -->
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="btn-changepro">
        @if($profile->promotion == 'Y')
        <button type="button" class="btn btn-sm btn-success btn-block" ref="btnchangepro"
                v-on:click="changePro">
            <i class="far fa-check"></i> รับโปรโมชั่น
        </button>
        @else
        <button type="button" class="btn btn-sm btn-danger btn-block" ref="btnchangepro"
                v-on:click="changePro">
            <i class="far fa-times"></i> ไม่รับโปรโมชั่น
        </button>
        @endif
    </script>

    <script type="text/x-template" id="btn-reset">
        <div class="row">
            <div class="col">
                <button type="button" ref="btnreset" class="btn btn-theme mx-auto btn-block" v-on:click="gameReset">
                    รีเซ็ตรหัสเกมทั้งหมด
                </button>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="btnfree-reset">
        <div class="row">
            <div class="col">
                <button type="button" ref="btnfreereset" class="btn btn-theme mx-auto btn-block"
                        v-on:click="gameFreeReset">รีเซ็ตรหัสเกมทั้งหมด
                </button>
            </div>
        </div>

    </script>

    <script type="module">


        Vue.component('example-modal', {
            template: '#example-modal',
            data: function () {
                return {
                    password: '',
                    old_password: '',
                    password_confirmation: ''
                }
            },
            mounted() {
                this.$validator.errors.clear();
            },
            methods: {
                validateBeforeSubmit() {
                    this.$validator.validate().then(valid => {

                        if (valid) {
                            this.submit();
                        }


                    });
                },
                showModalNew() {
                    console.log('tester');
                    let element = this.$refs.modal.$el
                    console.log(element);
                    $(element).modal('show')
                },
                submit() {

                    this.$http.post(`${this.$root.baseUrl}/member/profile/changepass`, {
                        old_password: this.old_password,
                        password: this.password,
                        password_confirmation: this.password_confirmation,
                        '_token': "{{ csrf_token() }}"
                    })
                        .then(response => {
                            $('.modal').modal('hide');

                            if (response.data.success) {
                                Swal.fire(
                                    'เปลี่ยนรหัสผ่าน',
                                    response.data.message,
                                    'success'
                                )
                            } else {
                                Swal.fire(
                                    'เปลี่ยนรหัสผ่าน',
                                    'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ ในขณะนี้',
                                    'error'
                                )
                            }
                            this.old_password = '';
                            this.password = '';
                            this.password_confirmation = '';
                            this.$validator.reset();
                        })
                        .catch(exception => {
                            $('.modal').modal('hide');
                            Swal.fire(
                                'เปลี่ยนรหัสผ่าน',
                                'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ เนื่องจากข้อมูล รหัสผ่านไม่ถูกต้อง',
                                'error'
                            );
                        });

                },

            }
        });

        Vue.component('btn-changepro', {
            template: '#btn-changepro',
            methods: {
                changePro: function (event) {
                    Swal.fire({
                        title: 'แก้ไขการรับโปรโมชั่น',
                        text: "คุณต้องการดำเนินการ ใช่หรือไม่",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ยืนยัน',
                        cancelButtonText: 'ยกเลิก',
                        customClass: {
                            container: 'text-sm',
                            popup: 'text-sm'
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.$http.post(`${this.$root.baseUrl}/member/profile/changepro`)
                                .then(response => {
                                    $('.modal').modal('hide');

                                    if (response.data.success) {
                                        Swal.fire(
                                            'การดำเนินการ',
                                            response.data.message,
                                            'success'
                                        )
                                        window.location.href = window.location;
                                    } else {
                                        Swal.fire(
                                            'การดำเนินการ',
                                            'ไม่สามารถเปลี่ยนข้อมูลการรับโปรโมชั่นได้ ในขณะนี้',
                                            'error'
                                        )
                                    }
                                })
                                .catch(exception => {
                                    $('.modal').modal('hide');
                                    Swal.fire(
                                        'การดำเนินการ',
                                        'เกิดข้อผิดพลาดบางประการ โปรดลองใหม่อีกครั้ง',
                                        'error'
                                    );
                                });
                        }
                    })
                }
            }
        });

        Vue.component('btn-reset', {
            template: '#btn-reset',
            methods: {
                gameReset: function (event) {
                    Swal.fire({
                        title: 'รีเซ็ตรหัสผ่านเกม (Wallet) ทั้งหมด',
                        text: "คุณต้องการดำเนินการ ใช่หรือไม่",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ยืนยัน',
                        cancelButtonText: 'ยกเลิก',
                        customClass: {
                            container: 'text-sm',
                            popup: 'text-sm'
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.$http.post(`${this.$root.baseUrl}/member/profile/resetgamepass`)
                                .then(response => {
                                    $('.modal').modal('hide');

                                    if (response.data.success) {
                                        Swal.fire(
                                            'เปลี่ยนรหัสผ่าน',
                                            response.data.message,
                                            'success'
                                        )
                                    } else {
                                        Swal.fire(
                                            'เปลี่ยนรหัสผ่าน',
                                            'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ ในขณะนี้',
                                            'error'
                                        )
                                    }
                                    this.old_password = '';
                                    this.password = '';
                                    this.password_confirmation = '';
                                    this.$validator.reset();
                                })
                                .catch(exception => {
                                    $('.modal').modal('hide');
                                    Swal.fire(
                                        'เปลี่ยนรหัสผ่าน',
                                        'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ เนื่องจากข้อมูล รหัสผ่านไม่ถูกต้อง',
                                        'error'
                                    );
                                });
                        }
                    })
                }
            }
        });

        Vue.component('btnfree-reset', {
            template: '#btnfree-reset',
            methods: {
                gameFreeReset: function (event) {
                    Swal.fire({
                        title: 'รีเซ็ตรหัสผ่านเกม (Cashback) ทั้งหมด',
                        text: "คุณต้องการดำเนินการ ใช่หรือไม่",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'ยืนยัน',
                        cancelButtonText: 'ยกเลิก',
                        customClass: {
                            container: 'text-sm',
                            popup: 'text-sm'
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.$http.post(`${this.$root.baseUrl}/member/profile/resetgamefreepass`)
                                .then(response => {
                                    $('.modal').modal('hide');

                                    if (response.data.success) {
                                        Swal.fire(
                                            'เปลี่ยนรหัสผ่าน',
                                            response.data.message,
                                            'success'
                                        )
                                    } else {
                                        Swal.fire(
                                            'เปลี่ยนรหัสผ่าน',
                                            'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ ในขณะนี้',
                                            'error'
                                        )
                                    }
                                    this.old_password = '';
                                    this.password = '';
                                    this.password_confirmation = '';
                                    this.$validator.reset();
                                })
                                .catch(exception => {
                                    $('.modal').modal('hide');
                                    Swal.fire(
                                        'เปลี่ยนรหัสผ่าน',
                                        'ไม่สามารถเปลี่ยนข้อมูลรหัสผ่านได้ เนื่องจากข้อมูล รหัสผ่านไม่ถูกต้อง',
                                        'error'
                                    );
                                });
                        }
                    })
                }
            }
        });


    </script>
@endpush





