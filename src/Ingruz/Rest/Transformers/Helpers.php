<?php namespace Ingruz\Rest\Transformers;

use Carbon\Carbon;

class Helpers {

    public function niceDate($date)
    {
        try
        {
            $objDate = Carbon::createFromFormat('Y-m-d H:i:s', $date);

            return $objDate->format('d/m/Y - H:i:s');
        } catch (Exception $e)
        {
            return '';
        }
    }
} 