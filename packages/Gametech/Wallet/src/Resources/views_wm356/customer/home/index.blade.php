@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')

@push('styles')
    <style>
        .menu-item {
            min-width: 110px;
            min-height: 80px;
            background: #232323;
            border-radius: 16px;
            box-shadow: 0 2px 12px #0005;
            color: #ffb52a;
            display: flex;
            align-items: center;
            justify-content: center;
            /* ถ้าอยากให้ card มีระยะห่างระหว่างกัน ให้ใช้ gap ที่ .menu-scroll */
            transition: border 0.18s, background 0.15s, color 0.15s;
            text-align: center;
            border: 1px solid #ffb52a; // เพิ่มถ้าอยากให้ hover ชัด
        }

        /* ป้องกัน a ทำลาย flex ของ block */
        .menu-item a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: inherit;
            text-decoration: none;
            padding: 12px 0 6px 0;
        }

        .menu-item img {
            width: 36px;
            height: 36px;
            margin-bottom: 5px;
            object-fit: contain;
        }

        .menu-item small {
            margin-top: 2px;
            font-size: 1.08rem;
            letter-spacing: 0.3px;
            color: #ffb52a;
            font-weight: 600;
        }

        .menu-item:hover,
        .menu-item.active,
        .menu-item:focus-within {
            background: #111;
            box-shadow: 0 4px 20px #0009;
        }

        .menu-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
            /* optional: hide scrollbar */
            scrollbar-width: none;
        }


        .menu-scroll {
            display: flex;
            gap: 22px;
            /*background: #191919;*/
            padding: 18px 18px 10px 18px;
            width: fit-content;
            margin: 0 auto;

        }

        .menu-scroll-wrapper::-webkit-scrollbar { display: none; }
        .menu-scroll-wrapper { scrollbar-width: none; }
        @media (max-width: 600px) {
            .cat {

                padding-right: 0px !important;
                padding-left: 0px !important;

            }
            .menu-scroll {
                width: 100%;
                margin: 0;
                gap: 5px;
                padding: 5px 4px 4px 5px;
            }
            .menu-item {
                min-width: 66px;
                min-height: 48px;
                border-radius: 11px;
                font-size: 0.95rem;
            }
            .menu-item a {
                padding: 7px 0 3px 0;
            }
            .menu-item img {
                width: 26px;
                height: 26px;
                margin-bottom: 2px;
            }
            .menu-item small {
                font-size: smaller;
            }
        }

        .category-bar {
            display: flex;
            align-items: center;
            /*background: linear-gradient(92deg, #2e2e34 0%, #19191e 80%);*/
            border-radius: 14px;
            padding: 0 20px;
            min-height: 46px;
            margin-bottom: 20px;
            /*box-shadow: 0 2px 12px #0006, 0 0 0px #2229 inset;*/
            position: relative;
        }

        .category-title {
            background: linear-gradient(92deg, #ffb52a 60%, #ffd700 100%);
            color: #2a1a06;
            font-size: 1.18rem;
            font-weight: 900;
            padding: 7px 30px 7px 22px;
            border-radius: 11px 0 12px 11px;
            box-shadow: 0 1px 8px #ffb52a44;
            margin-left: -10px;
            margin-right: 18px;
            letter-spacing: 1.5px;
            min-width: 74px;
            text-align: center;
            border: 2.5px solid #b4001a;
            outline: 3.5px solid #232323;
            outline-offset: -6px;
        }

        @media (max-width: 600px) {
            .category-bar {
                padding: 0 6px;
                min-height: 40px;
                border-radius: 8px;
            }
            .category-title {
                font-size: 1rem;
                padding: 5px 16px 5px 10px;
                border-radius: 6px 0 9px 6px;
                min-width: 46px;
                margin-left: -5px;
                margin-right: 10px;
            }
        }

        .category-block {
            width: 100%;
            max-width: 1280px;      /* หรือ 100% ถ้าอยากเต็มจอ */
            min-height: 200px;      /* กำหนดความสูงเท่ากัน */
            margin-left: auto;
            margin-right: auto;
            /*background: linear-gradient(100deg, #23232c 80%, #18181c 100%);*/
            border-radius: 20px;
            /*box-shadow: 0 4px 40px #000a, 0 0 0 #ffd70015 inset;*/
            padding: 0 0 32px 0;
            /*margin-bottom: 34px;*/
            /*border: 1.8px solid #292942;*/
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;  /* หรือ center ถ้าอยากให้อยู่กลาง block */
        }


        /* ใช้กับ wrapper ของการ์ดเกม (เช่น .x-game-list-item-macro-in-share หรือ <li> ก็ได้) */
        .maintenance picture,
        .maintenance img {
            -webkit-filter: grayscale(100%);
            filter: grayscale(100%);
            opacity: 0.65;              /* ให้ดูหม่นลงหน่อย */
        }

        /* ปุ่มเข้าเล่นกดไม่ได้ + เคอร์เซอร์บอกสถานะ */
        .maintenance .-btn.-btn-play {
            pointer-events: none;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* กัน hover ทำให้ฟิลเตอร์หาย (ถ้ามีสไตล์อื่นมา override) */
        .maintenance:hover picture,
        .maintenance:hover img {
            -webkit-filter: grayscale(100%) !important;
            filter: grayscale(100%) !important;
        }

    </style>
@endpush

@section('content')

    @if($webconfig->seamless == 'Y')
        @include('wallet::customer.home.seamless')
    @else
        @if($webconfig->multigame_open == 'Y')
            @include('wallet::customer.home.multi')
        @else
            @include('wallet::customer.home.single')
        @endif
    @endif

@endsection

@push('script')
    <script type="application/ld+json">
            {
                "url": "member"
            }






    </script>
@endpush

@push('scripts')

    <script>
        function reload() {
            window.location.reload(true);
        }

        function openQuickRegis({details}) {
            console.log(details);
            // if (event) {
            //     event.preventDefault();
            //     event.stopPropagation();
            // }
            Swal.fire({
                title: 'ยืนยันการทำรายการนี้ ?',
                text: "คุณต้องการเปิดบัญชี เกม " + details.name + " หรือไม่",
                imageUrl: details.image,
                imageWidth: 90,
                imageHeight: 90,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ตกลง',
                cancelButtonText: 'ยกเลิก',
                customClass: {
                    container: 'text-sm',
                    popup: 'text-sm'
                },
            }).then((result) => {
                if (result.isConfirmed) {

                    $('.modal').modal('hide');
                    axios.post("{{ route('customer.home.create') }}", {id: details.code})
                        .then(response => {
                            if (response.data.success) {
                                reload();
                            } else {
                                Swal.fire(
                                    'พบข้อผิดพลาด',
                                    response.data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(response => {
                            $('.modal').modal('hide');
                            Swal.fire(
                                'การเชื่อมต่อระบบ มีปัญหา',
                                response.data.message,
                                'error'
                            );
                        });

                }
            })
        }

        function openQuickView({details, event}) {
            console.log(event);
            // console.log(event);
            // if (event) {
            //     event.preventDefault();
            //     event.stopPropagation();
            // }
            console.log(details);
            axios.post("{{ route('customer.profile.view') }}", {id: details.code})
                .then(response => {

                    console.log(response.data.success);
                    if (response.data.success) {

                        let btn = '';
                        if (response.data.data.game.link_ios) {
                            btn += '<a class="btn btn-sm btn-success mx-1" target="_blank" href="' + response.data.data.game.link_ios + '"><i class="fa-brands fa-apple"></i> iOS</a>';
                        }
                        if (response.data.data.game.link_android) {
                            btn += '<a class="btn btn-sm btn-primary mx-1" target="_blank" href="' + response.data.data.game.link_android + '"><i class="fa-brands fa-android"></i> Android</a>';
                        }
                        if (response.data.data.game.link_web) {
                            btn += '<a class="btn btn-sm btn-secondary mx-1" target="_blank" href="' + response.data.data.game.link_web + '"><i class="fas fa-link"></i> Web</a>';
                        }
                        if (response.data.data.game.autologin === 'Y') {
                            btn = '<a class="btn btn-sm btn-secondary mx-1" target="_blank" href="' + `{{ route('customer.game.login') }}` + '/' + response.data.data.game.id + '"><i class="fas fa-link"></i> Login</a>';
                        }

                        Swal.fire({
                            title: '<h5>ข้อมูลของเกม ' + details.name + '</h5>',
                            imageUrl: details.image,
                            imageWidth: 90,
                            imageHeight: 90,
                            html:
                                '<table class="table table-borderless text-sm" style="color:black;">, ' +
                                '<tbody> ' +
                                '<tr class="copybtn"> ' +
                                '<td>Username</td>' +
                                '<td><span>' + response.data.data.user_name + '</span></td>' +
                                '<td style="text-align: center"><a class="user text-primary" href="javascript:void(0)" onclick="copylink()">[คัดลอก]</a></td>' +
                                '</tr> ' +
                                '<tr class="copybtn"> ' +
                                '<td>Password</td>' +
                                '<td><span>' + response.data.data.user_pass + '</span></td>' +
                                '<td style="text-align: center"><a class="pass text-primary" href="javascript:void(0)" onclick="copylink()">[คัดลอก]</a></td>' +
                                '</tr> ' +
                                '<tr> ' +
                                '<td colspan="3">' + btn + '</td>' +
                                '</tr> ' +
                                '</tbody> ',
                            showConfirmButton: false,
                            showCloseButton: true,
                            showCancelButton: false,
                            focusConfirm: false,
                            scrollbarPadding: true,
                            customClass: {
                                container: 'text-sm',
                                popup: 'text-sm'
                            },
                            willOpen: () => {

                                $(".copybtn").click(function (event) {
                                    var $tempElement = $("<input>");
                                    $("body").append($tempElement);
                                    $tempElement.val($(this).closest(".copybtn").find("span").text()).select();
                                    document.execCommand("Copy");
                                    $tempElement.remove();
                                });


                            }
                        });

                    }
                })
                .catch(response => {

                    Swal.fire(
                        'เกิดปัญหาบางประการ',
                        'ไม่สามารถดำเนินการได้ โปรดลองใหม่อีกครั้ง',
                        'error'
                    );

                });

        }

        {{--function openTransfer({details, event}) {--}}
        {{--    (async () => {--}}
        {{--        const ipAPI = "{{ route('customer.transfer.load.promotion') }}";--}}
        {{--        const response = await fetch(ipAPI);--}}
        {{--        const data = await response.json();--}}
        {{--        const inputOptions = data.promotions;--}}
        {{--        const configeweb = {{ Illuminate\Support\Js::from($webconfig) }};--}}

        {{--        let foottext = '<small class="text-center text-danger">โยกเข้าเกมส์ ขั้นต่ำ {{ core()->currency($webconfig->mintransfer) }}  บาท</small>';--}}

        {{--        if (configeweb.mintransfer_pro !== 0) {--}}
        {{--            foottext += '<p><small class="text-center text-danger">สามารถโยกเข้าเกม ได้เมื่อเงินในเกมเหลือน้อยกว่า {{ core()->currency($webconfig->mintransfer_pro) }} บาท (กรณีมีการรับโปรไปแล้ว)</small></p>';--}}
        {{--        }--}}

        {{--        var options = {};--}}
        {{--        $.map(inputOptions,--}}
        {{--            function (o) {--}}
        {{--                options[o.value] = o.text;--}}
        {{--            });--}}
        {{--        const {value: formValues} = await Swal.fire({--}}
        {{--            title: "โยกเข้าเกม " + details.name,--}}
        {{--            input: "select",--}}
        {{--            inputOptions: options,--}}
        {{--            inputPlaceholder: "เลือกโปรโมชั่น",--}}
        {{--            html: 'จำนวนเงินที่โยก  <input id="amount" name="amount" class="swal2-input" placeholder="กรุณากรอกจำนวนเงิน" step="0.01" min="1" type="number" value="0">',--}}
        {{--            footer: foottext,--}}
        {{--            preConfirm: async (selectedOption) => {--}}

        {{--                const amount = document.getElementById('amount').value;--}}
        {{--                if (!amount) {--}}
        {{--                    Swal.showValidationMessage(`โปรดระบุจำนวนเงินที่ต้องการโยก`)--}}
        {{--                }--}}
        {{--                if (!selectedOption) {--}}
        {{--                    return new Promise(function (resolve) {--}}
        {{--                        resolve({amount: amount, game: details.code})--}}
        {{--                    });--}}
        {{--                } else {--}}
        {{--                    return new Promise(function (resolve) {--}}
        {{--                        resolve({promotion: selectedOption, amount: amount, game: details.code})--}}
        {{--                    });--}}

        {{--                }--}}


        {{--            },--}}

        {{--            didOpen: function () {--}}

        {{--                Swal.getPopup()?.querySelector('input')?.focus()--}}
        {{--            },--}}
        {{--            showCancelButton: true--}}
        {{--        });--}}


        {{--        if (formValues) {--}}

        {{--            axios.post("{{ route('customer.transfer.game.checkpro') }}", formValues)--}}
        {{--                .then(response => {--}}
        {{--                    if (response.data.success) {--}}

        {{--                        Swal.fire(--}}
        {{--                            'สำเร็จ',--}}
        {{--                            'โยกเงินเข้าเกมสำเร็จแล้ว',--}}
        {{--                            'success'--}}
        {{--                        );--}}

        {{--                    } else {--}}

        {{--                        Swal.fire(--}}
        {{--                            'พบข้อผิดพลาด',--}}
        {{--                            response.data.message,--}}
        {{--                            'error'--}}
        {{--                        );--}}
        {{--                    }--}}
        {{--                })--}}
        {{--                .catch(response => {--}}

        {{--                    Swal.fire(--}}
        {{--                        'การเชื่อมต่อระบบ มีปัญหา',--}}
        {{--                        response.data.message,--}}
        {{--                        'error'--}}
        {{--                    );--}}
        {{--                });--}}


        {{--        }--}}


        {{--    })()--}}
        {{--}--}}


    </script>
@endpush
