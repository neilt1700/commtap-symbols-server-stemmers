<?php

function csTestSettings() {
  $root = dirname(dirname(__FILE__));
  return array(
    // Path to test data directory:
    'stem-data-path' => $root . '/test-data/stem-data/',
  );
}

// http://www.practicalweb.co.uk/blog/2008/05/18/reading-a-unicode-excel-file-in-php/
// Opens a unicode text file saved in UTF-16LE and other formats for reading.
function fopen_utf8($filename){

  $handle = fopen($filename, 'r');
  $bom = fread($handle, 2);

  rewind($handle);

  if($bom === chr(0xff).chr(0xfe)  || $bom === chr(0xfe).chr(0xff)){
    // UTF16 Byte Order Mark present
    $encoding = 'UTF-16';
  } else {
    $file_sample = fread($handle, 1000) . 'e'; //read first 1000 bytes
    // + e is a workaround for mb_string bug
    rewind($handle);

    $encoding = mb_detect_encoding($file_sample,'UTF-8, UTF-7, ASCII, EUC-JP, SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP');
  }
  if ($encoding){
    stream_filter_append($handle, 'convert.iconv.' . $encoding . '/UTF-8');
  }
  return $handle;
}

