<?php

function form_validate(
    $def
) {
    $p = $_POST;
    $g = $_GET;
    $err = array();

    $def_lines = explode("\n", $def);
    foreach ($def_lines AS $line) {
        $params = preg_split('/\s+/', $line);
        if (count($params) < 3) {
            throw new Exception("Too few params");
        }
        $src    = array_shift($params);
        $field  = array_shift($params);
        $type   = array_shift($params);
        $callback = count($params) > 0 ? array_shift($params) : NULL;

        /**
         *    Check field source
         */
        unset($f);
        switch ($src) {
            case 'get' :      $f = &$g; break;
            case 'post' :     $f = &$p; break;
            case 'request' :
                $dp = array_key_exists($field, $p);
                $dg = array_key_exists($field, $g);

                if ($dp && $dg) {
                    $err[$field] = "Campo duplicado en GET y POST";
                    unset($g[$field]);
                    unset($p[$field]);
                } elseif ($dp) {
                    $f = &$p;
                } elseif ($dg) {
                    $f = &$g;
                } else {
                    $err[$field] = "Valor no definido";
                }
                break;
            default:
                throw new Exception("Unknown source '$src'");
        }
        if (isset($f)) {
            if (array_key_exists($field, $f)) {
                /**
                 *  Got a value, check it's good
                 */
                $value = $f[$field];
                unset($f[$field]);

                switch ($type) {

                    case 'integer':

                        if (!is_numeric($value) || $value != (int)$value)
                            $err[$field] = 'El valor no es un entero';
                        break;

                    case 'string':

                        break;

                    default:

                        throw new Exception("Unknown field type '$type'");
                }
            } else {
                $err[$field] = 'Valor no definido';
            }
        }
    }
    if (count($p) > 0 || count($g) > 0)
        $err[] = 'Valores no esperados';

    return (count($err) > 0) ? $err : FALSE;
}

?>