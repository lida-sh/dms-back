<?php

namespace App\Services;

use Hekmatinasser\Verta\Verta;
use Carbon\Carbon;
class DataConverter
{
static function convertToGregorian($date)
{
    if (empty($date)) {
        return null;
    }

    if ($date instanceof Carbon) {
        return $date;
    }

    if ($date instanceof Verta) {
        return $date->DateTime(); // Convert to Carbon
    }

    if (is_string($date)) {
        if (preg_match('/^(13|14)\d{2}\-\d{1,2}\-\d{1,2}$/', $date)) {
            try {
                $verta = Verta::parseFormat('Y-m-d', $date);
                return $verta->DateTime();
            } catch (\Exception $e) {
                return $date;
            }
        } else {
            try {
                return Carbon::parse($date);
            } catch (\Exception $e) {
                return $date;
            }
        }
    }

    return $date;
}
}