<?php

namespace App\Http\Middleware;

use App\Services\LocalePreference;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLanguagePreference
{
    public function __construct(private LocalePreference $localePreference) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->localePreference->get() === null) {
            return to_route('language-preference.create');
        }

        return $next($request);
    }
}
