<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Member\Contracts\MemberSatang as MemberSatangContract;
use Illuminate\Database\Eloquent\Model;

class MemberSatang extends Model implements MemberSatangContract
{

    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public $table = 'member_satang';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'bank_code',
        'shortcode',
        'value'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'member_code' => 'integer',
        'bank_code' => 'integer',
        'shortcode' => 'string',
        'value' => 'integer'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    protected static $rules = [
        'member_code' => 'required|integer',
        'bank_code' => 'required|integer',
        'shortcode' => 'required|string|max:10',
        'value' => 'nullable|integer'
    ];
}
