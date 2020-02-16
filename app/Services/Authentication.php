<?php

namespace BRM\Authentication\app\Services;

use Validator;
use JWTAuth;
use Exception;

class Authentication
{
    use \BRM\Vivid\app\Traits\Vivid;
    public function __construct()
    {
        $this->model = \BRM\Authentication\app\Models\Credential::class;
        $this->fields = [
          'email',
          'password',
          'subject',
          'subjectId',
          'series'
        ];
        $this->sanitise = [
          'email'=> ['string','email']
        ];
    }

    /**
     * authenticate
     *
     * @param mixed $data
     * @return void
     */
    public function store($data = [])
    {
        $this->validation = [
          'email' => ['required','unique:tenant.credentials,email'],
          'subject' => ['required'],
          'subjectId' => ['required']
        ];

        $this->hook('beforeSave', function () {
            try {
                $model = (new $this->data['subject']);
            } catch (Exception $e) {
                $this->response = [
                  'status'=>'failed',
                  'data'=> [
                    'errors'=> ['Subject is invaild.']
                  ]
                ];
                return false;
            }
        });

        return $this->vivid('store', $data);
    }

    /**
     * authenticate
     *
     * @param mixed $data
     * @return void
     */
    public function authenticate($data = [])
    {
        $validator = Validator::make($data, [
          'token' => 'string',
          'email' => 'required_without:token|email|exists:tenant.credentials,email',
          'password' => 'required_without:token',
          'persistent' => 'boolean'
        ]);
        $credentials = null;
        $validator->after(function ($validator) use ($data, &$credentials) {
            if (!$validator->failed()) {
                if (!isset($data['token'])) {
                    $credentials = (new $this->model)->where('email', $data['email'])->get()->first();
                    if (!$credentials|| !\Hash::check($data['password'], $credentials->password)) {
                        $validator->errors()->add('password', 'The password provided is invalid');
                    }
                } else {
                    try {
                        $credentials = \JWTAuth::toUser($data['token']);
                    } catch (Exception $e) {
                        if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                            $validator->errors()->add('token', 'Token is Invalid');
                        } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                            $validator->errors()->add('token', 'Token is Expired');
                        } else {
                            $validator->errors()->add('token', 'Something is wrong');
                        }
                    }
                }
            }
        });
        if ($validator->fails()) {
            if ($credentials) {
                $credentials->lastAttempt = now();
                $credentials->save();
            }
            return [
              'status'=>'failed',
              'data'=>[
                'errors'=> $validator->messages()
              ]
            ];
        }
        if (isset($data['persistent']) && $data['persistent']) {
            $credentials->claims['exp'] = strtotime("+2 months");
            $credentials->claims['persistent'] = true;
        } else {
            $credentials->claims['exp'] = strtotime("+48 hours");
        }
        $credentials->lastLogin = now();
        $credentials->lastAttempt = now();
        if (empty($credentials->series)) {
            $credentials->series = md5(bcrypt(rand()));
        }
        $credentials->save();
        return [
          'status'=>'success',
          'data'=>[
            'kind' =>  (new $credentials->subject)->getTable(),
            'token' => \JWTAuth::fromUser($credentials),
            'expires' =>  gmdate("Y-m-d H:i:s", $credentials->claims['exp']),
            'user' => $credentials->user,
          ]
        ];
    }

    /**
     * options
     *
     * @param mixed $data
     * @return void
     */
    public function options($data = [])
    {
        $validator = Validator::make($data, [
          'token' => 'required|string'
        ]);
        $credentials = null;
        $token = null;
        $validator->after(function ($validator) use ($data, &$credentials, &$token) {
            if (!$validator->failed()) {
                try {
                    $token = \JWTAuth::manager()->decode(new \Tymon\JWTAuth\Token($data['token']));
                    $credentials = (new $this->model)->find($token['sub']);
                } catch (Exception $e) {
                    if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                        $validator->errors()->add('token', 'Token is Invalid');
                    } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                        $validator->errors()->add('token', 'Token is Expired');
                    } else {
                        $validator->errors()->add('token', 'Something is wrong');
                    }
                }
            }
        });
        if ($validator->fails()) {
            return [
              'status'=>'failed',
              'data'=>[
                'errors'=> $validator->messages()
              ]
            ];
        }
        return [
          'status'=>'success',
          'data'=>[
            'kind' =>  (new $credentials->subject)->getTable(),
            'expires' =>  gmdate("Y-m-d H:i:s", $token['exp'])
          ]
        ];
    }

    /**
     * destroy
     *
     * @param mixed $data
     * @return void
     */
    public function destroy($data = [])
    {
        $validator = Validator::make($data, [
          'token' => 'string',
          'email' => 'required_without:token|email|exists:tenant.credentials,email',
          'all' => 'boolean|required_without:token'
        ]);
        $credential = null;
        $validator->after(function ($validator) use ($data, &$credential) {
            if (!$validator->failed()) {
                if (isset($data['email'])) {
                    $credential = (new $this->model)->where('email', $data['email'])->first();
                } else {
                    try {
                        $token = \JWTAuth::manager()->decode(new \Tymon\JWTAuth\Token($data['token']));
                        $credential = (new $this->model)->find($token['sub']);
                    } catch (Exception $e) {
                        $validator->errors()->add('token', 'The token provided is invaild.');
                    }
                }
                if (isset($data['all']) && $data['all']) {
                    $credential->series = md5(bcrypt(rand()));
                    $credential->save();
                }
          
                try {
                    \JWTAuth::manager()->invalidate(new \Tymon\JWTAuth\Token($data['token']));
                } catch (Exception $e) {
                    if ($e instanceof \Tymon\JWTAuth\Exceptions\JWTException) {
                        if (!isset($data['all']) || !$data['all']) {
                            $validator->errors()->add('token', 'Unable to invalidate token.');
                        }
                    }
                }
            }
        });
        if ($validator->fails()) {
            return [
              'status'=>'failed',
              'data'=>[
                'errors'=> $validator->messages()
              ]
            ];
        }

        return [
          'status'=>'success',
          'data'=>[
            'kind' =>  (new $credential->subject)->getTable(),
            'token' => null,
            'expires' => null,
            'user' => null
          ]
        ];
    }
}
