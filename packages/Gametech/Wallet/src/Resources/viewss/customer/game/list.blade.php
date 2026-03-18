@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2 col-sm-12">
                <div class="card text-light card-trans">
                    <div class="card-body py-3 px-2">
                        @if($config->seamless == 'Y')
                            <seamless></seamless>
                        @else
                            <credit></credit>
                        @endif
                    </div>
                </div>

                <div class="card text-light card-trans mt-5">
                    <div class="card-body py-3 px-2">
                        <div class="row">
                            @foreach($games as $i => $item)
                                <div class="col-6 mb-3 col-md-4">
                                    <a href="" target="gametech"
                                       onclick="openPopup('{{$id}}','{{ $item->code }}'); return false;">
                                        <img
                                            loading="lazy"
                                            src="{{ $item->image }}"
                                            data-src="{{ $item->image }}"
                                            class="d-block mx-auto img-fluid img-full"
                                        />
                                        <p class="text-main text-center mb-0 cut-text small">{{ $item->name }}</p>
                                        <p class="mb-0"></p>

                                    </a>
                                </div>
                                {{--                                <gameseamless-list :product="{{ json_encode($item) }}" ref="{{$i}}"--}}
                                {{--                                                   product_id="{{ $id }}" number="{{$i}}"></gameseamless-list>--}}
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{--    <window-portal ref="gamepopup" id="gamepopup"></window-portal>--}}
@endsection

@push('scripts')
    <script>
        var isMobile = false;
        var windowObjectReference = null; // global variable
        var PreviousUrl = ''; /* global variable that will store the
                    url currently in the secondary window */


        function openPopup(id, game) {

            Toast.fire({
                icon: 'info',
                title: 'กำลังทำการเข้าสู่ระบบเกม'
            })

            const w = 900;
            const h = 500;
            const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
            const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);


            axios.post(`{{ route('customer.game.listlogin') }}`, {
                id: id,
                game: game
            }).then(response => {
                if (response.data.success) {
                    if (response.data.url) {
                        PreviousUrl = response.data.url;
                    } else {
                        PreviousUrl = '';
                    }

                    if (PreviousUrl.length) {
                        Toast.fire({
                            icon: 'success',
                            title: 'เข้าสู่ระบบเรียบร้อย กำลังเปิดเกม'
                        })

                        setTimeout(() => {

                            if (windowObjectReference == null || windowObjectReference.closed) {

                                if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
                                    || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4))) {
                                    // alert('mobile');
                                    windowObjectReference = window.open(PreviousUrl, '_blank');
                                    windowObjectReference.focus();

                                } else {
                                    windowObjectReference = window.open(PreviousUrl, "gametech", `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}, top=${y}, left=${x}`);

                                }
                            } else {
                                windowObjectReference.focus();
                            }
                            // windowObjectReference.location.href = PreviousUrl;
                        }, 2000);

                    } else {
                        Toast.fire({
                            icon: 'warning',
                            title: 'ขออภัย เกมนี้ ยังไม่พร้อมให้บริการ'
                        })
                        // windowObjectReference.close();
                    }

                }
            }).catch(err => [err]);

            console.log(PreviousUrl.length);


            // if(url){
            //     windowObjectReference.location.href = url;
            // }

        }

        @if($refill)
        $(document).ready(function () {

            Swal.fire({
                title: 'คุณได้รับสิทธิ์ในการเลือกรับโปร',
                html: "จากยอดเติมล่าสุด {{ $refill->value }} บาท<br>คุณจึงได้รับสิทธิ์ในการเลือกรับโปรสุดพิเศษ<br>กดที่รับโปร เพื่อไปเลือกโปรโมชั่น<br>กดไม่รับโปร เพื่อสละสิทธิ์ใน ยอดเติมล่าสุดนี้<br>** โปรดตัดสินใจการกดเข้าเล่นเกม **",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'รับโปร',
                cancelButtonText: 'ไม่รับโปร',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '{{ route('customer.promotion.index') }}';
                } else {
                    axios.post(`{{ route('customer.promotion.cancel') }}`).then(response => {
                        if (response.data.success) {
                            Toast.fire({
                                icon: 'warning',
                                title: 'คุณเลือกไม่รับโปร คุณจะได้รับ สิทธิ์ในการรับโปร เมื่อมีการฝากเงินเข้ามาในระบบ'
                            })
                        }
                    }).catch(err => [err]);

                }
            })

        });
        @endif
    </script>
    <script type="module">
        // window.app = new Vue({
        //     el: "#app"
        // });
    </script>

@endpush

