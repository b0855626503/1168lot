@if($status == 0 && $balance <> 0)
    <button class="btn btn-xs btn-secondary icon-only"
            onclick="addCredit({{ $code }},'{{ $member_code }}','{{ $upline_code }}','{{ $balance }}','{{ $bonus }}','{{ $date_cashback }}')">
        <i class="fas fa-plus"></i>
    </button>
@endif
