<?php

namespace App\Http\Middleware;

use Closure;
use JoliCode\Slack\ClientFactory;

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
            $slack_client = ClientFactory::create(config('services.slack.token'));

            $slack_client->chatPostMessage([
                'channel' => $request->user_id,
                'text' => ':warning: このコマンドは管理者専用です。'
            ]);

            return response('', 200)->send();
        }
    }
}
