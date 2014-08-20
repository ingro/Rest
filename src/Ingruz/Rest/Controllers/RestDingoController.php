<?php namespace Ingruz\Rest\Controllers;

use Dingo\Api\Routing\Controller;
use Dingo\Api\Dispatcher;
use Dingo\Api\Auth\Shield;
use Illuminate\Support\Facades\App;
use Response;
use Input;
use League\Fractal;
use Illuminate\Foundation\Application;
use Ingruz\Rest\Exceptions\ValidationErrorException as ValidationErrorException;

class RestDingoController extends Controller implements RestControllerInterface {

    protected $repo;
    protected $fractal;
    protected $baseClass;
    protected $transformerClass;

    public function __construct(Fractal\Manager $fractal, Application $app, Dispatcher $api, Shield $auth)
    {
        $this->api  = $api;
        $this->auth = $auth;

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

        return $list;
//        return Response::api()->withCollection($list, new $this->transformerClass);
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

        return Response::api()->withItem($item, new $this->transformerClass);
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

        return Response::api()->withItem($item, new $this->transformerClass);
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

        return Response::api()->withItem($item, new $this->transformerClass);
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

        return $this->baseClass.' deleted successfully';
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
        return Response::api()->withError($message, $statusCode);
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