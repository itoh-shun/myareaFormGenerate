<?php

class SanitizeHelper
{
    public static function sanitize($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
?>
