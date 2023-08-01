<?php

include(__DIR__ . '/Elastic.php');

class Config
{
    protected static $_config = null;
    public static function set($k, $v)
    {
        if (is_null(self::$_config)) {
            self::$_config = new stdClass;
        }
        self::$_config->{$k} = $v;
    }

    public static function get($k)
    {
        return self::$_config->{$k};
    }

    public static function setIndex($name)
    {
        foreach (self::$_config->indexes as $map) {
            if ($map->user == $name) {
                putenv('ELASTIC_PASSWORD=' . $map->password);
                putenv('ELASTIC_PREFIX=' . $map->prefix);
                putenv('ELASTIC_URL=' . $map->url);
                putenv('ELASTIC_USER=' . $map->user);
                putenv('SEARCH_URL=' . $map->oldurl);
            }
        }
    }
}

if (file_exists(__DIR__ . '/config.json')) {
    $config_file = __DIR__ . "/config.json";
} elseif (file_exists("/etc/config.json")) {
    $config_file = "/etc/config.json";
}

Config::set('indexes', json_decode(file_get_contents($config_file)));
