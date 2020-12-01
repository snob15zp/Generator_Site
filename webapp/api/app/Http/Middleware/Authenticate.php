<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class Authenticate extends BaseMiddleware
{
    public function handle($request, Closure $next, $optional = null)
    {
        $this->auth->setRequest($request);

        try {
            if (!$user = $this->auth->parseToken()->authenticate()) {
                abort(401, 'JWT error: User not found');
            }
        } catch (TokenExpiredException $e) {
            abort(401, 'JWT error: Token has expired');
        } catch (TokenInvalidException $e) {
            abort(401, 'JWT error: Token is invalid');
        } catch (JWTException $e) {
            if ($optional === null) {
                abort(401);
            }
        }

        return $next($request);
    }
}
