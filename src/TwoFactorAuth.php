<?php

namespace MichaelDzjap\TwoFactorAuth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuth extends Model
{
    /**
     * The reference to the user model.
     *
     * @var string
     */
    private $model;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'user_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];

    /**
     * The primary key of the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Get the user that owns the two-factor auth.
     *
     * @param \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        $model = $this->model();

        return $this->belongsTo($model, 'user_id', (new $model)->getKeyName());
    }

    /**
     * Get the reference to the user model.
     *
     * @return string
     */
    private function model(): string
    {
        if (is_null($this->model)) {
            $this->model = config('twofactor-auth.model');
        }

        return $this->model;
    }
}
