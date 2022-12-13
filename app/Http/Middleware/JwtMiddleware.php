<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use TheSeer\Tokenizer\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware {
    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */

    public function __construct() {
        $this->result = ( object ) array(
            'status' => false,
            'status_code' => 200,
            'message' => null,
            'data'=> null,
            'token' => null,
            'debug' => null
        );

    }

    public function handle( Request $request, Closure $next ) {
        if ( Auth::check() ) {
            return $next( $request );
        }

        $this->result->status_code = 401;
        $this->result->message = 'Unauthorized';
        return response()->json( $this->result );
    }
}
