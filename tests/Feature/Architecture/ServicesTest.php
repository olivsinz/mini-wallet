<?php

declare(strict_types=1);

arch('services classes')
    ->expect('App\Services')
    ->toExtendNothing()
    ->toOnlyBeUsedIn(['App\Http\Controllers']);
