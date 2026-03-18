<?php

namespace Gametech\Admin\Http\Controllers;



use Gametech\Admin\DataTables\BatchUserDataTable;
use Gametech\Auto\Events\BatchUser;
use Gametech\Core\Repositories\BatchUserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BatchUserController extends AppBaseController
{
    protected $_config;

    protected $repository;

    public function __construct
    (
        BatchUserRepository $repository
    )
    {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
    }


    public function index(BatchUserDataTable $batchUserDataTable)
    {
        $games = app('Gametech\Game\Repositories\GameRepository')->findWhere(['enable' => 'Y' , 'batch_game' => 'Y'])->pluck('name', 'code');

        return $batchUserDataTable->render($this->_config['view'], [ 'games' => $games ]);
    }

    public function create(Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;
        $data = json_decode($request['data'],true);

        $chk = app('Gametech\Game\Repositories\GameRepository')->find($data['game_code']);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }


        $data['game_id'] = ($chk->id == 'jokerNew' ? 'joker' : $chk->id);
        $data['ip'] = $request->ip();
        $data['user_create'] = $user;
        $data['user_update'] = $user;

        $response = $this->repository->create($data);

        $items = collect($response->toArray());

        event(new BatchUser($items));

//        Event::dispatch('admin.batch_user.after', $response);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function loadData(Request $request)
    {
        $id = $request->input('game_code');
        $freecredit = $request->input('freecredit');

        $data = $this->repository->select(['code','game_code','freecredit','prefix','batch_start','batch_stop'])->where('game_code',$id)->where('freecredit',$freecredit)->latest()->first();
        if(!$data){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $data['batch_start'] = ($data['batch_stop'] + 1);
        $data['batch_stop'] = ($data['batch_stop'] + 30000);

        if($data['batch_stop'] >= 90000){
            $data = $this->newLoop($data);
        }else{
            $data['batch_start'] = ($data['batch_stop'] + 1);
            $data['batch_stop'] = (($data['batch_stop'] + 30000) > 90000 ? 90000 : ($data['batch_stop'] + 30000));
        }

        return $this->sendResponse($data,'ดำเนินการเสร็จสิ้น');

    }


    public  function callback($matches)
    {
        if(isset($matches[1]))
        {
            $length = strlen($matches[1]);
            return sprintf("%0".$length."d", ++$matches[1]);
        }
    }


    public function newLoop($data)
    {

        $prefix = $data['prefix'];

        $prefix = (Str::length($prefix) < 5 ? Str::padRight($prefix,5,0) : preg_replace_callback( "|(\d+)|", "self::callback",$prefix));

        $data['prefix'] = $prefix;
        $data['batch_start'] = 1;
        $data['batch_stop'] = 30000;

        $chk = $this->repository->where('prefix', $prefix)->where ('game_code' , $data['game_code'])->where('freecredit' , $data['freecredit']);
        if($chk->exists()){
            $data['prefix'] = $prefix;
            return $this->newLoop($data);
        }

        return $data;
    }

    public function update($id,Request $request)
    {
        $user = $this->user()->name.' '.$this->user()->surname;

        $data = json_decode($request['data'],true);


        $chk = $this->repository->find($id);
        if(!$chk){
            return $this->sendError('ไม่พบข้อมูลดังกล่าว',200);
        }

        $data['user_update'] = $user;
        $this->repository->updatenew($data, $id);

        return $this->sendSuccess('ดำเนินการเสร็จสิ้น');

    }

    public function loadGame()
    {
        $games = [
            'value' => '',
            'text' => 'ทั้งหมด'
        ];

        $responses = collect(app('Gametech\Game\Repositories\GameRepository')->findWhere(['enable' => 'Y' , 'batch_game' => 'Y'])->toArray());

        $responses = $responses->map(function ($items){
            $item = (object)$items;
            return [
                'value' => $item->code,
                'text' => $item->name
            ];

        })->prepend($games);



        $result['games'] = $responses;
        return $this->sendResponseNew($result,'complete');
    }


}
