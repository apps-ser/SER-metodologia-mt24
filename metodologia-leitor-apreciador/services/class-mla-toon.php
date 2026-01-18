<?php
/**
 * TOON (Token-Oriented Object Notation) Encoder.
 *
 * This is a lightweight implementation for token optimization in LLM payloads.
 *
 * @package MetodologiaLeitorApreciador
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Toon
 *
 * Encodes data in TOON format to save tokens and improve LLM parsing.
 */
class MLA_Toon
{

    /**
     * Encode a PHP value to TOON format.
     *
     * @param mixed $value The value to encode.
     * @param int $indent Internal indentation level.
     * @return string TOON formatted string.
     */
    public static function encode($value, $indent = 0)
    {
        $space = str_repeat('  ', $indent);

        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            // Se tiver quebras de linha ou caracteres especiais, não precisa de quotes se for TOON puro, 
            // mas simplificamos para manter legível.
            return $value;
        }

        if (is_array($value)) {
            if (empty($value)) {
                return '[]';
            }

            // 1. Detectar se é uma tabela (Array uniforme de objetos)
            if (self::is_uniform_array($value)) {
                return self::encode_table($value, $indent);
            }

            // 2. Verificar se é um array associativo (Objeto)
            if (self::is_assoc($value)) {
                $lines = array();
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        $count = count($v);
                        $suffix = self::is_uniform_array($v) ? "($count):" : ":";
                        $lines[] = "$space$k$suffix\n" . self::encode($v, $indent + 1);
                    } else {
                        $encoded_v = self::encode($v);
                        $lines[] = "$space$k: $encoded_v";
                    }
                }
                return implode("\n", $lines);
            }

            // 3. Array simples (Lista)
            $lines = array();
            foreach ($value as $item) {
                $lines[] = "$space- " . trim(self::encode($item, $indent + 1));
            }
            return implode("\n", $lines);
        }

        return (string) $value;
    }

    /**
     * Encodes a uniform array of objects as a TOON table.
     */
    private static function encode_table($array, $indent)
    {
        if (empty($array))
            return '';

        $space = str_repeat('  ', $indent);
        $keys = array_keys($array[0]);

        $lines = array();

        // Header
        $lines[] = $space . "| " . implode(" | ", $keys) . " |";

        // Rows
        foreach ($array as $row) {
            $values = array();
            foreach ($keys as $key) {
                $val = isset($row[$key]) ? $row[$key] : '';

                // Tratar arrays internos no valor da célula (ex: respostas)
                if (is_array($val)) {
                    $val = '{' . str_replace("\n", " ", self::encode($val)) . '}';
                } else {
                    // Remover quebras de linha para não quebrar a tabela
                    $val = str_replace(array("\r", "\n"), " ", (string) $val);
                }

                $values[] = $val;
            }
            $lines[] = $space . "| " . implode(" | ", $values) . " |";
        }

        return implode("\n", $lines);
    }

    /**
     * Checks if an array is an associative array.
     */
    private static function is_assoc(array $arr)
    {
        if (array() === $arr)
            return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Checks if an array is a uniform array of associative arrays (same keys).
     */
    private static function is_uniform_array(array $arr)
    {
        if (empty($arr) || !is_array($arr[0]) || !self::is_assoc($arr[0])) {
            return false;
        }

        $keys = array_keys($arr[0]);
        foreach ($arr as $item) {
            if (!is_array($item) || array_keys($item) !== $keys) {
                return false;
            }
        }

        return true;
    }
}
