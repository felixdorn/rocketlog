<?php

namespace App\Http\Controllers;

use App\Events\CollectionUpdated;
use App\Events\DailyLogUpdated;
use App\Models\Collection;

class CollectionBulletDoneController extends Controller
{
    public function destroy(Collection $collection)
    {
        $this->authorize('update', $collection);

        $completeBullets = $collection->bullets()->whereState('complete')->get();

        $completeBullets->each->delete();

        return redirect(route('c.show', $collection));
    }
}
