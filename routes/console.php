<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('invoices:flag-overdue')->dailyAt('00:15');
