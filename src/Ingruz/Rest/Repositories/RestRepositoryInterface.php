<?php namespace Ingruz\Rest\Repositories;

interface RestRepositoryInterface {

    /**
     * Make a new instance of the entity to query on
     *
     * @param array $with
     */
    public function make(array $with = array());

    /**
     * Return all items
     *
     * @param array $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $with = array());

    /**
     * Return all items paginated
     *
     * @param array $with
     * @return mixed
     */
    public function allPaged(array $with = array());

    /**
     * Return an item by its primary key
     *
     * @param  mixed $id
     * @param  array $with
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getById($id, array $with = array());

    /**
     * Create a new item
     *
     * @param  array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data);

    /**
     * Update an item
     *
     * @param  mixed $id
     * @param  array $data
     * @return boolean
     */
    public function update($id, array $data);

    /**
     * Delete an item
     *
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id);

    /**
     * Find an item by a key value
     *
     * @param  string $key   [description]
     * @param  mixed $value [description]
     * @param  array $with  [description]
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getFirstBy($key, $value, array $with = array());

    /**
     * Return many items by a key value
     *
     * @param  string $key
     * @param  mixed $value
     * @param  array $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getManyBy($key, $value, array $with = array());

    /**
     * Return items that have a defined relation
     *
     * @param  string $relation
     * @param  array $with
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function has($relation, array $with = array());
} 