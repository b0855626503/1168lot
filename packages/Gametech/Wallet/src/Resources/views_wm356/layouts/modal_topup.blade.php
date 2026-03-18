<script type="text/x-template" id="topup-slip-top-template">
    <div class="x-modal modal -v2 -with-backdrop -with-separator -with-more-than-half-size"
         id="depositModal"
         tabindex="-1"
         role="dialog"
         data-loading-container=".modal-body"
         data-ajax-modal-always-reload="true"
         data="deposit"
         data-container="#depositModal"
         style="display: none;"
         aria-hidden="true">
        <div class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-deposit -modal-mobile"
             role="document">

            <div class="modal-content -modal-content">
                <button type="button" class="close f-1" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                <div class="modal-header -modal-header">
                    <h3 class="x-title-modal m-auto">
                        {{ __('app.home.topup_channel') }}
                    </h3>
                </div>


                <div class="modal-body -modal-body">
                    <div class="x-deposit-form -v2">
                        <div class="-deposit-container">
                            <div data-animatable="fadeInModal" class="order-lg-2 -form order-0 animated fadeInModal">
                                <div class="container">

                                    <div class="el-input my-1">

                                        <div class="x-deposit-promotion-outer-container js-scroll-ltr -fade -on-left -on-right">
                                            <div class="x-deposit-promotion -v2 -slide pt-0 -has-promotion"
                                                 data-scroll-booster-container=".x-deposit-promotion-outer-container"
                                                 data-scroll-booster-content=".x-deposit-promotion"
                                                 style="transform: translate(0px, 0px);">

                                                @if(count($topupbanks) > 0)
                                                    <div class="-promotion-box-wrapper">
                                                        <button type="button"
                                                                onclick="topupSelect('topup_bank')"
                                                                class="btn -promotion-box-apply-btn js-promotion-apply btn-for-deposit"
                                                                data-url="/promotion/2/apply" data-type="deposit"
                                                                data-display-slide-mode="true">
                                                            <picture>
                                                                <source type="image/webp"
                                                                        srcset="https://img2.pic.in.th/pic/bank19da438c9e295f0b.png"/>
                                                                <source type="image/png"
                                                                        srcset="https://img2.pic.in.th/pic/bank19da438c9e295f0b.png"/>
                                                                <img class="-img img50" alt="BONUS" width="26"
                                                                     height="26"
                                                                     loading="lazy" fetchpriority="low"
                                                                     src="https://img2.pic.in.th/pic/bank19da438c9e295f0b.png"/>
                                                            </picture>

                                                            <span class="-title">{{ __('app.home.topup_bank') }}</span>

                                                        </button>
                                                        <a href="javascript:void(0)"
                                                           class="-promotion-box-cancel-btn js-cancel-promotion"

                                                           data-display-slide-mode="true">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    </div>
                                                @endif

                                                @if(count($topuptws) > 0)
                                                    <div class="-promotion-box-wrapper">
                                                        <button type="button"
                                                                onclick="topupSelect('topup_tw')"
                                                                class="btn -promotion-box-apply-btn js-promotion-apply btn-for-deposit"
                                                                data-url="/promotion/2/apply" data-type="deposit"
                                                                data-display-slide-mode="true">
                                                            <picture>
                                                                <source type="image/webp"
                                                                        srcset="https://img2.pic.in.th/pic/twa6cf4bb54c16ae4b.png"/>
                                                                <source type="image/png"
                                                                        srcset="https://img2.pic.in.th/pic/twa6cf4bb54c16ae4b.png"/>
                                                                <img class="-img img50" alt="BONUS" width="26"
                                                                     height="26"
                                                                     loading="lazy" fetchpriority="low"
                                                                     src="https://img2.pic.in.th/pic/twa6cf4bb54c16ae4b.png"/>
                                                            </picture>

                                                            <span class="-title">{{ __('app.home.topup_wallet') }}</span>

                                                        </button>
                                                        <a href="javascript:void(0)"
                                                           class="-promotion-box-cancel-btn js-cancel-promotion"

                                                           data-display-slide-mode="true">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    </div>
                                                @endif

                                                @if($config->qrscan == 'Y')
                                                    <div class="-promotion-box-wrapper">
                                                        <button type="button"
                                                                onclick="topupSelect('topup_qrscan')"
                                                                class="btn -promotion-box-apply-btn js-promotion-apply btn-for-deposit"
                                                                data-url="/promotion/2/apply" data-type="deposit"
                                                                data-display-slide-mode="true">
                                                            <picture>
                                                                <source type="image/webp"
                                                                        srcset="https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png"/>
                                                                <source type="image/png"
                                                                        srcset="https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png"/>
                                                                <img class="-img img50" alt="BONUS" width="26"
                                                                     height="26"
                                                                     loading="lazy" fetchpriority="low"
                                                                     src="https://img5.pic.in.th/file/secure-sv1/qr0068bdbf0cc6226d.png"/>
                                                            </picture>

                                                            <span class="-title">{{ __('app.home.topup_luckypay') }}</span>

                                                        </button>
                                                        <a href="javascript:void(0)"
                                                           class="-promotion-box-cancel-btn js-cancel-promotion"

                                                           data-display-slide-mode="true">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    </div>
                                                @endif

                                                <topup-slip></topup-slip>

                                            </div>
                                        </div>

                                    </div>


                                    <div id="topup_bank" class="-deposit-form-inner-wrapper table-responsive-new"
                                         style="display:none">
                                        @foreach($topupbanks as $bank)
                                            @foreach($bank['banks_account'] as $item)

                                                <div class="-bank-info-container mt-3 ml-3 mr-3">
                                                    <div class="x-customer-bank-info-container -v2">
                                                        <div class="media m-auto">
                                                            <img loading="lazy" fetchpriority="low"
                                                                 src="{{ $bank['filepic'] }}"
                                                                 class="-img rounded-circle" width="50" height="50"
                                                                 alt="bank-ktb"/>
                                                            <div class="-content-wrapper">
                                                                <span class="-name">{{ $bank['name_th'] }}</span>
                                                                <span class="-name">{{ $item['acc_name'] }}</span>
                                                                <span class="-number">{{$item['acc_no'] }}</span>
                                                                <button onclick="copylink()"
                                                                        class="btncopy btn btn-flat"
                                                                        data-clipboard-text="{{ $item['acc_no'] }}"><i
                                                                            class="fa fa-copy"></i> {{ __('app.con.copy') }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                        @if($item['qrcode'] === 'Y')

                                                            <div class="media m-auto">
                                                                <img loading="lazy" fetchpriority="low"
                                                                     src="{{ $bank['filepic2'] }}"
                                                                     class="img-fluid"
                                                                     alt="bank-ktb"/>
                                                            </div>

                                                        @endif
                                                    </div>

                                                </div>

                                            @endforeach
                                        @endforeach
                                        <div class="-bank-info-container mt-3 ml-3 mr-3 text-center">
                                            <small>{{ __('app.topup.remark') }}</small>
                                        </div>
                                    </div>

                                    <div id="topup_tw" class="-deposit-form-inner-wrapper table-responsive-new"
                                         style="display:none">
                                        @foreach($topuptws as $bank)
                                            @foreach($bank['banks_account'] as $item)

                                                <div class="-bank-info-container mt-3 ml-3 mr-3">
                                                    <div class="x-customer-bank-info-container -v2">
                                                        <div class="media m-auto">
                                                            <img loading="lazy" fetchpriority="low"
                                                                 src="{{ $bank['filepic'] }}"
                                                                 class="-img rounded-circle" width="50" height="50"
                                                                 alt="bank-ktb"/>
                                                            <div class="-content-wrapper">
                                                                <span class="-name">{{ $bank['name_th'] }}</span>
                                                                <span class="-name">{{ $item['acc_name'] }}</span>
                                                                <span class="-number">{{$item['acc_no'] }}</span>
                                                                <button onclick="copylink()"
                                                                        class="btncopy btn btn-flat"
                                                                        data-clipboard-text="{{ $item['acc_no'] }}"><i
                                                                            class="fa fa-copy"></i> {{ __('app.con.copy') }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                        @if($item['qrcode'] === 'Y')

                                                            <div class="media m-auto">
                                                                <img loading="lazy" fetchpriority="low"
                                                                     src="{{ $bank['filepic2'] }}"
                                                                     class="img-fluid"
                                                                     alt="bank-ktb"/>
                                                            </div>

                                                        @endif
                                                    </div>

                                                </div>

                                            @endforeach
                                        @endforeach
                                        <div class="-bank-info-container mt-3 ml-3 mr-3 text-center">
                                            <small>{{ __('app.topup.remark') }}</small>
                                        </div>
                                    </div>

                                    @if($config->qrscan == 'Y')
                                        <div id="topup_qrscan" class="-deposit-form-inner-wrapper" style="display:none">

                                            <br>
                                            {{--                                        <p class="text-center">{{ __('app.topup.papayapay_detail_1') }}</p>--}}
                                            {{--                                        @auth--}}
                                            {{--                                            <div class="-bank-info-container">--}}
                                            {{--                                                <div class="x-customer-bank-info-container -v2">--}}
                                            {{--                                                    <div class="media m-auto">--}}
                                            {{--                                                        <img loading="lazy" fetchpriority="low"--}}
                                            {{--                                                             src="{{ Storage::url('bank_img/' . $userdata->bank->filepic) }}"--}}
                                            {{--                                                             class="-img rounded-circle" width="50" height="50"--}}
                                            {{--                                                             alt="bank-ktb"/>--}}
                                            {{--                                                        <div class="-content-wrapper">--}}
                                            {{--                                                            <span class="-name">{{ $userdata->name }}</span>--}}
                                            {{--                                                            <span class="-number">{{ $userdata->acc_no }}</span>--}}
                                            {{--                                                        </div>--}}
                                            {{--                                                    </div>--}}
                                            {{--                                                </div>--}}
                                            {{--                                            </div>--}}
                                            {{--                                        @endauth--}}
                                            <form target="_blank" novalidate="" id="frmqrscan" name="deposit"
                                                  method="post"
                                                  class="qrscan"
                                                  action="{{ route('customer.topup.visapay') }}"
                                                  onsubmit="return false;">
                                                @csrf
                                                <div class="-fake-bg-bottom-wrapper">
                                                    <div class="x-modal-separator-container">
                                                        <div class="-top">
                                                            <div class="-promotion-intro-deposit -spacer">
                                                                <div class="js-promotion-active-html"></div>
                                                            </div>

                                                            {{--                                                        <div class="-spacer">--}}
                                                            {{--                                                            <div class="js-turnover text-center">--}}
                                                            {{--                                                                <div class="-turnover-wrapper">Rate : <span>{{ $config->rate }}</span>--}}
                                                            {{--                                                                    --}}{{--                                                                <div class="-turnover-wrapper">Last Update : <span>{{ $config->rate_update }}</span>--}}
                                                            {{--                                                                </div>--}}
                                                            {{--                                                            </div>--}}
                                                            {{--                                                        </div>--}}

                                                            <div class="-spacer pt-2">
                                                                <div
                                                                        class="-x-input-icon x-input-operator mb-3 flex-column">
                                                                    <button type="button"
                                                                            class="-icon-left -btn-icon js-adjust-amount-by-operator"
                                                                            data-operator="-" data-value="1">
                                                                        <i class="fas fa-minus-circle"></i>
                                                                    </button>

                                                                    <input
                                                                            type="text"
                                                                            id="deposit_amount"
                                                                            name="amount"
                                                                            required="required"
                                                                            pattern="[0-9]*"
                                                                            value="200"
                                                                            class="x-form-control -no text-center js-deposit-input-amount form-control"
                                                                            placeholder="ยอดฝากขั้นต่ำ 200"
                                                                            inputmode="text"
                                                                    />
                                                                    <button type="button"
                                                                            class="-icon-right -btn-icon js-adjust-amount-by-operator"
                                                                            data-operator="+" data-value="1">
                                                                        <i class="fas fa-plus-circle"></i>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="-spacer">
                                                                <div class="x-select-amount js-quick-amount -v2"
                                                                     data-target-input="#deposit_amount">

                                                                    {{--                                                                <div class="-amount-container">--}}
                                                                    {{--                                                                    <button type="button"--}}
                                                                    {{--                                                                            class="btn btn-block -btn-select-amount"--}}
                                                                    {{--                                                                            data-amount="100">--}}
                                                                    {{--                                                                        <span class="-no">100</span>--}}
                                                                    {{--                                                                    </button>--}}
                                                                    {{--                                                                </div>--}}
                                                                    {{--                                                                <div class="-amount-container">--}}
                                                                    {{--                                                                    <button type="button"--}}
                                                                    {{--                                                                            class="btn btn-block -btn-select-amount"--}}
                                                                    {{--                                                                            data-amount="200">--}}
                                                                    {{--                                                                        <span class="-no">200</span>--}}
                                                                    {{--                                                                    </button>--}}
                                                                    {{--                                                                </div>--}}
                                                                    <div class="-amount-container">
                                                                        <button type="button"
                                                                                class="btn btn-block -btn-select-amount"
                                                                                data-amount="200">
                                                                            <span class="-no">200</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="-amount-container">
                                                                        <button type="button"
                                                                                class="btn btn-block -btn-select-amount"
                                                                                data-amount="300">
                                                                            <span class="-no">300</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="-amount-container">
                                                                        <button type="button"
                                                                                class="btn btn-block -btn-select-amount"
                                                                                data-amount="400">
                                                                            <span class="-no">400</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="-amount-container">
                                                                        <button type="button"
                                                                                class="btn btn-block -btn-select-amount"
                                                                                data-amount="500">
                                                                            <span class="-no">500</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="-amount-container">
                                                                        <button type="button"
                                                                                class="btn btn-block -btn-select-amount"
                                                                                data-amount="1000">
                                                                            <span class="-no">1000</span>
                                                                        </button>
                                                                    </div>
                                                                    <div class="-amount-container">
                                                                        <button type="button"
                                                                                class="btn btn-block -btn-select-amount"
                                                                                data-amount="1500">
                                                                            <span class="-no">1500</span>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="-spacer">
                                                                <hr class="-liner"/>
                                                            </div>

                                                            <div class="-bank-info-container mt-3 ml-3 mr-3"
                                                                 style="color:yellow">
                                                                {{--                                            <small>{{ __('app.topup.hengpay_detail_1') }} </small><br>--}}
                                                                <small>{{ __('app.topup.papayapay_detail_1') }} </small><br>
                                                                <small>{{ __('app.topup.papayapay_detail_2') }} </small><br>
                                                                <small>{{ __('app.topup.papayapay_detail_3') }} </small><br>
                                                            </div>
                                                            <div class="text-center -spacer">
                                                                <button type="submit"
                                                                        class="btn btn-primary my-0 my-lg-3">
                                                                    {{ __('app.login.submit') }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="-bottom"></div>
                                                    </div>
                                                </div>

                                            </form>


                                        </div>
                                    @endif


                                    <topup-slip-content></topup-slip-content>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</script>