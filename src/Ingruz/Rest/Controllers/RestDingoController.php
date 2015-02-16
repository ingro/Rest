<?php namespace Ingruz\Rest\Controllers;

use Dingo\Api\Routing\ControllerTrait;
use Illuminate\Routing\Controller;
use Response;
use Input;
use League\Fractal;
use Illuminate\Foundation\Application;
use Ingruz\Rest\Exceptions\ValidationErrorException as ValidationErrorException;

class RestDingoController extends Controller implements RestControllerInterface {

    use ControllerTrait;

    protected $repo;
    protected $fractal;
    protected $baseClass;
    protected $transformerClass;

    public function __construct(Fractal\Manager $fractal, Application $app)
    {
        $this->fractal = $fractal;

        $namespace = $app['config']->get('rest::namespace');

        $pieces = explode('\\', get_class($this));

        $this->baseClass = str_replace('Controller', '', end($pieces));

        $repositoryClass = $namespace.'\\Repositories\\'.$this->baseClass.'Repository';

        $modelClass = $namespace.'\\Models\\'.$this->baseClass;

        $this->transformerClass = $namespace.'\\Transformers\\'.$this->baseClass.'Transformer';

        $this->repo = new $repositoryClass(new $modelClass);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $list = $this->repo->allPaged();

        if ( ! $list)
        {
            return $this->respondNotFound('Unable to fetch the selected resource');
        }

        $resource = new Fractal\Resource\Collection($list->getCollection(), new $this->transformerClass);
        $resource->setPaginator(new Fractal\Pagination\IlluminatePaginatorAdapter($list));

        return $this->fractal->createData($resource)->toArray();
//        return $this->response->collection($list, new $this->transformerClass);
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

        return $this->response->item($item, new $this->transformerClass);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $item = $this->repo->getById($id);

        if ( ! $item)
        {
            return $this->respondNotFound();
        }

        return $this->response->item($item, new $this->transformerClass);
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

        return $this->response->item($item, new $this->transformerClass);
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
            return $this->respondNotProcessable('Unable to delete the selected '.ucfirst($this->baseClass));
        }

        $message = $this->baseClass.' deleted successfully';

        return compact('message');
    }

    /**
     * Respond with an error
     *
     * @param $message
     * @param $statusCode
     * @return mixed
     */
    protected function respondWithError($message, $statusCode)
    {
        return $this->response->error($message, $statusCode);
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
            $message = ucfirst($this->baseClass).' does not exist';
        }

        return $this->respondWithError($message, 404);
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
        return $this->respondWithError(array('message' => $message), 422);
    }
}
