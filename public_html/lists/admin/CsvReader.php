<?php

class CsvReader
{
    private $fh;
    private $delimiter;
    private $totalRows;

    const ENCLOSURE = '"';
    const ESCAPE = "\0";

    /**
     * Constructor.
     * Setting auto_detect_line_endings is needed to allow CR as line separator.
     * Read all rows from the file to get the count of rows ignoring empty lines.
     */
    public function __construct($filename, $delimiter)
    {
        ini_set('auto_detect_line_endings', true);
        $this->fh = fopen($filename, 'r');
        $this->delimiter = $delimiter;
        $this->totalRows = 0;

        while ($row = fgetcsv($this->fh, 0, $this->delimiter, self::ENCLOSURE, SELF::ESCAPE)) {
            if ($row[0] !== null) {
                ++$this->totalRows;
            }
        }
        rewind($this->fh);
    }

    /**
     * Return the number of rows in the file.
     *
     * @return int
     */
    public function totalRows()
    {
        return $this->totalRows;
    }

    /**
     * Return the result of calling fgetcsv() ignoring empty lines.
     *
     * @return array|false|null
     */
    public function getRow()
    {
        do {
            $row = fgetcsv($this->fh, 0, $this->delimiter, self::ENCLOSURE, SELF::ESCAPE);
        } while ($row && $row[0] === null);

        return $row;
    }
}
