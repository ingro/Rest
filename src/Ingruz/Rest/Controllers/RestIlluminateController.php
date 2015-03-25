<?php namespace Adrias\AoPortali\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Foundation\Application;
use League\Fractal;
use Response;

abstract class AbstractIlluminateApiController extends Controller implements RestControllerInterface {

    /**
     * @var Fractal\Manager
     */
    protected $fractal;

    /**
     * @var
     */
    protected $repo;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var
     */
    protected $transformerClass;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->fractal = new Fractal\Manager();

        if (isset($_GET['include']))
        {
            $this->fractal->parseIncludes($_GET['include']);
        }

        $this->app = $app;

        $this->setRepository();
        $this->setTransformerClass();
    }

    /**
     * @return mixed
     */
    abstract protected function setRepository();

    /**
     *
     */
    protected function setTransformerClass()
    {
        $namespace = $this->app['config']->get('rest::namespace');
        $pieces = explode('\\', get_class($this));
        $baseClass = str_replace('Controller', '', end($pieces));
        $this->transformerClass = $namespace . '\\Transformers\\' . $baseClass . 'Transformer';
    }

    /**
     * Get the query helper options
     *
     * @return mixed
     */
    protected function getQueryHelperOptions()
    {
        return $this->app['request']->only('filter','query','top','orderby','orderdir','page');
    }

    /**
     * Return items in a paged way
     *
     * @return mixed
     */
    public function index()
    {
        $options = $this->getQueryHelperOptions();
        $items = $this->repo->allPaged($options);

        if ( ! $items)
        {
            return $this->respondNotFound('Unable to fetch the selected resources');
        }

        $response = [
            'data' => [],
            'meta' => [
                'pagination' => []
            ]
        ];

        $resource = new Fractal\Resource\Collection($items->getCollection(), new $this->transformerClass);
        $resource->setPaginator(new Fractal\Pagination\IlluminatePaginatorAdapter($items));

        $response['data'] = $this->fractal->createData($resource)->toArray()['data'];

        $paginator = new Fractal\Pagination\IlluminatePaginatorAdapter($items);

        $response['meta']['pagination'] = [
            'total' => $paginator->getTotal(),
            'count' => $paginator->count(),
            'per_page' => $paginator->getPerPage(),
            'current_page' => $paginator->getCurrentPage(),
            'total_pages' => $paginator->getLastPage()
        ];

        return $response;
    }

    /**
     * Return an item by it's id
     *
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $item = $this->repo->getById($id);

        if ( ! $item)
        {
            return $this->respondNotFound();
        }

        $resource = new Fractal\Resource\Item($item, new $this->transformerClass);
        return $this->fractal->createData($resource)->toArray();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        try
        {
            $item = $this->repo->create(Input::all());
        } catch (ValidationErrorException $exception)
        {
            return $this->respondNotValid($exception);
        }

        $resource = new Fractal\Resource\Item($item, new $this->transformerClass);
        return $this->fractal->createData($resource)->toArray();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        try
        {
            $update = $this->repo->update($id, Input::all());
        } catch (ValidationErrorException $exception)
        {
            return $this->respondNotValid($exception);
        }

        if ( ! $update)
        {
            return $this->respondNotFound();
        }

        $item = $this->repo->getById($id);

        $resource = new Fractal\Resource\Item($item, new $this->transformerClass);
        return $this->fractal->createData($resource)->toArray();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $delete = $this->repo->delete($id);

        if ( ! $delete)
        {
            return $this->respondNotProcessable('Unable to delete the selected item');
        }

        $message = 'Item deleted successfully';

        return compact('message');
    }

    /**
     * @param $message
     * @param $code
     *
     * @return mixed
     */
    protected function respondWithError($message, $code)
    {
        return Response::json($message, $code);
    }

    /**
     * Respond with an error in case the resource request is not found
     *
     * @param  string $message
     * @return Response
     */
    protected function respondNotFound($message = '')
    {
        if (empty($message))
        {
            $message = 'The selected resource does not exists';
        }

        return $this->respondWithError(compact('message'), 404);
    }

    /**
     * Return an error in case of a validation exception throwned
     *
     * @param  Exception $exception
     * @return Response
     */
    protected function respondNotValid($exception)
    {
        return $this->respondWithError(
            array(
                'message' => $exception->getMessage(),
                'errors' => $exception->getValidationErrors()
            ),
            422
        );
    }

    /**
     * Return an error in case of a generic unprocessable request
     *
     * @param  string $message
     * @return Response
     */
    protected function respondNotProcessable($message = 'Unable to process your request')
    {
        return $this->respondWithError(compact('message'), 422);
    }

}
