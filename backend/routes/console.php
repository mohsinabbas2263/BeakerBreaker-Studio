<?php

use Illuminate\Support\Facades\Schedule;

// FR-09.5: an assignment becomes overdue with no teacher action.
Schedule::command('assignments:mark-overdue')->everyFifteenMinutes();
