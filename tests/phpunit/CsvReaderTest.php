<?php

require __DIR__ . '/../../public_html/lists/admin/CsvReader.php';

class CsvReaderTest extends PHPUnit\Framework\TestCase
{
    private static $temporaryFiles = [];

    private function createTestFile($data)
    {
        $filename = tempnam(sys_get_temp_dir(), 'phpunit_');
        file_put_contents($filename, $data);
        self::$temporaryFiles[] = $filename;

        return $filename;
    }

    /**
     * @test
     * @dataProvider lineEndingDataProvider
     */
    public function lineEnding($filename, $expected)
    {
        $csv = new csvReader($filename, ',');
        $this->assertEquals($expected, $csv->totalRows());
    }

    public function lineEndingDataProvider()
    {
        $dataForTests = [
            'line-ending CRNL' => ["email,name,country\r\nfoo@foo.com,Jim Smith,United Kingdom\r\n", 2],
            'line-ending NL' => ["email,name,country\nfoo@foo.com,Jim Smith,United Kingdom\n", 2],
            'line-ending CR' => ["email,name,country\rfoo@foo.com,Jim Smith,United Kingdom\r", 2],
        ];

        return array_map(
            function ($item) {
                $data = $item[0];
                $expected = $item[1];
                $filename = $this->createTestFile($data);

                return [$filename, $expected];
            },
            $dataForTests
        );
    }

    /**
     * @test
     */
    public function embeddedEnclosure()
    {
        $le = "\n";
        $data = '"email","name","description"' . $le
            . '"foo@foo.com","Jim Smith","before "" after"' . $le
            . '"foo2@foo.com","John Brown","a description"' . $le;
        $filename = $this->createTestFile($data);

        $csv = new csvReader($filename, ',');
        $this->assertEquals(3, $csv->totalRows());

        $headers = $csv->getRow();
        $fields = $csv->getRow();
        $this->assertEquals('before " after', $fields[2]);
    }

    /**
     * @test
     */
    public function embeddedDelimiter()
    {
        $le = "\n";
        $data = '"email","name","description"' . $le
            . '"foo@foo.com","Jim Smith","before , after"' . $le
            . '"foo2@foo.com","John Brown","a description"' . $le;
        $filename = $this->createTestFile($data);

        $csv = new csvReader($filename, ',');
        $this->assertEquals(3, $csv->totalRows());

        $headers = $csv->getRow();
        $fields = $csv->getRow();
        $this->assertEquals('before , after', $fields[2]);
    }

    /**
     * @test
     * @dataProvider embeddedLineEndingDataProvider
     */
    public function embeddedLineEnding($le)
    {
        $data = '"email","name","description"' . $le
            . '"foo2@foo.com","John Brown","a description"' . $le
            . sprintf('"foo@foo.com","Jim Smith","%s"', "before $le after") . $le;
        $filename = $this->createTestFile($data);

        $csv = new csvReader($filename, ',');
        $this->assertEquals(3, $csv->totalRows());

        $headers = $csv->getRow();
        $csv->getRow();
        $fields = $csv->getRow();
        $this->assertEquals("before $le after", $fields[2]);
    }

    public function embeddedLineEndingDataProvider()
    {
        return [
            'embedded NL' => ["\n"],
            'embedded CRNL' => ["\r\n"],
            'embedded CR' => ["\r"],
        ];
    }

    /**
     * @test
     */
    public function embeddedBackslash()
    {
        $le = "\n";
        $data = '"email","name","description"' . $le
            . '"foo@foo.com","Jim Smith","before \"" after"' . $le
            . '"foo2@foo.com","John Brown","at end\"' . $le;
        $filename = $this->createTestFile($data);

        $csv = new csvReader($filename, ',');
        $this->assertEquals(3, $csv->totalRows());

        $headers = $csv->getRow();
        $fields = $csv->getRow();
        $this->assertEquals('before \" after', $fields[2]);
        $fields = $csv->getRow();
        $this->assertEquals('at end\\', $fields[2]);
    }

    public static function tearDownAfterClass() : void
    {
        array_walk(
            self::$temporaryFiles,
            function ($item) {
                unlink($item);
            }
        );
    }
}
