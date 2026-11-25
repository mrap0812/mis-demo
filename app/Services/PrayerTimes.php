<?php

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Jadwal shalat astronomis, parameter Kemenag RI:
 * Subuh 20 derajat, Isya 18 derajat, Ashar bayangan 1x (Syafi'i), ihtiyat +2 menit (terbit -2), imsak = Subuh - 10.
 * Hasil diuji cocok dengan jadwal Bimas Islam Kemenag untuk Banjarmasin, 17 April 2026 (selisih maks. 1 menit).
 * Nilai keluaran: menit sejak 00:00 waktu setempat.
 */
class PrayerTimes
{
    public function __construct(
        private float $lat = -3.3569571,   // koordinat dari peta di mis.id
        private float $lng = 114.6156541,
        private int $tz = 8,               // WITA
    ) {}

    /** @return array{imsak:int,fajr:int,sunrise:int,dhuhr:int,asr:int,maghrib:int,isha:int} */
    public function forDate(CarbonInterface $date): array
    {
        $jdate = $this->julian($date->year, $date->month, $date->day) - $this->lng / (15 * 24);
        $off = $this->tz - $this->lng / 15;
        $t = ['fajr' => 5, 'sunrise' => 6, 'dhuhr' => 12, 'asr' => 13, 'maghrib' => 18, 'isha' => 18];

        for ($i = 0; $i < 3; $i++) {
            $raw = [
                'fajr' => $this->angleTime($jdate, 20, ($t['fajr'] - $off) / 24, true),
                'sunrise' => $this->angleTime($jdate, 0.833, ($t['sunrise'] - $off) / 24, true),
                'dhuhr' => $this->midDay($jdate, ($t['dhuhr'] - $off) / 24),
                'asr' => $this->asr($jdate, ($t['asr'] - $off) / 24),
                'maghrib' => $this->angleTime($jdate, 0.833, ($t['maghrib'] - $off) / 24, false),
                'isha' => $this->angleTime($jdate, 18, ($t['isha'] - $off) / 24, false),
            ];
            foreach ($raw as $k => $v) {
                $t[$k] = $v + $off;
            }
        }

        $out = [];
        foreach ($t as $k => $h) {
            $m = $h * 60;
            $out[$k] = $k === 'sunrise' ? (int) floor($m - 2) : (int) ceil($m + 2);
        }
        $out['imsak'] = $out['fajr'] - 10;

        return $out;
    }

    public static function hhmm(int $minutes): string
    {
        $m = (($minutes % 1440) + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }

    private function julian(int $y, int $m, int $d): float
    {
        if ($m <= 2) {
            $y--;
            $m += 12;
        }
        $a = intdiv($y, 100);
        $b = 2 - $a + intdiv($a, 4);

        return floor(365.25 * ($y + 4716)) + floor(30.6001 * ($m + 1)) + $d + $b - 1524.5;
    }

    private function fix(float $a, float $b): float
    {
        $a = $a - $b * floor($a / $b);

        return $a < 0 ? $a + $b : $a;
    }

    /** @return array{0:float,1:float} [deklinasi, equation of time] */
    private function sun(float $jd): array
    {
        $r = M_PI / 180;
        $d = $jd - 2451545.0;
        $g = $this->fix(357.529 + 0.98560028 * $d, 360);
        $q = $this->fix(280.459 + 0.98564736 * $d, 360);
        $l = $this->fix($q + 1.915 * sin($g * $r) + 0.020 * sin(2 * $g * $r), 360);
        $e = 23.439 - 0.00000036 * $d;
        $ra = atan2(cos($e * $r) * sin($l * $r), cos($l * $r)) / $r / 15;

        return [asin(sin($e * $r) * sin($l * $r)) / $r, $q / 15 - $this->fix($ra, 24)];
    }

    private function midDay(float $jdate, float $t): float
    {
        return $this->fix(12 - $this->sun($jdate + $t)[1], 24);
    }

    private function angleTime(float $jdate, float $angle, float $t, bool $ccw): float
    {
        $r = M_PI / 180;
        $dec = $this->sun($jdate + $t)[0];
        $v = acos((-sin($angle * $r) - sin($dec * $r) * sin($this->lat * $r)) / (cos($dec * $r) * cos($this->lat * $r))) / $r / 15;

        return $this->midDay($jdate, $t) + ($ccw ? -$v : $v);
    }

    private function asr(float $jdate, float $t): float
    {
        $r = M_PI / 180;
        $dec = $this->sun($jdate + $t)[0];
        $a = -atan(1 / (1 + tan(abs($this->lat - $dec) * $r))) / $r;

        return $this->angleTime($jdate, $a, $t, false);
    }
}
