<div id="main__content" data-bgset="/assets/wm356/images/index-bg.jpg?v=2"
     class="lazyload x-bg-position-center x-bg-index lazyload">

    <div class="js-replace-cover-seo-container">
        <div class="x-cover -small x-cover-promotion lazyload x-bg-position-center"
             data-bgset="/assets/wm356/images/cover-bg-promotion.png?v=2">
            <div class="x-cover-template-full">
                <div class="container -container-wrapper">
                    <div class="-row-wrapper">
                        <div class="-col-wrapper -first" data-animatable="fadeInModal">
                            <div class="x-cover-typography">
                                <h1 class="-title">{{ $webconfig->content_header }}</h1>

                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="x-promotion-index">
        <div class="container">
            <div class="row px-2">
                @foreach($promotions as $i => $item)
                    <div class="col-lg-4 col-6 -promotion-card-link" data-animatable="fadeInUp"
                         data-delay="{{ 100 * $i }}">
                        <a
                            class="d-block h-100"
                            data-toggle="modal" data-target="#promotionmodal{{ $i }}"
                        >
                            <div class="x-card card -multi-card lazyload x-bg-position-center"
                                 data-bgset="/assets/wm356/images/card-promotion-bg.jpg?v=2">
                                <div class="-img-container">
                                    <img src="{{  Storage::url('promotion_img/'.$item->filepic)  }}"
                                         alt="{{ $item->name_th }}" class="-img"/>
                                </div>
                                <div class="card-body">
                                    <div class="-title-container m-3">
                                        <h3 class="-title">{{ $item->name_th }}</h3>
                                    </div>
                                </div>

                            </div>
                        </a>
                    </div>
                @endforeach
                    @foreach($pro_contents as $i => $item)
                        <div class="col-lg-4 col-6 -promotion-card-link" data-animatable="fadeInUp"
                             data-delay="{{ 100 * $i }}">
                            <a
                                    class="d-block h-100"
                                    data-toggle="modal" data-target="#promotionmodals{{ $i }}"
                            >
                                <div class="x-card card -multi-card lazyload x-bg-position-center"
                                     data-bgset="/assets/wm356/images/card-promotion-bg.jpg?v=2">
                                    <div class="-img-container">
                                        <img src="{{  Storage::url('procontent_img/'.$item->filepic)  }}"
                                             alt="{{ $item->name_th }}" class="-img"/>
                                    </div>
                                    <div class="card-body">
                                        <div class="-title-container m-3">
                                            <h3 class="-title">{{ $item->name_th }}</h3>
                                        </div>
                                    </div>
                                    <div class="card-footer">

                                    </div>

                                </div>
                            </a>
                        </div>
                    @endforeach
            </div>
        </div>

    </div>

    @foreach($promotions as $i => $item)
        <div class="x-modal modal -v2 -with-backdrop -with-separator -with-more-than-half-size"
             id="promotionmodal{{ $i }}"
             tabindex="-1"
             role="dialog"
             data-loading-container=".modal-body"
             data-ajax-modal-always-reload="true"
             data="deposit"
             data-container="#promotionmodal{{ $i }}"
             style="display: none;"
             aria-hidden="true">

            <div
                class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-single-card"
                role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header"></div>
                    <div class="modal-body -modal-body">
                        <div class="d-flex flex-column">
                            <div class="-real-content">
                                <div
                                    class="x-card card -single-card x-bg-position-center lazyloaded"
                                    data-bgset="/assets/wm356/images/card-promotion-bg.jpg?v=2"
                                    style="background-image: url('/assets/wm356/images/card-promotion-bg.jpg?v=2');"
                                >
                                    <div class="-img-container">
                                        <img src="{{  Storage::url('promotion_img/'.$item->filepic)  }}"
                                             alt="{{ $item->name_th }}" class="-img"/>
                                    </div>
                                    <div class="card-body">
                                        <div class="-title-container m-3">
                                            <h3 class="-title">{{ $item->name_th }}</h3>
                                        </div>
                                        <div class="-promotion-content p-3">
                                            {!! $item->content !!}
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <form method="POST" action="{{ route('customer.promotion.select') }}">
                                            @csrf
                                            <input type="hidden" name="promotion" value="{{ $item->code }}">

                                            <button class="btn -btn btn-success" type="submit"><span>{{ __('app.promotion.choose') }}</span></button>

                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach


    @foreach($pro_contents as $i => $item)
        <div class="x-modal modal -v2 -with-backdrop -with-separator -with-more-than-half-size"
             id="promotionmodals{{ $i }}"
             tabindex="-1"
             role="dialog"
             data-loading-container=".modal-body"
             data-ajax-modal-always-reload="true"
             data="deposit"
             data-container="#promotionmodal{{ $i }}"
             style="display: none;"
             aria-hidden="true">

            <div
                    class="modal-dialog -modal-size -v2 modal-dialog-centered modal-dialog-scrollable -modal-single-card"
                    role="document">
                <div class="modal-content -modal-content">
                    <button type="button" class="close f-1 -in-tab" data-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header -modal-header"></div>
                    <div class="modal-body -modal-body">
                        <div class="d-flex flex-column">
                            <div class="-real-content">
                                <div
                                        class="x-card card -single-card x-bg-position-center lazyloaded"
                                        data-bgset="/assets/wm356/images/card-promotion-bg.jpg?v=2"
                                        style="background-image: url('/assets/wm356/images/card-promotion-bg.jpg?v=2');"
                                >
                                    <div class="-img-container">
                                        <img src="{{  Storage::url('procontent_img/'.$item->filepic)  }}"
                                             alt="{{ $item->name_th }}" class="-img"/>
                                    </div>
                                    <div class="card-body">
                                        <div class="-title-container m-3">
                                            <h3 class="-title">{{ $item->name_th }}</h3>
                                        </div>
                                        <div class="-promotion-content p-3">
                                            {!! $item->content !!}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</div>
