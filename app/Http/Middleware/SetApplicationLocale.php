<?php

namespace App\Http\Middleware;

use App\Services\LocalePreference;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetApplicationLocale
{
    public function __construct(private LocalePreference $localePreference) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->localePreference->get();

        if ($locale !== null) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
