<hr class="x-hr-border-glow my-0">

<!-- SECTION03 -->
<section class="section03 pt-1">
    <div class="logosection">
        {!! core()->showImg($webconfig->logo,'img','','','') !!}
    </div>
    <div class="contain03">
        <div class="row m-0">
            <div class="col-12 col-md-6 p-0 leftsec03">
                <div class="leftdetailsec03">
                    <h3 class="text-center">{{ $webconfig->content_header }}</h3><hr class="x-hr-border-glow my-0">
                    {!! $webconfig->content_detail !!} </div>
            </div>
            <div class="col-12 col-md-6 p-0 rightsec03 flexcenter">
                <img src="{{ Storage::url('game_img/' . $games['filepic']).'?'.microtime() }}">
            </div>
            <div class="col-12 p-0">
                <a class="buttonboxsec03" href="{{ route('customer.session.store') }}">
                    <img src="images/icon/regisbtn.gif">
                </a>
            </div>
        </div>
    </div>

</section>
<!-- SECTION03 -->
<hr class="x-hr-border-glow my-0">
