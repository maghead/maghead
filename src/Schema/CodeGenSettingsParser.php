<?php
namespace Maghead\Schema;

class CodeGenSettingsParser
{
    static public function parse($comment)
    {
        $settings = [];
        preg_match_all('/@codegen (\w+)(?:\s*=\s*(\S+))?$/m', $comment, $allMatches);
        for ($i = 0; $i < count($allMatches[0]); ++$i) {
            $key = $allMatches[1][$i];
            $value = $allMatches[2][$i];

            if ($value === '') {
                $value = true;
            } else {
                if (strcasecmp($value, 'true') == 0 || strcasecmp($value, 'false') == 0) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                } elseif (preg_match('/^\d+$/', $value)) {
                    $value = intval($value);
                }
            }
            $settings[$key] = $value;
        }
        return $settings;
    }
}
