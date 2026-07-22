<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Legacy Write
    |--------------------------------------------------------------------------
    |
    | When enabled, both canonical EventAttendance and legacy Absensi/IzinAbsensi
    | records are written. When disabled, only EventAttendance is written for
    | participants with a resolvable Participation. Legacy fallback writes for
    | unmappable participants remain active.
    |
    | true  = canonical + legacy dual-write (default, backward compatible)
    | false = canonical-only for mapped participants
    |
    */
    'attendance_legacy_write' => env('ATTENDANCE_LEGACY_WRITE', true),
];
