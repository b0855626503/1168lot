<?php

namespace Gametech\Game\Http\Controllers;

use App\DataTables\GameDataTable;
use App\Http\Controllers\AppBaseController;
use Gametech\Game\Http\Requests\CreateGameRequest;
use Gametech\Game\Http\Requests\UpdateGameRequest;
use Gametech\Game\Repositories\GameRepository;
use Illuminate\Support\Facades\Response;
use Laracasts\Flash\Flash;

class GameController extends AppBaseController
{

    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    private $gameRepository;

    /**
     * Create a new controller instance.
     *
     * @param GameRepository $gameRepo
     */
    public function __construct
    (
        GameRepository $gameRepo
    )
    {
        $this->_config = request('_config');

        $this->gameRepository = $gameRepo;
    }

    /**
     * Display a listing of the Game.
     *
     * @param GameDataTable $gameDataTable
     * @return Response
     */
    public function index(GameDataTable $gameDataTable)
    {
        return $gameDataTable->render($this->_config['view']);
    }

    /**
     * Show the form for creating a new Game.
     *
     * @return Response
     */
    public function create()
    {
        return view($this->_config['view']);
    }

    /**
     * Store a newly created Game in storage.
     *
     * @param CreateGameRequest $request
     *
     * @return Response
     */
    public function store(CreateGameRequest $request)
    {
        $input = $request->all();

        $game = $this->gameRepository->create($input);

        if($game->code){
            Flash::success('ระบบได้สร้าง ระเบียนใหม่ เรียบร้อยแล้ว');
        }else{
            Flash::error('พบข้อผิดพลาด ไม่สามารถสร้าง ระเบียนใหม่ได้');
        }

        return redirect()->route($this->_config['redirect']);
    }


    /**
     * Show the form for editing the specified Game.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $game = $this->gameRepository->find($id);

        if (empty($game)) {
            Flash::error('พบข้อผิดพลาด ไม่พบข้อมูลที่ต้องการ');

            return redirect()->route($this->_config['redirect']);
        }

        return view($this->_config['view'])->with('game', $game);
    }

    /**
     * Update the specified Game in storage.
     *
     * @param  int              $id
     * @param UpdateGameRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateGameRequest $request)
    {
        $game = $this->gameRepository->find($id);

        if (empty($game)) {
            Flash::error('พบข้อผิดพลาด ไม่พบข้อมูลที่ต้องการ');

            return redirect()->route($this->_config['redirect']);
        }

        $game = $this->gameRepository->updatenew($request->all(), $id);

        if($game->wasChanged()){
            Flash::success('ระบบได้ทำการ บันทึกข้อมูลดังกล่าวแล้ว');
        }else{
            Flash::error('พบข้อผิดพลาด ไม่สามารถบันทึกข้อมูลได้');
        }

        return redirect()->route($this->_config['redirect']);
    }

    /**
     * Remove the specified Game from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $game = $this->gameRepository->find($id);

        if (empty($game)) {
            Flash::error('พบข้อผิดพลาด ไม่พบข้อมูลที่ต้องการ');

            return redirect()->route($this->_config['redirect']);
        }

        $this->gameRepository->delete($id);

        Flash::success('ระบบได้ทำการ ลบข้อมูลดังกล่าวแล้ว');

        return redirect()->route($this->_config['redirect']);
    }
}
