<?php namespace Ingruz\Rest\Transformers;

use League\Fractal;

class RestTransformer extends Fractal\TransformerAbstract {

    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager();
        $this->helper = new Helpers();
    }

    protected function transformNestedItem($model, $nestedTransformerClass)
    {
        $resource = new Fractal\Resource\Item($model, new $nestedTransformerClass);

        return $this->fractal->createData($resource)->toArray()['data'];
    }

    protected function transformNestedCollection($models, $nestedTransformerClass)
    {
        $resource = new Fractal\Resource\Collection($models, new $nestedTransformerClass);

        return $this->fractal->createData($resource)->toArray()['data'];
    }

}