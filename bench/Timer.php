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
        $run->start_mem  = memory_get_usage();
        $run->start_time = microtime(true);
        $run->name       = $name;
        return $run;
    }

    public function end(stdClass $run)
    {
        $end         = microtime(true);
        $mem         = memory_get_usage() - $run->start_mem;
        $run->time   = bcsub($end, $run->start_time, 6) * 1000;
        $run->mem    = $this->memoryToString($mem);
        $this->total = bcadd($this->total, $run->time);

        $this->timers->attach($run);
    }

    public function __toString()
    {
        $marginLength = 3;
        $margin = str_repeat(' ', $marginLength);

        $maxNameLength = $this->maxNameLength + 7;
        $dashes = str_repeat('-', $maxNameLength + 35 + $marginLength);

        $out = '';
        $out .= $margin .
                str_pad('timer', $maxNameLength) .
                str_pad("time (ms)", 12) .
                str_pad("perc ", 12) .
                str_pad("mem", 6, ' ', STR_PAD_LEFT) .
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
                    str_pad($run->time, 10) .
                    str_pad($perc, 6, ' ', STR_PAD_LEFT) .
                    str_pad($run->mem, 14, ' ', STR_PAD_LEFT) .
                    "\n";
        }
        $out .= "$dashes\n";
        return $out;
    }
    private function memoryToString($mem) {
        if ($mem < 1024) {
            return "$mem Bytes";
        } else if ($mem < 1048576) {
            return round($mem / 1024, 2)." KB";
        } else {
            return round($mem / 1048576, 2)." MB";
        }
    }
}
