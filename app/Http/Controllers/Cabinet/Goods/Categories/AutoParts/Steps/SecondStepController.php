<?php

namespace App\Http\Controllers\Cabinet\Goods\Categories\AutoParts\Steps;

use App\AutoPartModel;
use App\GoodsGallery;
use App\GoodsModels;
use App\Plugins\FileManager;
use Validator;
use Storage;
use App\AutoPart;
use App\CarModel;
use App\City;
use App\Goods;
use App\Handbook;
use App\Http\Controllers\Cabinet\Goods\Categories\StepController;
use App\Mark;
use App\Region;
use Illuminate\Http\Request;

use App\Http\Requests;

class SecondStepController extends StepController
{
    public $status = Goods::SECOND_STEP_ADDITION;

    public function view()
    {
        $data = $this->getData();
        $goods = $this->goods;
        $autoPart = AutoPart::find($goods->item_id);

        return view('cabinet.goods.categories.auto_parts.step_2', compact('data', 'goods', 'autoPart'));
    }

    private function getData()
    {
        if (count($this->goods->goodsModels)) {
            foreach ($this->goods->goodsModels as $goodsModel) {
                $modelIds[] = $goodsModel->model_id;
                $markIds[] = $goodsModel->mark_id;
            }
        }

        return [
            'regions' => Region::lists('name', 'id'),
            'cities' => City::where(['region_id' => $this->goodsController->getCurrentRegionId($this->goods)])
                    ->lists('name', 'id'),
            'marks' => Mark::lists('name', 'id'),
            'activeMarks' => isset($markIds) ? array_unique($markIds) : [],
            'models' => CarModel::whereIn('mark_id', isset($markIds) ? array_unique($markIds) : [])->lists('name', 'id'),
            'activeModels' => isset($modelIds) ? $modelIds : [],
            'kinds' => Handbook::find(12)->records()->lists('name', 'id'),
            'states' => Handbook::find(13)->records()->lists('name', 'id'),
            'region' => $this->goodsController->getCurrentRegionId($this->goods),
            'city' => $this->goodsController->getCurrentCityId($this->goods),
            'address' => $this->goodsController->getCurrentAddress($this->goods),
        ];
    }

    public function execute()
    {
        $goods = $this->goods;
        $request = $this->request;
        $autoPart = AutoPart::find($goods->item_id);

        $validator = Validator::make($request->all(), $this->getValidationRules(), $this->getValidationMessages());

        if ($validator->fails()) {
            return response($validator->errors(), 422);
        }

        $autoPart->fill($request->only('auto_part_kind_id', 'image', 'state_id', 'original_number'));

        $this->addGoodsModels($request, $goods);

        $goods->fill($request->only('comment', 'region_id', 'city_id', 'address', 'name'));
        $goods->image = $request->hasFile('image') ?
            FileManager::loadFile($request->file('image'), $goods->imagesDir) :
            $goods->image;

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $fileName = FileManager::loadFile($file, $goods->imagesDir);
                $fileHash = md5(Storage::get($goods->imagesDir . $fileName));
                $galleryItem = $goods->gallery()->where('hash', $fileHash)->first() ?: new GoodsGallery();
                $galleryItem->goods_id = $goods->id;
                $galleryItem->filename = $fileName;
                $galleryItem->hash = $fileHash;
                $galleryItem->save();
            }
        }

        if ($autoPart->save() and $goods->save()) {
            $this->goodsController->changeStepStatus($goods, $this->getNextStep());

            return response(['url' => route('cabinet.goods.edit', ['step' => 3, 'goods' => $goods->id])]);
        }

        return response(['message' => 'Произошла необратимая ошибка'], 500);
    }

    private function addGoodsModels(Request $request, Goods $goods)
    {
        if (count($request->input('models'))) {
            foreach ($request->input('models') as $modelId) {
                $modelIds[] = $modelId;
                $markIds[] = CarModel::find($modelId)['mark_id'];

                if (!GoodsModels::where(['goods_id' => $goods->id, 'model_id' => $modelId])->count()) {
                    GoodsModels::create([
                        'goods_id' => $goods->id,
                        'model_id' => $modelId,
                        'mark_id' => CarModel::find($modelId)['mark_id']
                    ]);
                }
            }
        }

        $aloneMarks = isset($markIds) ?
            array_diff($request->input('marks'), array_unique($markIds)) :
            $request->input('marks');

        $aloneMarkIds = [];

        if (count($aloneMarks)) {
            foreach ($aloneMarks as $markId) {
                $aloneMarkIds[] = $markId;

                if (!GoodsModels::where(['goods_id' => $goods->id, 'mark_id' => $markId, 'model_id' => null])->count()) {
                    GoodsModels::create(['goods_id' => $goods->id, 'mark_id' => $markId]);
                }
            }
        }

        GoodsModels::where('goods_id', $goods->id)
            ->whereNotIn('model_id', isset($modelIds) ? $modelIds : [])
            ->delete();

        GoodsModels::where('goods_id', $goods->id)
            ->where('model_id', null)
            ->whereNotIn('mark_id', isset($aloneMarkIds) ? $aloneMarkIds : [])
            ->delete();
    }

    /**
     * Get the validation rules for second step.
     *
     * @return array
     */
    private function getValidationRules()
    {
        return [
            'region_id' => 'required',
            'city_id' => 'required',
            'auto_part_kind_id' => 'required',
            'name' => 'required',
            'state_id' => 'required',
            'image' => 'image|between:0,5120'
        ];
    }

    /**
     * Get the validation messages for errors.
     *
     * @return array
     */
    private function getValidationMessages()
    {
        return [
            'region_id.required' => 'Это обязательное поле.',
            'city_id.required' => 'Это обязательное поле.',
            'state_id.required' => 'Это обязательное поле.',
            'auto_part_kind_id.required' => 'Это обязательное поле.',
            'name.required' => 'Это обязательное поле.',
            'image.image' => 'Файл должен быть в формате jpeg, png, bmp, gif или svg.',
            'image.between' => 'Файл не должен превышать 5Мб'
        ];
    }
}
