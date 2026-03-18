<?php

namespace Gametech\Member\Http\Controllers;

use App\DataTables\MemberDataTable;
use App\Http\Controllers\AppBaseController;
use Gametech\Member\Http\Requests\CreateMemberRequest;
use Gametech\Member\Http\Requests\UpdateMemberRequest;
use Gametech\Member\Repositories\MemberRepository;
use Laracasts\Flash\Flash;


class MemberController extends AppBaseController
{

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    private $memberRepository;

    /**
     * Create a new controller instance.
     *
     * @param MemberRepository $memberRepo
     */
    public function __construct
    (
        MemberRepository $memberRepo
    )
    {
        $this->_config = request('_config');

        $this->memberRepository = $memberRepo;
    }

    /**
     * Display a listing of the Member.
     *
     * @param MemberDataTable $memberDataTable
     * @return Response
     */
    public function index(MemberDataTable $memberDataTable)
    {
        return $memberDataTable->render($this->_config['view']);
    }

    /**
     * Show the form for creating a new Member.
     *
     * @return Response
     */
    public function create()
    {
        return view($this->_config['view']);
    }

    /**
     * Store a newly created Member in storage.
     *
     * @param CreateMemberRequest $request
     *
     * @return Response
     */
    public function store(CreateMemberRequest $request)
    {
        $input = $request->all();

        $member = $this->memberRepository->create($input);

        if($member->code){
            Flash::success('ระบบได้สร้าง ระเบียนใหม่ เรียบร้อยแล้ว');
        }else{
            Flash::error('พบข้อผิดพลาด ไม่สามารถสร้าง ระเบียนใหม่ได้');
        }

        return redirect()->route($this->_config['redirect']);
    }


    /**
     * Show the form for editing the specified Member.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $member = $this->memberRepository->find($id);

        if (empty($member)) {
            Flash::error('พบข้อผิดพลาด ไม่พบข้อมูลที่ต้องการ');

            return redirect()->route($this->_config['redirect']);
        }

        return view($this->_config['view'])->with('member', $member);
    }

    /**
     * Update the specified Member in storage.
     *
     * @param  int              $id
     * @param UpdateMemberRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateMemberRequest $request)
    {
        $member = $this->memberRepository->find($id);

        if (empty($member)) {
            Flash::error('พบข้อผิดพลาด ไม่พบข้อมูลที่ต้องการ');

            return redirect()->route($this->_config['redirect']);
        }

        $member = $this->memberRepository->update($request->all(), $id);
        if($member->wasChanged()){
            Flash::success('ระบบได้ทำการ บันทึกข้อมูลดังกล่าวแล้ว');
        }else{
            Flash::error('พบข้อผิดพลาด ไม่สามารถบันทึกข้อมูลได้');
        }

        return redirect()->route($this->_config['redirect']);
    }

    /**
     * Remove the specified Member from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $member = $this->memberRepository->find($id);

        if (empty($member)) {
            Flash::error('พบข้อผิดพลาด ไม่พบข้อมูลที่ต้องการ');

            return redirect()->route($this->_config['redirect']);
        }

        $this->memberRepository->delete($id);

        Flash::success('ระบบได้ทำการ ลบข้อมูลดังกล่าวแล้ว');

        return redirect()->route($this->_config['redirect']);
    }
}
