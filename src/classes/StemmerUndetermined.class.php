<?php
/**
 * Commtap CIC
 * https://symboliser.commtap.org
 * 
 */

class StemmerUndetermined extends Stemmer
{
  /**
   * @param string $word
   * @return string
   */
  public function stem($word) {
    $word = strtolower($word);
    $word = $this->prepare($word);
    return $word;
  }
}
