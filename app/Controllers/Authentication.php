<?php
namespace BRM\Authentication\app\Controllers;

use Illuminate\Http\Request;

class Authentication extends \Illuminate\Routing\Controller
{
    use \BRM\Vivid\app\Traits\Control;

    public function __construct()
    {
        $this->service = \BRM\Authentication\app\Services\Authentication::class;
    }

    public function authenticate(Request $request)
    {
        $response = (new $this->service)->authenticate($request->all());
        return response()->api($response, 200);
    }

    public function recover(Request $request)
    {
        $response = (new $this->service)->recover($request->all());
        return response()->api($response, 200);
    }

    public function options(Request $request)
    {
        $response = (new $this->service)->options($request->all());
        return response()->api($response, 200);
    }
}
