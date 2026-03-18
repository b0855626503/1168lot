<footer class="main-footer ml-0 p-0 mt-5">
    <div class="navigation nav-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="navigation-nav mt-2">
                        <div class="list-inline-item d-flex align-items-end text-center">
                            <a href="{{ route('customer.home.index') }}"
                               class="{{ $menu->getFrontActive('customer.home.index') }}"><i
                                    class="fas fa-home mb-0"></i><br>หน้าแรก</a>
                        </div>
                        @if($config->seamless == 'Y')
                            <div class="list-inline-item d-flex align-items-end text-center">
                                <a href="{{ route('customer.credit.index') }}"
                                   class="{{ $menu->getFrontActive('customer.credit.index') }}">
                                    <i class="fas fa-coins mb-0"></i><br>ฟรีเครดิต</a>
                            </div>
                        @else
                            <div class="list-inline-item d-flex align-items-end text-center">
                                <a href="{{ route('customer.profile.index') }}"
                                   class="{{ $menu->getFrontActive('customer.profile.index') }}">
                                    <i class="fas fa-user mb-0"></i><br>บัญชี</a>
                            </div>
                        @endif
                        @if($config->seamless == 'Y')
                            @if(request()->routeIs('customer.credit.*'))
                                <div class="list-inline-item d-flex align-items-end text-center">
                                    <a class="exchange text-center {{ $menu->getFrontActive('customer.credit.game.index') }}"
                                       href="{{ route('customer.credit.game.index') }}">
                                        <i class="fas fa-gamepad fa-5x mb-0 blue"></i>
                                        <p class="text-center"><br>เล่นเกม</p>
                                    </a>
                                </div>
                            @else

                                <div class="list-inline-item d-flex align-items-end text-center">
                                    <a class="exchange text-center {{ $menu->getFrontActive('customer.game.index') }}"
                                       href="{{ route('customer.game.index') }}">
                                        <i class="fas fa-gamepad fa-5x mb-0"></i>
                                        <p class="text-center"><br>เล่นเกม</p>
                                    </a>
                                </div>


                            @endif

                        @else
                            @if($config->multigame_open == 'Y')
                                <div class="list-inline-item d-flex align-items-end text-center">
                                    <a class="exchange text-center {{ $menu->getFrontActive('customer.transfer.game.index') }}"
                                       href="{{ route('customer.transfer.game.index') }}">
                                        <i class="fas fa-usd-circle fa-5x mb-0"></i>
                                        <p class="text-center"><br>โยกเงิน</p>
                                    </a>
                                </div>
                            @else

                                @if($config->onegame == 'Y')
                                    <div class="list-inline-item d-flex align-items-end text-center">
                                        <a class="exchange-single text-center"
                                           href="" onclick="openPopupNew('{{$single->id}}'); return false;">
                                            <img class="mb-0" src="{{ $single->image }}">
                                        </a>
                                    </div>
                                @else
                                    <div class="list-inline-item d-flex align-items-end text-center">
                                        <a class="exchange-single text-center"
                                           href="{{ route('customer.game.index') }}">
                                            <img class="mb-0" src="{{ $single->image }}">
                                        </a>
                                    </div>
                                @endif


                            @endif
                        @endif
                        @if(request()->routeIs('customer.credit.*'))
                            <div class="list-inline-item d-flex align-items-end text-center">
                                <a href="{{ route('customer.credit.withdraw.index') }}"
                                   class="{{ $menu->getFrontActive('customer.credit.withdraw.index') }}">
                                    <i class="fas fa-hand-holding-usd mb-0"></i><br>ถอนเงิน</a>
                            </div>
                        @else
                            <div class="list-inline-item d-flex align-items-end text-center">
                                <a href="{{ route('customer.withdraw.index') }}"
                                   class="{{ $menu->getFrontActive('customer.withdraw.index') }}">
                                    <i class="fas fa-hand-holding-usd mb-0"></i><br>ถอนเงิน</a>
                            </div>
                        @endif
                        <div class="list-inline-item d-flex align-items-end text-center">
                            <a target="_blank" href="{{ $config->linelink }}">
                                <i class="fas fa-comments mb-0"></i>
                                <br>แชทสด </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
@if($config->onegame == 'Y')
    @push('scripts')
        <script>
            var isMobile = false;
            var windowObjectReference = null; // global variable
            var PreviousUrl = ''; /* global variable that will store the
                    url currently in the secondary window */


            function openPopupNew(id) {

                Toast.fire({
                    icon: 'info',
                    title: 'กำลังทำการเข้าสู่ระบบเกม'
                })

                const w = 900;
                const h = 500;
                const y = window.top.outerHeight / 2 + window.top.screenY - (h / 2);
                const x = window.top.outerWidth / 2 + window.top.screenX - (w / 2);


                axios.post(`{{ route('customer.game.listloginnew') }}`, {
                    id: id
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

        </script>

    @endpush
@endif
