@extends('wallet::layouts.master')

{{-- page title --}}
@section('title','')



@section('content')

    <div class="spinner-wrapper text-warning sub-page" style="display: none;">
        <div class="w-100" style="position: absolute; text-align: center; transform: translateY(-6em);">
            <img src="{{ url(core()->imgurl($webconfig->logo,'img')) }}" class="anim-spin animate__animated animate__bounce animate__infinite" style="width: 100%; max-width: 20em;">
        </div>
        <div role="status" class="spinner-border" style="width: 5em; height: 5em; border-width: 0.3em;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div class="sub-page sub-footer" style="min-height: 100vh;">
        <div class="container my-3 position-relative">
            <h3 class="text-center txt-game-recommend">เกมแนะนำ</h3>
            <!---->
            <div id="suggestSlider" class="swiper-container mt-4" >
                <div class="swiper-wrapper" id="swiper-wrapper-9c12b2ccdb625da1"
                >
                    <div class="swiper-slide swiper-slide-duplicate" data-swiper-slide-index="7" role="group" aria-label="1 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -300px) rotateX(0deg) rotateY(150deg) scale(1); z-index: -2;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/evoplay/958.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 3; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 0; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-duplicate" data-swiper-slide-index="8" role="group" aria-label="2 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -221.792px) rotateX(0deg) rotateY(110.896deg) scale(1); z-index: -1;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202107161340292929.png" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 2.21792; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 0; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-duplicate swiper-slide-prev" data-swiper-slide-index="9" role="group" aria-label="3 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -143.585px) rotateX(0deg) rotateY(71.7923deg) scale(1); z-index: 0;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/AllWaySpin/AWS_47.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 1.43585; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 0; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-active" data-swiper-slide-index="0" role="group" aria-label="4 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -65.3768px) rotateX(0deg) rotateY(32.6884deg) scale(1); z-index: 0;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202109020949414141.png" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0.653768; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 0; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-next" data-swiper-slide-index="1" role="group" aria-label="5 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -12.831px) rotateX(0deg) rotateY(-6.41548deg) scale(1); z-index: 1;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202205261624111111.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 0.12831; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide" data-swiper-slide-index="2" role="group" aria-label="6 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -91.0387px) rotateX(0deg) rotateY(-45.5193deg) scale(1); z-index: 0;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/Iconic Gaming/cm00040.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 0.910387; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide" data-swiper-slide-index="3" role="group" aria-label="7 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -169.246px) rotateX(0deg) rotateY(-84.6232deg) scale(1); z-index: -1;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202010201222383838.png" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 1.69246; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide" data-swiper-slide-index="4" role="group" aria-label="8 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -247.454px) rotateX(0deg) rotateY(-123.727deg) scale(1); z-index: -1;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/Funky/iconsize308x166_En.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 2.47454; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide" data-swiper-slide-index="5" role="group" aria-label="9 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -325.662px) rotateX(0deg) rotateY(-162.831deg) scale(1); z-index: -2;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202203071423353535.png" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 3.25662; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide" data-swiper-slide-index="6" role="group" aria-label="10 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -403.87px) rotateX(0deg) rotateY(-201.935deg) scale(1); z-index: -3;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202202021404343434.png" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 4.0387; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide" data-swiper-slide-index="7" role="group" aria-label="11 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -482.077px) rotateX(0deg) rotateY(-241.039deg) scale(1); z-index: -4;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/evoplay/958.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 4.82077; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide" data-swiper-slide-index="8" role="group" aria-label="12 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -560.285px) rotateX(0deg) rotateY(-280.143deg) scale(1); z-index: -5;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202107161340292929.png" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 5.60285; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-duplicate-prev" data-swiper-slide-index="9" role="group" aria-label="13 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -638.493px) rotateX(0deg) rotateY(-319.246deg) scale(1); z-index: -5;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/AllWaySpin/AWS_47.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 6.38493; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-duplicate swiper-slide-duplicate-active" data-swiper-slide-index="0" role="group" aria-label="14 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -716.701px) rotateX(0deg) rotateY(-358.35deg) scale(1); z-index: -6;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202109020949414141.png" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 7.16701; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-duplicate swiper-slide-duplicate-next" data-swiper-slide-index="1" role="group" aria-label="15 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -794.908px) rotateX(0deg) rotateY(-397.454deg) scale(1); z-index: -7;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/slot_202205261624111111.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 7.94908; transition-duration: 0ms;"></div>
                    </div>
                    <div class="swiper-slide swiper-slide-duplicate" data-swiper-slide-index="2" role="group" aria-label="16 / 16" style="width: 163.667px; transition-duration: 0ms; transform: translate3d(0px, 0px, -873.116px) rotateX(0deg) rotateY(-436.558deg) scale(1); z-index: -8;">
                        <img src="https://s3-ap-southeast-1.amazonaws.com/cdn.ambbet.com/Iconic Gaming/cm00040.jpg" class="w-100" style="max-height: 12em; object-fit: contain;">
                        <div class="swiper-slide-shadow-left" style="opacity: 0; transition-duration: 0ms;"></div>
                        <div class="swiper-slide-shadow-right" style="opacity: 8.73116; transition-duration: 0ms;"></div>
                    </div>
                </div>
                <div class="swiper-pagination swiper-pagination-bullets">
                    <span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span>
                    <span class="swiper-pagination-bullet"></span>
                    <span class="swiper-pagination-bullet"></span>
                    <span class="swiper-pagination-bullet"></span>
                    <span class="swiper-pagination-bullet"></span>
                    <span class="swiper-pagination-bullet"></span>
                    <span class="swiper-pagination-bullet"></span>
                    <span class="swiper-pagination-bullet"></span>
                    <span class="swiper-pagination-bullet"></span>
                    <span class="swiper-pagination-bullet"></span>
                </div>
                <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
                <span class="swiper-notification" aria-live="assertive" aria-atomic="true"></span>
            </div>
            <hr>
            <!---->
            <div class="row g-2">
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/pg_slot" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">{{ $name }}</div>
                            <img src="images/game/slot/pgslot.png">
                        </div>
                    </a>
                </div>
                @if(isset($games))
                    @foreach($games as $k => $item)
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="{{ route('customer.game.list', ['id' => Str::lower($item->id)]) }}" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">{{ $item->name }}</div>
                            <img src="https://file-upload.cloud/vendor-2/img/slot/upg.png">
                        </div>
                    </a>
                </div>
                    @endforeach
                @endif

                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/pragmatic" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Pragmatic</div>
                            <img src="images/game/slot/pragmatic.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/spade_gaming" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Spade Gaming</div>
                            <img src="images/game/slot/spadegaming.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/ambslot" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">ambSlot</div>
                            <img src="images/game/slot/ambslot.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/askmebet" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Askmebet</div>
                            <img src="images/game/slot/askmebet.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/evoplay01" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">EvoPlay</div>
                            <img src="images/game/slot/evoplay.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/gamatron" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Gamatron</div>
                            <img src="images/game/slot/gamatron.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/ka_gaming" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">KA Gaming</div>
                            <img src="images/game/slot/kagaming.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/manna_play" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Manna Play</div>
                            <img src="images/game/slot/mannaplay.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/booongo" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Booongo</div>
                            <img src="images/game/slot/booongo.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/allwayspin" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Allwayspin</div>
                            <img src="images/game/slot/allwayspin.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/iconic_gaming" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Iconic Gaming</div>
                            <img src="images/game/slot/iconicgaming.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/wazdan_direct" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Wazdan Direct</div>
                            <img src="images/game/slot/wazdandirect.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/funta_gaming" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Funta Gaming</div>
                            <img src="images/game/slot/funtagaming.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/funkyGames" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">FunkyGames</div>
                            <img src="images/game/slot/funkygames.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/jili" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Jili</div>
                            <img src="images/game/slot/jili.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/slotxo" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">SlotXO</div>
                            <img src="images/game/slot/slotxo.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/ameba" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Ameba</div>
                            <img src="images/game/slot/ameba.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/live_22" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Live 22</div>
                            <img src="images/game/slot/live22.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/simpleplay" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Simple Play</div>
                            <img src="images/game/slot/simpleplay.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/micro_gaming" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">Micro Gaming</div>
                            <img src="images/game/slot/microgame.png">
                        </div>
                    </a>
                </div>
                <div class="game-item col-6 col-md-3 col-lg-2 game-type" style="cursor: pointer;">
                    <a href="#/games/slot/yggdrasil" class="btn btn-img">
                        <div class="game-preview">
                            <div class="preview-head text-content">yggdrasil</div>
                            <img src="https://file-upload.cloud/vendor-2/img/slot/yggdrasil.png">
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection




