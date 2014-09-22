<?php namespace Ingruz\Rest\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Validator;

use Ingruz\Rest\Exceptions\ValidationErrorException;

abstract class RestModel extends Model {

    /**
     * Validation rules
     *
     * @var array
     */
    protected static $rules = array(
        'save' => array(),
        'create' => array(),
        'update' => array()
    );

    /**
     * Validation errors container
     *
     * @var \Illuminate\Support\MessageBag;
     */
    protected $validationErrors;
    // protected $mergedRules = array();

    /**
     * Attributes to check if the model has been successfully saved
     *
     * @var boolean
     */
    protected $saved = false;

    /**
     * Attributes to check if the model has been successfully validated
     *
     * @var boolean
     */
    protected $valid = false;

    /**
     * Array of the purgeable attributes
     *
     * @var array
     */
    protected static $purgeable = array();

    /**
     * Array of the field that can be searched by the filter field from the REST api query
     *
     * @var array
     */
    protected $fullSearchFields = array();

    protected $eagerTables = array();

    public function __construct( array $attributes = array() )
    {
        parent::__construct( $attributes );
        $this->validationErrors = new MessageBag;
    }

    /**
     * Setup the model events
     */
    public static function boot()
    {
        parent::boot();

        self::saving(function($model)
        {
            return $model->beforeSave();
        });

        self::saved(function($model)
        {
            return $model->afterSave();
        });

        self::deleting(function($model)
        {
            return $model->beforeDelete();
        });

        self::deleted(function($model)
        {
            return $model->afterDelete();
        });
    }

    /**
     * Check if the model is valid
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Check if the model has been save
     *
     * @return boolean
     */
    public function isSaved()
    {
        return $this->saved;
    }

    /**
     * Persist the model to the DB if it's valid
     *
     * @param  array   $options
     * @param  boolean $force
     * @return boolean
     */
    public function save(array $options = array(), $force = false)
    {
        if ( $force || $this->validate() )
        {
            return $this->performSave($options);
        } else
        {
            return false;
        }
    }

    /**
     * Scope to setup additional required conditions to fetch the models
     *
     * @param  mixed $query
     * @return mixed
     */
    public function scopeListConditions($query)
    {
        return $query;
        // return $query->where('content', '<>', '');
    }

    /**
     * Scope to setup a default order by clause
     *
     * @param  mixed $query
     * @return mixed
     */
    public function scopeListOrder($query)
    {
        return $query;
        // return $query->orderBy('id');
    }

    /**
     * Return the model validation errors
     *
     * @return array
     */
    public function errors()
    {
        return $this->validationErrors->toArray();
    }

    /**
     * Action to be executed before the model is saved on the database
     *
     * @return bool
     */
    protected function beforeSave()
    {
        return true;
    }

    /**
     * Action to be executed after the model is saved on the database
     *
     * @return bool
     */
    protected function afterSave()
    {
        return true;
    }

    /**
     * Action to be executed before the model will be deleted from the database
     *
     * @return bool
     */
    protected function beforeDelete()
    {
        return true;
    }

    /**
     * Action to be executed after the model has been deleted from the database
     *
     * @return bool
     */
    protected function afterDelete()
    {
        return true;
    }

    /**
     * Save the model on the database
     *
     * @param  array $options
     * @return boolean
     */
    protected function performSave(array $options) {

        $this->purgeAttributes();

        $this->saved = true;

        return parent::save($options);
    }

    /**
     * Validate the model by the defined rules
     *
     * @throws \Ingruz\Rest\Exceptions\ValidationErrorException
     * @return boolean
     */
    protected function validate()
    {
        $rules = $this->mergeRules();

        if ( empty($rules) ) return true;

        $data = $this->attributes;

        $validator = Validator::make($data, $rules);
        $success = $validator->passes();

        if ( $success )
        {
            if ( $this->validationErrors->count() > 0 )
            {
                $this->validationErrors = new MessageBag;
            }
        } else
        {
            $this->validationErrors = $validator->messages();
            throw new ValidationErrorException($validator->messages());
        }

        $this->valid = true;

        return $success;
    }

    /**
     * Get the attributes that need to be purged before the model will be saved
     *
     * @return array
     */
    public function getPurgeAttributes()
    {
        return $this->purgeable;
    }

    public function getFullSearchFields()
    {
        return $this->fullSearchFields;
    }

    public function getEagerTables()
    {
        return $this->eagerTables;
    }

    /**
     * Return a single array with the rules for the action required
     *
     * @return array
     */
    private function mergeRules()
    {
        $rules = static::$rules;
        $output = array();

        if (empty ($rules))
        {
            return $output;
        }

        if ($this->exists)
        {
            $merged = (isset($rules['update'])) ? array_merge_recursive($rules['save'], $rules['update']) : $rules['save'];
        } else
        {
            $merged = (isset($rules['create'])) ? array_merge_recursive($rules['save'], $rules['create']) : $rules['save'];
        }

        foreach ($merged as $field => $rules)
        {
            if (is_array($rules))
            {
                $output[$field] = implode("|", $rules);
            } else
            {
                $output[$field] = $rules;
            }
        }

        return $output;
    }

    /**
     * Purge the attributes that are not a field on the database table to prevent error during the save
     */
    protected function purgeAttributes()
    {
        $attributes = $this->getPurgeAttributes();

        if ( ! empty($attributes) )
        {
            foreach ( $attributes as $attribute )
            {
                unset($this->attributes[$attribute]);
            }
        }
    }
}