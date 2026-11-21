<?php

use Illuminate\Support\Facades\Schedule;

// Jadwal harian: pengingat masa berlaku 90/60/30 hari, eskalasi SLA, dan evaluasi retensi.
Schedule::command('archive:reminders')->dailyAt('07:00');
Schedule::command('archive:escalate')->hourly();
Schedule::command('archive:retention')->dailyAt('01:00');
