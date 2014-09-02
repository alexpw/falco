<?php

class Timer
{
    private $timers;
    private $maxNameLength = 0;
    private $total = 0;

    public function __construct()
    {
        $this->timers = new \SplObjectStorage;
    }

    public function start($name)
    {
        $this->maxNameLength = max(strlen($name), $this->maxNameLength);

        $run = new stdClass;
        $run->start = microtime(true);
        $run->name  = $name;
        return $run;
    }

    public function end(stdClass $run)
    {
        $end         = microtime(true);
        $run->time   = bcsub($end, $run->start, 6) * 1000;
        $this->total = bcadd($this->total, $run->time);

        $this->timers->attach($run);
    }

    public function __toString()
    {
        $marginLength = 3;
        $margin = str_repeat(' ', $marginLength);

        $maxNameLength = $this->maxNameLength + 7;
        $dashes = str_repeat('-', $maxNameLength + 25 + $marginLength);

        $out = '';
        $out .= $margin .
                str_pad('timer', $maxNameLength) .
                str_pad("time (ms)", 16) .
                str_pad("perc ", 8) .
                "\n";

        $out .= "$dashes\n";

        foreach ($this->timers as $run) {

            $perc = number_format(
                ($run->time * 100) / $this->total,
                2,
                '.',
                ''
            );
            $out .= $margin .
                    str_pad($run->name, $maxNameLength, ' ') .
                    str_pad($run->time, 14) .
                    str_pad($perc, 8, ' ', STR_PAD_LEFT) .
                    "\n";
        }
        $out .= "$dashes\n";
        return $out;
    }
}
