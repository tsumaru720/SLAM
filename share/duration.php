<?php
function duration($seconds, $displaySeconds = 1, $displayMinutes = 1, $displayHours = 1) {
        $time['days'] = (int) $seconds / 86400 % 86400;
        $time['hours'] = (int) $seconds / 3600 % 24;
        $time['minutes'] = (int) $seconds / 60 % 60;
        $time['seconds'] = (int) $seconds % 60;

        $string = '';
        if ($time['days'] > 0) {
                $string .= $time['days'] . (($time['days'] == 1) ? 'd ' : 'd ');
        }
        if ($displayHours == 0) { return empty($string) ? '0d' : $string; }

        if ($time['hours'] > 0) {
                $string .= $time['hours'] . (($time['hours'] == 1) ? 'h ' : 'h ');
        }
        if ($displayMinutes == 0) { return empty($string) ? '0h' : $string; }

        if ($time['minutes'] > 0) {
                $string .= $time['minutes'] . (($time['minutes'] == 1) ? 'm ' : 'm ');
        }
        if ($displaySeconds == 0) { return empty($string) ? '0m' : $string; }

        if ($time['seconds'] > 0) {
                $string .= $time['seconds'] . (($time['seconds'] == 1) ? 's ' : 's ');
        }

        $string = trim($string);
        return empty($string) ? '0h' : $string;
}

?>
