<?php

include(__DIR__ . '/init.inc.php');
$index = 'INDEX HERE'; // TODO
Config::setIndex($index);
$prefix = getenv('ELASTIC_PREFIX');
foreach (Elastic::oldDbQuery("/{$index}/_mapping")->{$index}->mappings as $mapping => $map) {
    unset($map->_timestamp);
    foreach ($map->properties as $k => $mapdata) {
        if ($mapdata->type == 'string') {
            $map->properties->{$k}->type = 'text';
        }
        if (property_exists($mapdata, 'format') and $mapdata->format == 'epoch_millis||dateOptionalTime') {
            $map->properties->{$k}->type = 'date_nanos';
            unset($map->properties->{$k}->format);
        }
    }

    try {
        Elastic::createIndex($mapping, $map);
    } catch (Exception $e) {
    }

    $i = 0;
    $url = "/{$index}/{$mapping}/_search?scroll=1m&size=1000";
    $data = null;
    do {
        if (is_null($data)) {
            $ret = Elastic::oldDbQuery($url);
        } else {
            $ret = Elastic::oldDbQuery("/_search/scroll", "GET", json_encode([
                'scroll' => '1m',
                'scroll_id' => $data,
            ]));
        }
        foreach ($ret->hits->hits as $hit) {
            $i ++;
            Elastic::dbBulkInsert($mapping, $hit->_id, $hit->_source);
        }
        if (!$ret->hits->hits) {
            break;
        }
        $data = $ret->_scroll_id;
    } while (true);
    Elastic::dbBulkCommit();
}
