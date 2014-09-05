<?php namespace Ingruz\Rest\Helpers;

use Illuminate\Support\Facades\Request;
use Ingruz\Rest\Exceptions\OperandNotFoundException;
use League\Fractal;
use Config;

class DBQueryHelper {

    protected $item;
    protected $query;
    /*protected $page = 1;*/
    protected $perPage = 20;

    protected $operands = array(
        '=' => '=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'like' => 'LIKE'
    );

    public function __construct( \Ingruz\Rest\Models\RestModel $istance )
    {
        $this->item = $istance;
        $this->fractal = new Fractal\Manager();

        $pieces = explode('\\', get_class($this->item));

        $this->transformerClass = reset($pieces).'\\Transformers\\'.end($pieces).'Transformer';

        /*if (Request::get('currentPage'))
        {
            $this->page = (int) Request::get('currentPage');
        }*/

        $top = Request::get('top');

        if ( ! empty($top) )
        {
            $this->perPage = Request::get('top');
        }
    }

    /*public function getData()
    {
        $this->buildQuery();

        $total = $this->getItemsTotal();

        if ( $total === 0 )
        {
            return $data = array(
                'total' => $total,
                'values' => array(),
                'page' => $this->page
            );
        }

        $idsList = $this->getItemsId();

        return $this->getItemsModels($idsList, $total);
    }*/

    public function getData()
    {
        $this->buildQuery();

        $eagerTables = $this->item->getEagerTables();

        if( ! empty($eagerTables) )
        {
            $this->query->with($eagerTables);
        }

        $models = $this->query->paginate($this->perPage);

        $resource = new Fractal\Resource\Collection($models->getCollection(), new $this->transformerClass);
        $resource->setPaginator(new Fractal\Pagination\IlluminatePaginatorAdapter($models));

        $data = $this->fractal->createData($resource)->toArray();

        return $data;
    }

    protected function buildQuery()
    {
        $staticItem = get_class($this->item);
        $this->query = $staticItem::listConditions();

        if(Request::get('filter'))
        {
            $term = Request::get('filter');

            $fields = $this->item->getFullSearchFields();

            $this->query->where(function($q) use ($fields, $term)
            {
                foreach ($fields as $field)
                {
                    if (strpos($field, '.') === FALSE)
                    {
                        $q->orWhere($field, 'LIKE', '%'.$term.'%');
                    } else
                    {
                        $bits = explode('.', $field);
                        $q->orWhereHas($bits[0], function($q) use ($bits, $term)
                        {
                            $q->where($bits[1], 'LIKE', '%'.$term.'%');
                        });
                    }
                }
            });
        }

        if(Request::get('query'))
        {
            $fields = $this->getQueryFields();

            foreach( $fields as $field )
            {
                $this->addQueryFilter($field);
            }
        }

        $this->setItemsOrder($this->query);

        return $this->query;
    }

    /*protected function getItemsTotal()
    {
        return (int) $this->query->count($this->item->getKeyName());
    }

    private function getItemsId()
    {
        return $this->query->forPage($this->page, $this->perPage)->lists($this->item->getKeyName());
    }

    private function getItemsModels($ids, $total)
    {
        $data = array(
            'total' => $total,
            'values' => array(),
            'page' => $this->page
            // 'query' => array()
        );

        $staticItem = get_class($this->item);
//        $query = $staticItem::whereIn($this->item->getKeyName(), $ids);
//        $this->setItemsOrder($query);
        $query = $this->buildQuery();

        $eagerTables = $this->item->getEagerTables();

        if( ! empty($eagerTables) )
        {
            $query->with($eagerTables);
        }

        $models = $query->paginate(10);

        $resource = new Fractal\Resource\Collection($models->getCollection(), new $this->transformerClass);
        $resource->setPaginator(new Fractal\Pagination\IlluminatePaginatorAdapter($models));

        $data = $this->fractal->createData($resource)->toArray();

        return $data;
    }*/

    protected function getQueryFields()
    {
        return explode('::', Request::get('query'));
    }

    protected function addQueryFilter($chunk)
    {
        if ( $chunk !== "" )
        {
            $sub = explode('||', $chunk);

            if ( count($sub) === 2 )
            {
                $this->addEqualCondition($sub);
            } else if ( count($sub) === 3 )
            {
                $this->addOtherCondition($sub);
            }
        }
    }

    protected function setItemsOrder($query)
    {
        if(Request::get('orderby'))
        {
            $orderField = Request::get('orderby');
            $orderDir = Request::get('orderdir') ? Request::get('orderdir') : 'asc';

            $query->orderBy($orderField, $orderDir);
        } else
        {
            $query->listOrder();
        }
    }

    protected function addEqualCondition($chunk)
    {
        if( $chunk[1] !== "" ) $this->query->where($chunk[0], $chunk[1]);
    }

    protected function addOtherCondition($chunk)
    {
        try
        {
            $operand = $this->getOperand($chunk[1]);
        } catch (OperandNotFoundException $e)
        {
            return false;
        }

        if( $chunk[2] !== "" )
        {
            $value = ($operand === 'LIKE') ? '%'.$chunk[2].'%' : $chunk[2];

            $this->query->where($chunk[0], $operand, $value);
        }
    }

    protected function getOperand($code)
    {
        if ( ! array_key_exists(strtolower($code), $this->operands))
        {
            throw new OperandNotFoundException("Invalid operand found in request's querystring: '{$code}'");
        }

        return $this->operands[$code];
    }
}