<?php namespace Ingruz\Rest\Helpers;

use Ingruz\Rest\Exceptions\OperandNotFoundException;
use Ingruz\Rest\Models\RestModel;
use League\Fractal;

class DBQueryHelper {

    /**
     * @var RestModel
     */
    protected $item;

    /**
     * @var mixed
     */
    protected $query;
    /*protected $page = 1;*/

    /**
     * @var integer
     */
    protected $perPage;

    /**
     * @var array
     */
    protected $defaults = [
        'filter' => null,
        'query' => null,
        'orderby' => null,
        'orderdir' => 'asc',
        'top' => 20,
        'paginate' => true
    ];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var \League\Fractal\TransformerAbstract
     */
    protected $transformerClass;

    /**
     * @var array
     */
    protected $operands = array(
        '=' => '=',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'like' => 'LIKE',
        'in' => 'in'
    );

    /**
     * @param RestModel $istance
     * @param array $options
     */
    public function __construct( RestModel $istance, array $options = [] )
    {
        $this->item = $istance;
        $this->fractal = new Fractal\Manager();

        $this->options = $this->mergeDefaults($options, $this->defaults);

        if($this->options['paginate'])
        {
            $this->setPerPage();
        }
    }

    /**
     * @param array $data
     * @param array $defaults
     * @return array
     */
    protected function mergeDefaults($data, $defaults)
    {
        foreach ($defaults as $field => $value)
        {
            if ( ! isset($data[$field]))
            {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    /**
     * Set the Transformer Class for the current item
     */
    /*protected function setTransformerClass()
    {
        $pieces = explode('\\', get_class($this->item));

        $this->transformerClass = reset($pieces).'\\Transformers\\'.end($pieces).'Transformer';
    }*/

    /**
     * Set the number of items to be returned by the paginator
     */
    protected function setPerPage()
    {
        $this->perPage = (int) $this->options['top'];
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

    /**
     * Return the data filter by the options
     *
     * @return array
     */
    public function getData()
    {
        $this->buildQuery();

        $eagerTables = $this->item->getEagerTables();

        if( ! empty($eagerTables) )
        {
            $this->query->with($eagerTables);
        }

        $models = ($this->options['paginate']) ? $this->query->paginate($this->perPage) : $this->query->get();

        return $models;
    }

    /**
     * @return mixed
     */
    protected function buildQuery()
    {
        $staticItem = get_class($this->item);
        $this->query = $staticItem::listConditions();

        $scopes = $this->item->getApplicableScopes();

        if ( ! empty($scopes))
        {
            foreach ($scopes as $scope)
            {
                $this->query->{$scope}();
            }
        }

        if ( ! empty($this->options['filter']))
        {
            $term = $this->options['filter'];

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

        if ( ! empty($this->options['query']))
        {
            $fields = $this->getQueryFields($this->options['query']);

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

    /**
     * @param string $query
     * @return array
     */
    protected function getQueryFields($query)
    {
        return explode('::', $query);
    }

    /**
     * @param string $chunk
     */
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

    /**
     * @param $query
     */
    protected function setItemsOrder($query)
    {
        if ( ! empty($this->options['orderby']))
        {
            $orderField = $this->options['orderby'];
            $orderDir = $this->options['orderdir'];

            $query->orderBy($orderField, $orderDir);
        } else
        {
            $query->listOrder();
        }
    }

    /**
     * @param array $chunk
     */
    protected function addEqualCondition($chunk)
    {
        if( $chunk[1] !== "" )
        {
            $this->addConditionToQuery($chunk[0], '=', $chunk[1]);
        }
    }

    /**
     * @param array $chunk
     */
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
            $this->addConditionToQuery($chunk[0], $operand, $chunk[2]);
        }
    }

    /**
     * @param string $field
     * @param string $operand
     * @param mixed $value
     */
    protected function addConditionToQuery($field, $operand, $value)
    {
        $fieldValue = ($operand === 'LIKE') ? '%'.$value.'%' : $value;

        if (strpos($field, '.') === FALSE)
        {
            if ($operand === 'in') {
                $this->query->whereIn($field, explode(',', $value));
            } else
            {
                $this->query->where($field, $operand, $fieldValue);
            }
        } else
        {
            $bits = explode('.', $field);
            $this->query->whereHas($bits[0], function($q) use ($bits, $operand, $fieldValue)
            {
                if ($operand !== 'in') {
                    $q->where($bits[1], $operand, $fieldValue);
                } else {
                    $q->whereIn($bits[1], explode(',', $fieldValue));
                }
            });
        }
    }

    /**
     * @param string $code
     * @return string
     * @throws OperandNotFoundException
     */
    protected function getOperand($code)
    {
        if ( ! array_key_exists(strtolower($code), $this->operands))
        {
            throw new OperandNotFoundException("Invalid operand found in request's querystring: '{$code}'");
        }

        return $this->operands[$code];
    }
}
