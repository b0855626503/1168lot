<div class="card text-light card-trans mt-5">
    <div class="card-body py-3 px-2">
        <table class="table table-bordered text-sm">
            <thead>
            <tr>
                <th style="text-align: center" colspan="3">{{ $game['game']['name'] }}</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td align="center">Username</td>
                <td align="center" id="user">{{ $game['user_name'] }}</td>
                <td style="text-align: center"><a class="user text-primary" href="javascript:void(0)"
                                                  onclick="copy('user')">[คัดลอก]</a></td>
            </tr>
            <tr>
                <td align="center">Password</td>
                <td align="center" id="pass">{{ $game['user_pass'] }}</td>
                <td style="text-align: center"><a class="user text-primary" href="javascript:void(0)"
                                                  onclick="copy('pass')">[คัดลอก]</a></td>
            </tr>
            <tr>
                <td colspan="3" align="center">
                    @if($game['game']['link_ios'])
                        <a class="btn btn-sm btn-success mx-1" target="_blank"
                           href="{{ $game['game']['link_ios'] }}"><i class="fab fa-apple"></i> iOS</a>
                    @endif
                    @if($game['game']['link_android'])
                        <a class="btn btn-sm btn-primary mx-1" target="_blank"
                           href="{{ $game['game']['link_android'] }}"><i class="fab fa-android"></i>
                            Android</a>
                    @endif
                    @if($game['game']['link_web'])
                        <a class="btn btn-sm btn-secondary mx-1" target="_blank"
                           href="{{ $game['game']['link_web'] }}"><i class="fas fa-link"></i> Web</a>
                    @endif

                    <button class="btn btn-sm btn-info mx-1" type="button"
                            onclick=""><i
                            class="fas fa-key text-light"></i> เปลี่ยนรหัส
                    </button>

                    @if($game['game']['autologin'] == 'Y')
                        <a class="btn btn-sm btn-primary mx-1" target="_blank"
                           href="{{ route('customer.game.login') }}"><i class="fas fa-link"></i>
                            Login</a>
                    @endif
                </td>

            </tr>
            </tbody>
        </table>
    </div>
</div>
