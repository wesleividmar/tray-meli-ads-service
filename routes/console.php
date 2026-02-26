<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('meli:sync-items')->everyTenMinutes()->withoutOverlapping()->onOneServer()->runInBackground();