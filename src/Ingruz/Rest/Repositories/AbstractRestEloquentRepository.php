<?php namespace Ingruz\Rest\Repositories;

use Ingruz\Rest\Helpers\DBQueryHelper as RestQueryHelper;
use Illuminate\Support\Facades\Input;

abstract class AbstractRestEloquentRepository implements RestRepositoryInterface {

    /**
     * @var \Ingruz\Rest\Models\RestModel;
     */
    protected $model;

    /**
     * Make a new instance of the entity to query on
     *
     * @param array $with
     * @return mixed
     */
    public function make(array $with = array())
    {
        return $this->model->with($with);
    }

    /**
     * Return all items
     *
     * @param array $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $with = array())
    {
        $query = $this->make($with);

        return $query->get();
    }

    /**
     * Return items paged
     *
     * @param array $with
     * @return array|mixed
     */
    public function allPaged(array $with = array())
    {
        $helper = $this->getQueryHelper();

        return $helper->getData();
    }

    /**
     * Get the query helper istance
     *
     * @return RestQueryHelper
     */
    protected function getQueryHelper()
    {
        return new RestQueryHelper($this->model, $this->getQueryHelperOptions());
    }

    /**
     * Get the query helper options
     *
     * @return mixed
     */
    protected function getQueryHelperOptions()
    {
        return Input::only('filter','query','top','orderby','orderdir');
    }

    /**
     * Return an item by its primary key
     *
     * @param  mixed $id
     * @param  array $with
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getById($id, array $with = array())
    {
        $query = $this->make($with);

        return $query->find($id);
    }

    /**
     * Create a new item
     *
     * @param  array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update an item
     *
     * @param  mixed $id
     * @param  array $data
     * @return boolean
     */
    public function update($id, array $data)
    {
        $istance = $this->getById($id);

        if (! $istance)
        {
            return false;
        }

        return $istance->update($data);
    }

    /**
     * Delete an item
     *
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id)
    {
        $istance = $this->getById($id);

        if (! $istance)
        {
            return false;
        }

        return $istance->delete();
    }

    /**
     * Find an item by a key value
     *
     * @param  string $key   [description]
     * @param  mixed $value [description]
     * @param  array $with  [description]
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getFirstBy($key, $value, array $with = array())
    {
        return $this->make($with)->where($key, '=', $value)->first();
    }

    /**
     * Return many items by a key value
     *
     * @param  string $key
     * @param  mixed $value
     * @param  array $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getManyBy($key, $value, array $with = array())
    {
        return $this->make($with)->where($key, '=', $value)->get();
    }

    /**
     * Return items that have a defined relation
     *
     * @param  string $relation
     * @param  array $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function has($relation, array $with = array())
    {
        $query = $this->make($with);

        return $query->has($relation)->get();
    }
}
