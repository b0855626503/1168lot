<?php

namespace Gametech\CenterOA\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Http\Request;

class ImgController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $setting;

    public function __construct(
        MemberRepository $repository
    ) {
        $this->_config = request('_config');

        $this->setting = core()->getConfigData();

        $this->middleware('admin');

        $this->repository = $repository;

    }

    public function upload(Request $request)
    {
        if (! $request->hasFile('file')) {
            return response()->json(['success' => false, 'message' => 'ข้อมูลไม่ครบ'], 200);
        }

        $id = $request->input('id');
        $user = $this->repository->find($id);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสมาชิกไอดีนี้'], 200);
        }
        $filename = $user->user_name.'.'.$request->file('file')->getClientOriginalExtension();
        $path = $request->file('file')->storeAs('qr', $filename, 'public');
        if ($path) {
            $user->pic_id = $path;
            $user->save();
        } else {
            return response()->json(['success' => false, 'message' => 'อัพรูปไม่สำเร็จ'], 200);
        }

        return response()->json(['success' => true, 'message' => 'สำเร็จ', 'id' => $id, 'url' => asset('storage/'.$path), 'delete_url' => route('admin.delete.pic', ['id' => $id])], 200);
    }

    public function delete($id, Request $request)
    {

        $user = $this->repository->find($id);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'ไม่มีสมาชิกไอดีนี้'], 200);
        }

        $user->pic_id = '';
        $user->save();

        return response()->json(['success' => true, 'message' => 'ลบภาพ เรียบร้อยแล้ว'], 200);
    }
}
