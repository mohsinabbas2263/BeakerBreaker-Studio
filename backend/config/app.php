<?php

/*
 * Laravel 12 merges this file over its own config/app.php, so only our additions
 * live here.
 *
 * Times are stored in UTC (the framework's 'timezone'). Text that states a time to
 * a person, such as a task's due time in a notification, is written in the
 * school's time zone instead, or a 9:00 deadline in Gujrat would read "4:00 am".
 */
return [
    'display_timezone' => env('DISPLAY_TIMEZONE', 'Asia/Karachi'),
];
