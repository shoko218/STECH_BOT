<?php

namespace App\Http\Middleware;

use Closure;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user_id === config('const.slack_id.administrator')) {
            return $next($request);
        } else {
            response('このコマンドは管理者専用です。', 200)->send();
        }
    }
}
