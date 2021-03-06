<?php namespace CryptoPolice\Academy\Models;

use Model;

class Training extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Sortable;

    protected $dates = ['deleted_at'];

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [

        'category' => [
            'CryptoPolice\Academy\Models\TrainingCategory',
            'key' => 'category_id'
        ],

    ];


    public $hasMany = [

        'views' => [
            'CryptoPolice\academy\Models\TrainingView',
            'key'   => 'training_id'
        ],

    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cryptopolice_academy_trainings';
}
