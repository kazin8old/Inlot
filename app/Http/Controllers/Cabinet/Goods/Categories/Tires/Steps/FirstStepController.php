<?php

namespace App\Http\Controllers\Cabinet\Goods\Categories\Tires\Steps;

use Auth;
use Storage;
use App\Goods;
use App\Http\Controllers\Cabinet\Goods\Categories\StepController;
use App\Tire;
use Illuminate\Http\Request;

use App\Http\Requests;

class FirstStepController extends StepController
{
    public $status = Goods::FIRST_STEP_ADDITION;

    public function view() {}

    public function execute()
    {
        $tire = Tire::create();
        $goods = Goods::create(
            [
                'category_id' => 5,
                'item_id' => $tire->id,
                'status' => $this->getNextStep(),
                'user_id' => Auth::user()->id,
                'active' => Auth::user()->may_sell
            ]
        );
        Storage::makeDirectory('/goods/' . $goods->id);

        return redirect()->route('cabinet.goods.edit', ['step' => 2, 'goods' => $goods->id]);
    }
}
