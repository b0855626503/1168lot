
<!-- SECTION2 -->
{{--<section class="section02">--}}

<div class="p-1">
    <div class="headsecion2">
        <img src="{{ Storage::url('game_img/' . $games['game']['filepic']).'?'.microtime() }}">
    </div>
    <hr class="x-hr-border-glow my-0">
    <div class="ctpersonal">
        <div class="row text-light">
            <div class="col-md-12">
                <div class="card card-trans">
                    <div class="card-body">

                        <table class="table table-bordered text-sm text-light">
                            <tbody>
                            <tr>
                                <td align="center">ID</td>
                                <td align="center" id="user">{{ $games['user_name'] }}</td>
                                <td style="text-align: center"><a class="user text-primary" href="javascript:void(0)"
                                                                  onclick="copy('user')">[คัดลอก]</a></td>
                            </tr>
                            <tr>
                                <td align="center">PASS</td>
                                <td align="center" id="pass">{{ $games['user_pass'] }}</td>
                                <td style="text-align: center"><a class="user text-primary" href="javascript:void(0)"
                                                                  onclick="copy('pass')">[คัดลอก]</a></td>
                            </tr>
                            <tr>
                                <td colspan="3" align="center">
                                    @if($games['game']['link_ios'])
                                        <a class="btn btn-sm btn-success mx-1" target="_blank"
                                           href="{{ $games['game']['link_ios'] }}"><i class="fab fa-apple"></i> iOS</a>
                                    @endif
                                    @if($games['game']['link_android'])
                                        <a class="btn btn-sm btn-primary mx-1" target="_blank"
                                           href="{{ $games['game']['link_android'] }}"><i class="fab fa-android"></i>
                                            Android</a>
                                    @endif
                                    @if($games['game']['link_web'])
                                        <a class="btn btn-sm btn-secondary mx-1" target="_blank"
                                           href="{{ $games['game']['link_web'] }}"><i class="fas fa-link"></i> Web</a>
                                    @endif


                                    @if($games['game']['autologin'] == 'Y')
                                        <a class="loginbtn mx-1 py-2" target="_blank"
                                           href="{{ route('customer.credit.game.login') }}"><i class="fas fa-link"></i>
                                            Login</a>
                                    @endif
                                </td>

                            </tr>
                            </tbody>
                        </table>



                    </div>
                </div>
            </div>
        </div>


    </div>

</div>
{{--</section>--}}
<!-- SECTION2 -->
{{--<hr class="x-hr-border-glow my-0">--}}
