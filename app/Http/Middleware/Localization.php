<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /* Set new lang with the use of session */
        $lang = (string) session()->get('lang', 'en');
        if (! in_array($lang, ['en', 'id'], true)) {
            $lang = 'en';
        }
        App::setLocale($lang);
        return $next($request);
    }
}
