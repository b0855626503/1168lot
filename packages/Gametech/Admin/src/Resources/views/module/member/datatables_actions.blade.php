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
            <a class="dropdown-item" href="javascript:void(0)" onclick="refill({{ $code }})">ทำรายการฝากเงิน</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="money({{ $code }})">เพิ่ม-ลด เครดิต</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="point({{ $code }})">เพิ่ม-ลด Point</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="diamond({{ $code }})">เพิ่ม-ลด Diamond</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setwallet')">ประวัติการเพิ่ม-ลด
                เครดิต</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setpoint')">ประวัติการเพิ่ม-ลด
                Point</a>
            <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setdiamond')">ประวัติการเพิ่ม-ลด
                Diamond</a>
            <a class="dropdown-item" href="javascript:void(0)"
               onclick="showModalNew({{ $code }},'deposit')">ประวัติการฝาก</a>
            <a class="dropdown-item" href="javascript:void(0)"
               onclick="showModalNew({{ $code }},'withdraw')">ประวัติการถอน</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="javascript:void(0)" onclick="editModal({{ $code }})">แก้ไขข้อมูล</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="javascript:void(0)" onclick="commentModal({{ $code }})">เพิ่มหมายเหตุ</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="javascript:void(0)" onclick="delModal({{ $code }})">ลบข้อมูล</a>
        </div>
    </div>
@else

    @if($config->multigame_open == 'Y')
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-primary " onclick="showModalNew({{ $code }},'gameuser')"><i
                    class="fas fa-gamepad"></i> Game
            </button>
            <button type="button" class="btn btn-primary  dropdown-toggle dropdown-icon dropdown-toggle-split"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <div class="dropdown-menu" role="menu">
                <a class="dropdown-item" href="javascript:void(0)" onclick="refill({{ $code }})">ทำรายการฝากเงิน</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="money({{ $code }})">เพิ่ม-ลด Wallet</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="point({{ $code }})">เพิ่ม-ลด Point</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="diamond({{ $code }})">เพิ่ม-ลด Diamond</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setwallet')">ประวัติการเพิ่ม-ลด
                    Wallet</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setpoint')">ประวัติการเพิ่ม-ลด
                    Point</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setdiamond')">ประวัติการเพิ่ม-ลด
                    Diamond</a>
                <a class="dropdown-item" href="javascript:void(0)"
                   onclick="showModalNew({{ $code }},'transfer')">ประวัติการโยก</a>
                <a class="dropdown-item" href="javascript:void(0)"
                   onclick="showModalNew({{ $code }},'deposit')">ประวัติการฝาก</a>
                <a class="dropdown-item" href="javascript:void(0)"
                   onclick="showModalNew({{ $code }},'withdraw')">ประวัติการถอน</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="editModal({{ $code }})">แก้ไขข้อมูล</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="commentModal({{ $code }})">เพิ่มหมายเหตุ</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="delModal({{ $code }})">ลบข้อมูล</a>
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
                <a class="dropdown-item" href="javascript:void(0)" onclick="refill({{ $code }})">ทำรายการฝากเงิน</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="money({{ $code }})">เพิ่ม-ลด Credit</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="point({{ $code }})">เพิ่ม-ลด Point</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="diamond({{ $code }})">เพิ่ม-ลด Diamond</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setwallet')">ประวัติการเพิ่ม-ลด
                    Credit</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setpoint')">ประวัติการเพิ่ม-ลด
                    Point</a>
                <a class="dropdown-item" href="javascript:void(0)" onclick="showModalNew({{ $code }},'setdiamond')">ประวัติการเพิ่ม-ลด
                    Diamond</a>
                <a class="dropdown-item" href="javascript:void(0)"
                   onclick="showModalNew({{ $code }},'deposit')">ประวัติการฝาก</a>
                <a class="dropdown-item" href="javascript:void(0)"
                   onclick="showModalNew({{ $code }},'withdraw')">ประวัติการถอน</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="editModal({{ $code }})">แก้ไขข้อมูล</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="commentModal({{ $code }})">เพิ่มหมายเหตุ</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="javascript:void(0)" onclick="delModal({{ $code }})">ลบข้อมูล</a>
            </div>
        </div>
    @endif
@endif
