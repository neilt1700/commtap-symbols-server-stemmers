<?php
/**
 * Commtap CIC
 * https://symboliser.commtap.org
 */

abstract class Stemmer
{

  protected function longest_suffix($word, $suffixes) {
    if (strlen($word) > 0) {
      $this->sc_array_sort_by_longest($suffixes);
      foreach ($suffixes as $suffix) {
        if ($this->str_ends_with($word, $suffix)) {
          return $suffix;
        }
      }
    }
    return "";
  }

  protected function prepare($word) {
    return $this->smart_quote_replace($word);
  }

  protected function smart_quote_replace($string) {
    return str_replace(array(chr(145), chr(146), '“', '”', chr(147), chr(148)), array("'", "'", '"', '"', '"', '"'), $string);
  }

  /**
   *
   * Definition:
   * R1 is the region after the first non-vowel following a vowel, or is the
   * null region at the end of the word if there is no such non-vowel.
   * R2 is defined in the same way except it is derived from R1 and not the
   * whole word. See: https://snowballstem.org/texts/r1r2.html
   *
   * @param $string
   * @param $vowels
   * @return bool|string
   *
   * See Porter2 English Stemmer for a better way of defining this.
   *
   */
  protected function r1_r2($string, $vowels) {

    $foundFirstVowel = false;
    $subStrings = str_split($string);

    for ($i = 0; $i < count($subStrings); $i++) {
      if ($foundFirstVowel) {
        if (!in_array($subStrings[$i], $vowels)) {
          return substr($string, $i + 1);
        }
      }
      if (in_array($subStrings[$i], $vowels)) {
        $foundFirstVowel = true;
      }
    }
    return "";
  }

  /**
   *
   * Varies from language to language, and does not need to be defined in all
   * languages.
   *
   * @param $word
   * @return string
   */
  protected function rv($word) {
    return $word;
  }

  /**
   * All stem classes must provide this function.
   *
   * @param $word string
   * @return string
   */
  abstract protected function stem($word);

  protected function vowels() {
    return array();
  }

  protected function consonants() {
    return array();
  }

  // Array functions
  protected function sc_array_sort_by_longest(&$array) {
    array_multisort(array_map('strlen', $array), SORT_DESC, $array);
  }

// String functions
  /**
   * @param string $haystack
   * @param $needle string
   * @return bool
   */
  protected function str_ends_with($haystack, $needle) {
    if (strLen($needle) > strLen($haystack)) {
      return false;
    }
    elseif (substr($haystack, strLen($haystack) - strLen($needle)) == $needle) {
      return true;
    }
    else {
      return false;
    }
  }

  protected function str_starts_with($haystack, $needle) {

    if (strLen($needle) > strLen($haystack)) {
      return false;
    }
    elseif (substr($haystack, 0, strLen($needle)) == $needle) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   *
   * Replaces oldPrefix with newPrefix.
   * If newPrefix is not provided, oldPrefix is deleted.
   *
   * @param string $subject
   * @param string $oldPrefix
   * @param string $newPrefix
   * @return string
   */
  protected function str_replace_prefix($subject, $oldPrefix, $newPrefix = "") {
    if (strpos($subject, $oldPrefix) === 0) {
      return $newPrefix . substr($subject, strlen($oldPrefix));
    }
    return $subject;
  }


  /**
   *
   * Replaces oldSuffix with newSuffix.
   * If newSuffix is not provided, oldSuffix is deleted.
   *
   * @param string $subject
   * @param string $oldSuffix
   * @param string $newSuffix
   * @return string
   */
  protected function str_replace_suffix($subject, $oldSuffix, $newSuffix = "") {
    if (substr($subject, strlen($subject) - strlen($oldSuffix)) == $oldSuffix) {
      return substr($subject, 0, strlen($subject) - strlen($oldSuffix)) . $newSuffix;
    }
    return $subject;
  }

}
