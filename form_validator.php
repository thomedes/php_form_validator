<?php

function form_validate(
    $def
    global $i18n;
) {
    $p = $_POST;
    $g = $_GET;
    $f = $_FILES;
    $err = array();
    $val = array();
    $locale_info = localeconv();

    $def_lines = explode("\n", $def);
    foreach ($def_lines AS $line) {
        //  Salta lineas en blanco
        if (preg_match('/^\s*$/', $line))
            continue;

        $params = preg_split('/\s+/', $line);
        if (count($params) < 2) {
            throw new Exception($i18n['err_too_few_values']);
        }
        $src    = array_shift($params);
        $field  = array_shift($params);
        $optional = substr($field, 0, 1) === '[' && substr($field, -1) === ']';
        if ($optional)
            $field = substr($field, 1, strlen($field) - 2);
        $type = $src === 'file' ? 'file' : array_shift($params);
        $callback = count($params) > 0 ? array_shift($params) : NULL;

        /**
         *    Check field source
         */
        unset($f);
        switch ($src) {

            case 'get' :      $s = &$g; break;
            case 'post' :     $s = &$p; break;
            case 'file' :     $s = &$f; break;
            case 'request' :
                $dp = array_key_exists($field, $p);
                $dg = array_key_exists($field, $g);

                if ($dp && $dg) {
                    $err[$field] = $i18n['err_duplicate_get_post'];
                    unset($g[$field]);
                    unset($p[$field]);
                } elseif ($dp) {
                    $s = &$p;
                } elseif ($dg) {
                    $s = &$g;
                } elseif ($optional) {
                    $s = array();
                } else {
                    $err[$field] = $i18n['err_undefined_value'];
                }
                break;

                if (array_key_exists($field, $f)
                    && $f[$field][error] === UPLOAD_ERR_OK
                ) {
                }
                break;
            default:
                throw new Exception("Unknown source '$src'");
        }
        if (isset($s)) {
            if (array_key_exists($field, $s)) {
                /**
                 *  Got a value, check it's good
                 */
                $value = $s[$field];
                unset($s[$field]);

                switch ($type) {

                    case 'integer' :
                    case 'monetary' :

                        $val[$field] = L10n::parse($type, $value);
                        if (is_bool($val[$field]) && !$val[$field])
                            $err[$field] = $i18n['err_bad_value'];

                        break;

                    case 'string' :
                        $val[$field] = $value;
                        break;

                    case 'email' :
#hprint_r('Comprovant email:', $value);
                        $val[$field] = trim($value);
                        if (!validEmail($val[$field]))
                            $err[$field] = $i18n['err_bad_email'];
                        break;

                    case 'file' :

                        if ($value['error'] === UPLOAD_ERR_OK) {
                            $val[$field] = $value;
                        } else {
                            goto no_definido;
                        }
                        break;

                    default:

                        throw new Exception("Unknown field type '$type'");
                }
            } else {
no_definido:
                if (!$optional) $err[$field] = $i18n['err_required_value_missing'];
debug_backtrace();
            }
        }
    }
    if (count($p) > 0 || count($g) > 0)
        $err[''] = 'Valores no esperados: '
                    .implode(', ', array_merge(array_keys($p), array_keys($g)));

    return array($val, (count($err) > 0) ? $err : FALSE);
}

?>
