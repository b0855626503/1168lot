@if($config->seamless == 'Y')
    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-primary " onclick="showModalNew({{ $code }},'gameuser')"><i
                class="fas fa-gamepad"></i> Game
        </button>
        <button type="button" class="btn btn-primary  dropdown-toggle dropdown-icon dropdown-toggle-split"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <div class="dropdown-menu" role="menu">
            <a class="dropdown-item" href="javascript:void(0)" onclick="money({{ $code }})">เพิ่ม-ลด Free Credit</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setwallet')">ประวัติการเพิ่ม-ลด
                Free Credit</a>
            <a class="dropdown-item" href="javascript:void(0)"
               onclick="showModalNew({{ $code }},'withdraw')">ประวัติการถอน</a>
        </div>
    </div>
@else

    <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-primary " onclick="showModalNew({{ $code }},'gameuser')"><i
                class="fas fa-gamepad"></i> Game
        </button>
        <button type="button" class="btn btn-primary  dropdown-toggle dropdown-icon dropdown-toggle-split"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <div class="dropdown-menu" role="menu">
            <a class="dropdown-item" href="javascript:void(0)" onclick="money({{ $code }})">เพิ่ม-ลด Credit</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setwallet')">ประวัติการเพิ่ม-ลด
                Credit</a>
            <a class="dropdown-item" href="javascript:void(0)"
               onclick="showModalNew({{ $code }},'transfer')">ประวัติการโยก</a>
            <a class="dropdown-item" href="javascript:void(0)"
               onclick="showModalNew({{ $code }},'withdraw')">ประวัติการถอน</a>
        </div>
    </div>
@endif

