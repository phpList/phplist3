<?php

/**
 * CSV parser compliant with RFC 4180
 *
 * @author Lukas Jelinek, CZ.NIC
 */
class CSVParser {

    private $recordDelimiter;
    private $fieldDelimiter;


    /**
     * Constructor
     *
     * @param string $recordDelimiter record delimiter; default is "\n" (new line)
     * @param string $fieldDelimiter field delimiter; defailt is "," (comma)
     */
    public function __construct($recordDelimiter = "\n", $fieldDelimiter = ",") {
        $this->recordDelimiter = $recordDelimiter;
        $this->fieldDelimiter = $fieldDelimiter;
    }

    /**
     * Parses a CSV string.
     *
     * This method parses a CSV string and creates a two-dimensional array
     * containing lines as the first level and fields as the second one.
     *
     * @param string $str string to be parsed
     * @return array two-dimensional array
     */
    public function parse($str) {
        $str = preg_replace_callback('/([^"]*)("((""|[^"])*)"|$)/s', array($this, 'parseCSVQuotes'), $str);
        $str = preg_replace(sprintf('/%s$/', $this->recordDelimiter), '', $str);
        return array_map(array($this, 'parseCSVLine'), explode($this->recordDelimiter, $str));
    }

    private function parseCSVQuotes($matches) {
        $str = str_replace("\r", "\rR", $matches[3]);
        $str = str_replace($this->recordDelimiter, "\rN", $str);
        $str = str_replace('""', "\rQ", $str);
        $str = str_replace($this->fieldDelimiter, "\rD", $str);
        return preg_replace('/\r\n?/', "\n", $matches[1]).$str;
    }

    private function parseCSVLine($line) {
        return array_map(array($this, 'parseCSVField'), explode($this->fieldDelimiter, $line));
    }

    private function parseCSVField($field) {
        $field = str_replace("\rD", $this->fieldDelimiter, $field);
        $field = str_replace("\rQ", '"', $field);
        $field = str_replace("\rN", $this->recordDelimiter, $field);
        $field = str_replace("\rR", "\r", $field);
        return $field;
    }


    /**
     * Parses a CSV string.
     *
     * This static method creates an instance of the parser and then calls
     * its parse() method.
     *
     * @param string $str string to be parsed
     * @param type $recordDelimiter record delimiter; default is "\n" (new line)
     * @param string $fieldDelimiter field delimiter; defailt is "," (comma)
     * @return array two-dimensional array
     */
    public static function parseCSV($str, $recordDelimiter = "\n", $fieldDelimiter = ",") {
        $p = new CSVParser($recordDelimiter, $fieldDelimiter);
        return $p->parse($str);
    }
}
