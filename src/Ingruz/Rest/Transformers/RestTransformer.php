<?php namespace Ingruz\Rest\Transformers;

use League\Fractal;

class RestTransformer extends Fractal\TransformerAbstract {

    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager();
        $this->helper = new Helpers();
    }

    protected function transformNested($models, $nestedTransformerClass)
    {
        $resource = new Fractal\Resource\Item($models, new $nestedTransformerClass);

        return $this->fractal->createData($resource)->toArray()['data'];
    }

}