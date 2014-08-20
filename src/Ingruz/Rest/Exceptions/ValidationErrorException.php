<?php namespace Ingruz\Rest\Exceptions;

class ValidationErrorException extends \Exception {

	protected $validationErrors;

	public function __construct($message, $code = 0, Exception $previous = null) {

		$this->validationErrors = $message;

        parent::__construct(null, $code, $previous);
    }

	public function getValidationErrors()
	{
		return $this->validationErrors->toArray();
	}
}