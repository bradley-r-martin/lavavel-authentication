<?php

namespace BRM\Authentication\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Credential extends Authenticatable implements JWTSubject
{
    use \BRM\Vivid\app\Traits\Model;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $hidden = [
      'username',
      'password',
      'series'
    ];

    protected $dates = [
      'createdAt',
      'updatedAt',
      'deletedAt'
    ];

    public $claims = [
      'series' => ''
    ];

    public function setPasswordAttribute($pass)
    {
        $this->attributes['password'] = Hash::make($pass);
    }

    public function user()
    {
        return $this->belongsTo($this->subject, 'subjectId', 'id');
    }


    public function resetSeries(){
      $this->series = md5(bcrypt(rand()));
      $this->save();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
  
    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        $this->claims['series'] = $this->series;
        return $this->claims;
    }
}
