<?php

namespace App\Http\Controllers\Club\Concerns;

use App\Models\Club;
use Illuminate\Http\Request;

trait ResolvesClub
{
    protected function resolveClub(Request $request): Club
    {
        $club = $request->attributes->get('club');

        if ($club instanceof Club) {
            return $club;
        }

        return $request->user()->ownedClub()->firstOrFail();
    }
}
