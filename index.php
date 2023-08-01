<?php

include(__DIR__ . '/init.inc.php');

$uri = $_SERVER['REQUEST_URI'];
list(, $index, $other) = explode('/', $uri, 3);
Config::setIndexWithSecret($index);
list($index, $secret) = explode(':', urldecode($index), 2);

$prefix = getenv('ELASTIC_PREFIX');
header('Content-Type: application/json');
if (array_key_exists('HTTP_CONTENT_LENGTH', $_SERVER)) {
    $content = file_get_contents('php://input');
    $obj = json_decode($content);
    $oldobj = json_decode($content);
    if (property_exists($obj, 'query') and property_exists($obj->query, 'filtered')) {
        $obj->query->bool = new StdClass;
        $obj->query->bool->filter = [];
        $obj->query->bool->must_not = [];

        $query = $obj->query->filtered->query;
        if (property_exists($query, 'query_string')) {
            $obj->query->bool->filter[] = $query;
        }

        $query = $obj->query->filtered->filter;
        if (property_exists($query, 'and')) {
            foreach ($query->and as $q) {
                $q = $q->query;
                if (property_exists($q, 'query_string')) {
                    $qs = $q->query_string;
                    if (preg_match('#(.*):(.*)$#', $qs->query, $matches)) {
                        list(, $k, $v) = $matches;
                        if (strpos($k, '-') === 0) {
                            $obj->query->bool->must_not[] = ['term' => [ltrim($k, '-') => $v]];
                        } else if (preg_match('#\[(.*) TO (.*)\]#', $v, $matches2)) {
                            $q = ['range' => [$k = []]];
                            if ($matches2[1] != '*') {
                                $q['range'][$k]['gte'] = $matches2[1];
                            }
                            if ($matches2[2] != '*') {
                                $q['range'][$k]['lte'] = $matche2[2];
                            }
                        } else {
                            $obj->query->bool->filter[] = ['term' => [$k => $v]];
                        }
                    } else {
                        $obj->query->bool->filter[] = $q;
                    }
                } else {
                    $obj->query->bool->filter[] = $q;
                }
            }
        }
        unset($obj->query->filtered);
    }
    $ret = Elastic::dbQuery("/{$prefix}" . ltrim($other),  $_SERVER['REQUEST_METHOD'], json_encode($obj));
} else {
    $ret = Elastic::dbQuery("/{$prefix}" . ltrim($other),  $_SERVER['REQUEST_METHOD']);
}

if (property_exists($ret, 'hits') and property_exists($ret->hits, 'total') and property_exists($ret->hits->total, 'value')) {
    $ret->hits->total = $ret->hits->total->value;
}

echo json_encode($ret);

