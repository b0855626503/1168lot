<?php

namespace Gametech\Admin\Http\Controllers;

use Gametech\Admin\DataTables\CouponDataTable;
use Gametech\Core\Repositories\CouponListRepository;
use Gametech\Core\Repositories\CouponRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $couponListRepository;

    public function __construct(
        CouponRepository $repository,
        CouponListRepository $couponListRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;

        $this->couponListRepository = $couponListRepository;
    }

    public function index(CouponDataTable $couponDataTable)
    {
        return $couponDataTable->render($this->_config['view']);
    }

    public function loadData(Request $request)
    {
        $id = $request->input('id');

        $data = $this->repository->find($id);
        if (! $data) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($data, 'ดำเนินการเสร็จสิ้น');

    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = $request->input('data');

        $data['user_create'] = $user;
        $data['user_update'] = $user;

        if ($data['refill_start'] != '') {
            $data['refill_start'] = $data['refill_start'].' 00:00:00';
        }
        if ($data['refill_stop'] != '') {
            $data['refill_stop'] = $data['refill_stop'].' 23:59:59';
        }

        $this->repository->create($data);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function update(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $data = $request->input('data');

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        if ($data['refill_start'] != '') {
            $data['refill_start'] = $data['refill_start'].' 00:00:00';
        }
        if ($data['refill_stop'] != '') {
            $data['refill_stop'] = $data['refill_stop'].' 23:59:59';
        }

        //        $data['sort'] = 1;
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function edit(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');
        $status = $request->input('status');
        $method = $request->input('method');

        $data[$method] = $status;

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $chk = $this->repository->find($id);

        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $this->repository->delete($id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');
    }

    public function gen(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $id = $request->input('id');

        $chk = $this->repository->find($id);
        if (! $chk) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        $result = $this->couponListRepository->genCouponList($id);
        if (! $result) {
            return $this->sendError('ผิดพลาดในการ GEN', 200);
        }

        $data['gen'] = 'Y';
        $data['user_update'] = $user;
        $this->repository->update($data, $id);

        return $this->sendSuccess('GEN คูปองเสร็จสิ้น');

    }

    public function couponlist(Request $request)
    {
        $id = $request->input('id');

        // page / per_page จาก front-end (มีค่า default ให้)
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);

        if ($page < 1) {
            $page = 1;
        }
        if ($perPage < 1) {
            $perPage = 20;
        }

        // ====== filter params ======
        // status: all / used / unused
        $statusFilter = $request->input('status', 'all');

        // member: string ค้นหาจาก members.user_name (เท่ากับเท่านั้น)
        $memberFilter = trim((string) $request->input('member', ''));

        $header = 'ข้อมูลคูปอง';

        // ดึงข้อมูลตาม coupon_code
        $query = $this->couponListRepository
            ->model()::query()
            ->with('members')
            ->where('coupon_code', $id);

        // ====== APPLY FILTERS ======

        // 1) filter ตามสถานะ
        // 'N' = ยังไม่ใช้, 'Y' = ใช้งานแล้ว
        if ($statusFilter === 'unused') {
            $query->where('status', 'N');
        } elseif ($statusFilter === 'used') {
            $query->where('status', 'Y');
        }

        // 2) filter ตาม user_name แบบเท่ากับเท่านั้น
        if ($memberFilter !== '') {
            $query->whereHas('members', function ($q) use ($memberFilter) {
                $q->where('user_name', $memberFilter);
            });
        }

        // นับ total หลังจาก apply filter แล้ว
        $total = (clone $query)->count();

        // ดึงเฉพาะหน้าที่ต้องการ
        $pagedItems = $query
            ->orderBy('code', 'asc') // กัน paging กระโดด
            ->forPage($page, $perPage)
            ->get();

        // map เป็น array สำหรับ front-end
        $mapped = $pagedItems->map(function ($item) {
            $isUnused = ($item->status === 'N');

            return [
                'code'        => $item->name,
                'member_code' => ((int) $item->member_code === 0 ? '' : $item->members?->user_name),
                'status'      => $isUnused ? 'ยังไม่ใช้งาน' : 'ใช้งานแล้ว',
                'date'        => $isUnused
                    ? ''
                    : core()->formatDate($item->date_update, 'Y-m-d H:i:s'),
            ];
        });

        $result = [
            'name' => $header,
            'list' => $mapped->values()->all(),
            'pagination' => [
                'total'     => $total,
                'per_page'  => $perPage,
                'current'   => $page,
                'last_page' => (int) ceil($total / max($perPage, 1)),
            ],
        ];

        return $this->sendResponseNew($result, 'complete');
    }

}
