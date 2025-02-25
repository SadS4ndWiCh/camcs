<?php

namespace App\Services;

use App\Enums\InsigniaTypes;

class PrayService
{
    public function analyze(string $prayer, InsigniaTypes $insignia)
    {
        $length = strlen($prayer);

        $words = explode(' ', str_replace(',', ' ', strtolower($prayer)));
        $totalWords = count($words);

        $totalGods = 0;
        for ($i = 0; $i < $totalWords; $i++) {
            if ($words[$i] == 'god') {
                $totalGods++;
            }
        }

        // Some comparations
        $presenceOfGodInPray = $totalGods / $totalWords;
        $prayerLengthFromTheMaximum = $length / 1024;

        if ($totalGods == 0) {
            return 0;
        }

        $worth = 0;

        /*
            To give more attraction from gods, is valuable to say their name 
            during pray. That way, they can feel more gratitude from you.

            But, saying very frequently in the pray, like around 40% to 70% of 
            the pray don't fit very well. So, to not to encourage that, only 1 SP
            is gain with that.
        */

        $worth += match (true) {
            $presenceOfGodInPray < 0.4                                 => max(1, $totalGods * 0.15),
            $presenceOfGodInPray >= 0.4 && $presenceOfGodInPray <= 0.7 => 1,
            default                                                    => 0
        };

        /*
            A person that gives more attention to their prey, giving a more long 
            pray, can receive more acknowledgement from gods.
        */
        $worth += 10 * $prayerLengthFromTheMaximum;

        /*
            Ever in same instant of time should be choosed the same insignia.
        */
        srand(time());
        $luckyInsignia = InsigniaTypes::random();
        srand();

        /*
            The worth of prayer can increase based in individual's insignia and 
            their lucky.
        */
        $worth += match ($insignia) {
            (InsigniaTypes::DARKNESS || InsigniaTypes::LIGHT) && $luckyInsignia => 4,
            (InsigniaTypes::DARKNESS || InsigniaTypes::LIGHT)                   => 2,
            $luckyInsignia                                                      => 1,
            default                                                             => 0
        };

        return $worth;
    }
}
