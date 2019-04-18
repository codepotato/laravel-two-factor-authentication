<?php

namespace MichaelDzjap\TwoFactorAuth;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MichaelDzjap\TwoFactorAuth\TwoFactorAuth;
use Illuminate\Support\Facades\DB;

trait TwoFactorAuthenticable
{
    /**
     * Get the two-factor auth record associated with the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function twoFactorAuth() : HasOne
    {
        return $this->hasOne(TwoFactorAuth::class, $this->getKeyName());
    }

    /**
     * Set the two-factor auth id.
     *
     * @param  string $id
     * @return void
     */
    public function setTwoFactorAuthId(string $id) : void
    {
        $enabled = config('twofactor-auth.enabled', 'per_user');
        if ($enabled === 'per_user') {
            // respect when 2fa is not set for user, never insert
            $this->twoFactorAuth->update(['id' => $id]);
        }
        elseif ($enabled === 'always') {
            $this->upsertTwoFactorAuthId($id);
        }
    }

    /**
     * Get the two-factor auth id.
     *
     * @return string $id
     */
    public function getTwoFactorAuthId() : string
    {
        return $this->twoFactorAuth->id;
    }

    /**
     * @param string $id
     */
    private function upsertTwoFactorAuthId(string $id) : void
    {
        DB::transaction(function () use ($id) {
            $attributes = ['id' => $id];
            if (!$this->twoFactorAuth()->exists()) {
                $this->twoFactorAuth()->create($attributes);
            } else {
                $this->twoFactorAuth->update($attributes);
            }
        });
    }
}
