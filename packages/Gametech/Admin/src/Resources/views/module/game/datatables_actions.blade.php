<div class="btn-group btn-group-sm">
    <button type="button" class="btn btn-info" onclick="editModal({{ $code }})"><i class="fa fa-edit"></i> แก้ไข
    </button>
    <button type="button" class="btn btn-primary dropdown-toggle dropdown-icon dropdown-toggle-split"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    @if($admin = auth()->guard('admin')->user()->superadmin === 'Y')
        <div class="dropdown-menu" role="menu">
            <h6 class="dropdown-header">Real User</h6>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug({{ $code }},'add')">Add User</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug({{ $code }},'pass')">Edit Pass</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug({{ $code }},'balance')">View Balance</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug({{ $code }},'deposit')">Deposit</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug({{ $code }},'withdraw')">Withdraw</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug({{ $code }},'login')">Login</a>
            <div class="dropdown-divider"></div>
            <h6 class="dropdown-header">Free User</h6>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug_free({{ $code }},'add')">Add User</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug_free({{ $code }},'pass')">Edit Pass</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug_free({{ $code }},'balance')">View
                Balance</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug_free({{ $code }},'deposit')">Deposit</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="debug_free({{ $code }},'withdraw')">Withdraw</a>
        </div>
    @endif
</div>
