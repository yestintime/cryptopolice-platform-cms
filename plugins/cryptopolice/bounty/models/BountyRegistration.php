<?php namespace CryptoPolice\Bounty\Models;

use Model;

/**
 * Model
 */
class BountyRegistration extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    use \October\Rain\Database\Traits\SoftDelete;

    protected $dates = ['deleted_at'];

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    protected $jsonable = ['fields_data'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cryptopolice_bounty_user_registration';
}