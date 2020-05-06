<?php


class StemmerEnglishPorter2Test extends PHPUnit_Framework_TestCase
//class StemmerEnglishPorter2Test
{

  public static function setUpBeforeClass()
  {
    require_once 'include.php';
  }


  /**
   *
   * @dataProvider stemmerProducesExpectedStemProvider
   *
   * @param $word
   * @param $expected_stem
   *
   */
  public function testStemmerProducesExpectedStems($word, $expected_stem)
  {

    $objStemmer = New StemmerEnglishPorter2();

    $this->assertEquals($expected_stem, $stem = $objStemmer->stem($word), "Source word: " . $word);

  }

  public function stemmerProducesExpectedStemProvider() {

    require_once 'utility.php';

    $arrStemData = array();
    $file_name = csTestSettings()['stem-data-path'] . 'EnglishPorter2Stems_modified.csv';

    // Note, the input data is in a file with UTF-16 LE format. Internally, php
    // uses UTF-8 - and fgetcsv does not work with UTF-16 input.
    $handle = fopen_utf8($file_name);
    if ($handle) {
      while (($buffer = fgets($handle, 4096)) !== false) {
        $data = str_getcsv($buffer);
        if (substr(trim($data[0]), 0, 1) <> ";") {
          // Lines starting with ";" are comments.
          $arrStemData[] = array($data[0], $data[1]);
        }
      }
      fclose($handle);
    }

    return $arrStemData;
  }

}
