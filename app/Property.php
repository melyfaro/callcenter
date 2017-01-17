<?php namespace App;

use App\ACME\Model\PropertyTypes\DateProperty;
use App\ACME\Model\PropertyTypes\SelectProperty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;

class Property extends Model
{

    public $fillable = [
        "model_goal",
        "title",
        "type",
        "sort",
        "multiple",
        "values",
        "model_goal",
        "model_initiator",
        "link_id",
        "code",
        "active"
    ];


    /**
     * @param $model Model
     * @return array
     */
    public static function getPropertyByModel($model)
    {
        $function = new \ReflectionClass($model);
        $className = $function->getShortName();

        $list = self::where('model_goal', '=', $className)->orderBy('sort')->get();
        $arResult = array();

        foreach ($list as $item) {
            if (!empty($item->model_initiator)) {
                if (isset($model->attributes[strtolower($item->model_initiator) . "_id"]) && $model->attributes[strtolower($item->model_initiator) . "_id"] == $item->link_id) {
                    $arResult[] = $item;
                }
            }
        }
        return $arResult;
    }

    /**
     *
     */
    public static function showPropertyValue($model)
    {
        $properties = self::getPropertyByModel($model);
        $propertyValues = array();

        foreach ($properties as $property) {
            if ($property->type == "date") {
                $res = DateProperty::where('property_id', '=', $property->id)->where('element_id', "=",
                    $model->id)->first();
            }else if ($property->type == "select") {
                $res = SelectProperty::where('property_id', '=', $property->id)->where('element_id', "=",
                    $model->id)->first();
            }
            else {
                $res = \App\PropertyValue::where('property_id', '=', $property->id)->where('element_id', "=",
                    $model->id)->first();
            }

            if (!empty($res)) {
                $propertyValues[$property->id] = ['title' => $property->title, 'value' => $res->value,'code'=>$property->code,'type'=>$property->type];
            }
        }
        return $propertyValues;
    }


    public function getValuesAttribute() {
        if(empty($this->attributes['values'])) return [];
        return json_decode($this->attributes['values']) ;
    }



    public function setValuesAttribute($value) {

        $this->attributes['values'] =  json_encode($value);

    }
}
