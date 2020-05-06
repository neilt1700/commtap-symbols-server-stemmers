<?php
/**
 *
 * Commtap CIC
 * https://symboliser.commtap.org
 *
 * This implementation of the Porter Stemmer derived from
 * https://github.com/markfullmer/porter2
 *
 */

class StemmerEnglishPorter2 extends Stemmer
{

  /**
   * Computes the stem of the word.
   *
   * @param string $word
   *
   * @return string
   *   The word's stem.
   */
  public function stem($word)
  {

    $word = strtolower($word);
    $word = $this->prepare($word);

    if (($special_word = $this->special_words($word)) <> "") {
      return $special_word;
    }

    // Words returned invariant:
    // NOTE: this causes some words to stem differently from the lists given at
    // https://snowballstem.org/algorithms/english/stemmer.html - use the
    // modified list to test list instead.
    if (($stop_word = $this->stop_words($word)) <> "") {
      return $stop_word;
    }

    if (($additional_word = $this->stem_lookup_additional($word)) <> "") {
      return $additional_word;
    }

    if (strlen($word) > 2) {
      $word = $this->add_y_consonants($word);
      $word = $this->step0($word);
      $word = $this->step1a($word);
      $word = $this->step1b($word);
      $word = $this->step1c($word);
      $word = $this->step2($word);
      $word = $this->step3($word);
      $word = $this->step4($word);
      $word = $this->step5($word);
    }
    return strtolower($word);
  }

  /**
   * Set initial y, or y after a vowel, to Y.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The prepared word.
   */
  protected function add_y_consonants($word)
  {
    $inc = 0;
    if (strpos($word, "'") === 0) {
      $word = substr($word, 1);
    }
    while ($inc <= strlen($word)) {
      if (substr($word, $inc, 1) === 'y' && ($inc == 0 || $this->isVowel($inc - 1, $word))) {
        $word = substr_replace($word, 'Y', $inc, 1);
      }
      $inc++;
    }
    return $word;
  }

  /**
   * Search for the longest among the "s" suffixes and removes it.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function step0($word)
  {
    $found = FALSE;
    $checks = array("'s'", "'s", "'");
    foreach ($checks as $check) {
      if (!$found && $this->str_ends_with($word, $check)) {
        $word = $this->str_replace_suffix($word, $check);
        $found = TRUE;
      }
    }
    return $word;
  }

  /**
   * Handles various suffixes, of which the longest is replaced.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function step1a($word)
  {
    $found = FALSE;
    if ($this->str_ends_with($word, 'sses')) {
      $word = $this->str_replace_suffix($word, 'sses') . 'ss';
      $found = TRUE;
    }
    $checks = array('ied', 'ies');
    foreach ($checks as $check) {
      if (!$found && $this->str_ends_with($word, $check)) {
        // @todo: check order here.
        $length = strlen($word);
        $word = $this->str_replace_suffix($word, $check);
        if ($length > 4) {
          $word .= 'i';
        }
        else {
          $word .= 'ie';
        }
        $found = TRUE;
      }
    }
    if ($this->str_ends_with($word, 'us') || $this->str_ends_with($word, 'ss')) {
      $found = TRUE;
    }
    // Delete if preceding word part has a vowel not immediately before the s.
    if (!$found && $this->str_ends_with($word, 's') && $this->containsVowel(substr($word, 0, -2))) {
      $word = $this->str_replace_suffix($word, 's');
    }
    return $word;
  }

  /**
   * Handles various suffixes, of which the longest is replaced.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function step1b($word)
  {
    $exceptions = array(
      'inning',
      'outing',
      'canning',
      'herring',
      'earring',
      'proceed',
      'exceed',
      'succeed',
    );
    if (in_array($word, $exceptions)) {
      return $word;
    }
    $checks = array('eedly', 'eed');
    foreach ($checks as $check) {
      if ($this->str_ends_with($word, $check)) {
        if ($this->r($word, 1) !== strlen($word)) {
          $word = $this->str_replace_suffix($word, $check) . 'ee';
        }
        return $word;
      }
    }
    $checks = array('ingly', 'edly', 'ing', 'ed');
    $second_endings = array('at', 'bl', 'iz');
    foreach ($checks as $check) {
      // If the ending is present and the previous part contains a vowel.
      if ($this->str_ends_with($word, $check) && $this->containsVowel(substr($word, 0, -strlen($check)))) {
        $word = $this->str_replace_suffix($word, $check);
        foreach ($second_endings as $ending) {
          if ($this->str_ends_with($word, $ending)) {
            return $word . 'e';
          }
        }
        // If the word ends with a double, remove the last letter.
        $double_removed = $this->removeDoubles($word);
        if ($double_removed != $word) {
          $word = $double_removed;
        }
        elseif ($this->isShort($word)) {
          // If the word is short, add e (so hop -> hope).
          $word .= 'e';
        }
        return $word;
      }
    }
    return $word;
  }

  /**
   * Replaces suffix y or Y with i if after non-vowel not @ word begin.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function step1c($word)
  {
    if (($this->str_ends_with($word, 'y') || $this->str_ends_with($word, 'Y')) && strlen($word) > 2 && !($this->isVowel(strlen($word) - 2, $word))) {
      $word = $this->str_replace_suffix($word, 'y');
      $word .= 'i';
    }
    return $word;
  }

  /**
   * Implements step 2 of the Porter2 algorithm.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function step2($word)
  {
    $checks = array(
      "ization" => "ize",
      "iveness" => "ive",
      "fulness" => "ful",
      "ational" => "ate",
      "ousness" => "ous",
      "biliti" => "ble",
      "tional" => "tion",
      "lessli" => "less",
      "fulli" => "ful",
      "entli" => "ent",
      "ation" => "ate",
      "aliti" => "al",
      "iviti" => "ive",
      "ousli" => "ous",
      "alism" => "al",
      "abli" => "able",
      "anci" => "ance",
      "alli" => "al",
      "izer" => "ize",
      "enci" => "ence",
      "ator" => "ate",
      "bli" => "ble",
      "ogi" => "og",
    );
    foreach ($checks as $find => $replace) {
      if ($this->str_ends_with($word, $find)) {
        if ($this->inR1($word, $find)) {
          $word = $this->str_replace_suffix($word, $find) . $replace;
        }
        return $word;
      }
    }
    if ($this->inR1($word, "li") && $this->str_ends_with($word, 'li')) {
      if (strlen($word) > 4 && $this->validLi($this->charAt(-3, $word))) {
        $word = $this->str_replace_suffix($word, 'li');
      }
    }
    return $word;
  }

  /**
   * Implements step 3 of the Porter2 algorithm.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function step3($word)
  {
    $checks = array(
      'ational' => 'ate',
      'tional' => 'tion',
      'alize' => 'al',
      'icate' => 'ic',
      'iciti' => 'ic',
      'ical' => 'ic',
      'ness' => '',
      'ful' => '',
    );
    foreach ($checks as $find => $replace) {
      if ($this->str_ends_with($word, $find)) {
        if ($this->inR1($word, $find)) {
          $word = $this->str_replace_suffix($word, $find) . $replace;
        }
        return $word;
      }
    }
    if ($this->str_ends_with($word, 'ative')) {
      if ($this->inR2($word, 'ative')) {
        $word = $this->str_replace_suffix($word, 'ative');
      }
    }
    return $word;
  }

  /**
   * Implements step 4 of the Porter2 algorithm.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function step4($word)
  {
    $checks = array(
      'ement',
      'ment',
      'ance',
      'ence',
      'able',
      'ible',
      'ant',
      'ent',
      'ion',
      'ism',
      'ate',
      'iti',
      'ous',
      'ive',
      'ize',
      'al',
      'er',
      'ic',
    );
    foreach ($checks as $check) {
      // Among the suffixes, if found and in R2, delete.
      if ($this->str_ends_with($word, $check)) {
        if ($this->inR2($word, $check)) {
          if ($check !== 'ion' || in_array($this->charAt(-4, $word), array('s', 't'))) {
            $word = $this->str_replace_suffix($word, $check);
          }
        }
        return $word;
      }
    }
    return $word;
  }

  /**
   * Implements step 5 of the Porter2 algorithm.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function step5($word)
  {
    if ($this->str_ends_with($word, 'e')) {
      // Delete if in R2, or in R1 and not preceded by a short syllable.
      if ($this->inR2($word, 'e') || ($this->inR1($word, 'e') && !$this->isShortSyllable($word, strlen($word) - 3))) {
        $word = $this->str_replace_suffix($word, 'e');
      }
      return $word;
    }
    if ($this->str_ends_with($word, 'l')) {
      // Delete if in R2 and preceded by l.
      if ($this->inR2($word, 'l') && $this->charAt(-2, $word) == 'l') {
        $word = $this->str_replace_suffix($word, 'l');
      }
    }
    return $word;
  }

  /**
   * Removes certain double consonants from the word's end.
   *
   * @param string $word
   *   The word to stem.
   *
   * @return string $word
   *   The modified word.
   */
  protected function removeDoubles($word)
  {
    $doubles = array('bb', 'dd', 'ff', 'gg', 'mm', 'nn', 'pp', 'rr', 'tt');
    foreach ($doubles as $double) {
      if (substr($word, -2) == $double) {
        $word = substr($word, 0, -1);
        break;
      }
    }
    return $word;
  }

  /**
   * Checks whether a character is a vowel.
   *
   * @param int $position
   *   The character's position.
   * @param string $word
   *   The word in which to check.
   * @param string[] $additional
   *   (optional) Additional characters that should count as vowels.
   *
   * @return bool
   *   TRUE if the character is a vowel, FALSE otherwise.
   */
  protected function isVowel($position, $word, array $additional = array())
  {
    $vowels = array_merge(array('a', 'e', 'i', 'o', 'u', 'y'), $additional);
    return in_array($this->charAt($position, $word), $vowels);
  }

  /**
   * Retrieves the character at the given position.
   *
   * @param int $position
   *   The 0-based index of the character. If a negative number is given, the
   *   position is counted from the end of the string.
   * @param string $word
   *   The word from which to retrieve the character.
   *
   * @return string
   *   The character at the given position, or an empty string if the given
   *   position was illegal.
   */
  protected function charAt($position, $word)
  {
    $length = strlen($word);
    if (abs($position) >= $length) {
      return '';
    }
    if ($position < 0) {
      $position += $length;
    }
    return $word[$position];
  }

  /**
   * Determines whether the word ends in a "vowel-consonant" suffix.
   *
   * Unless the word is only two characters long, it also checks that the
   * third-last character is neither "w", "x" nor "Y".
   *
   * @param string $word
   *
   * @param int|null $position
   *   (optional) If given, do not check the end of the word, but the character
   *   at the given position, and the next one.
   *
   * @return bool
   *   TRUE if the word has the described suffix, FALSE otherwise.
   */
  protected function isShortSyllable($word, $position = NULL)
  {
    if ($position === NULL) {
      $position = strlen($word) - 2;
    }
    // A vowel at the beginning of the word followed by a non-vowel.
    if ($position === 0) {
      return $this->isVowel(0, $word) && !$this->isVowel(1, $word);
    }
    // Vowel followed by non-vowel other than w, x, Y and preceded by
    // non-vowel.
    $additional = array('w', 'x', 'Y');
    return !$this->isVowel($position - 1, $word) && $this->isVowel($position, $word) && !$this->isVowel($position + 1, $word, $additional);
  }

  /**
   * Determines whether the word is short.
   *
   * A word is called short if it ends in a short syllable and if R1 is null.
   *
   * @param string $word
   *
   * @return bool
   *   TRUE if the word is short, FALSE otherwise.
   */
  protected function isShort($word)
  {
    return $this->isShortSyllable($word) && $this->r($word, 1) == strlen($word);
  }

  /**
   * Determines the start of a certain "R" region.
   *
   * R is a region after the first non-vowel following a vowel, or end of word.
   *
   * @param string $word
   *
   * @param int $type
   *   (optional) 1 or 2. If 2, then calculate the R after the R1.
   *
   * @return int
   *   The R position.
   */
  protected function r($word, $type = 1)
  {
    $inc = 1;
    if ($type === 2) {
      $inc = $this->r($word, 1);
    }
    elseif (strlen($word) > 5) {
      $prefix_5 = substr($word, 0, 5);
      if ($prefix_5 === 'gener' || $prefix_5 === 'arsen') {
        return 5;
      }
      if (strlen($word) > 5 && substr($word, 0, 6) === 'commun') {
        return 6;
      }
    }
    while ($inc <= strlen($word)) {
      if (!$this->isVowel($inc, $word) && $this->isVowel($inc - 1, $word)) {
        $position = $inc;
        break;
      }
      $inc++;
    }
    if (!isset($position)) {
      $position = strlen($word);
    }
    else {
      // We add one, as this is the position AFTER the first non-vowel.
      $position++;
    }
    return $position;
  }

  /**
   * Checks whether the given string is contained in R1.
   *
   * @param string $word
   *
   * @param string $string
   *   The string.
   *
   * @return bool
   *   TRUE if the string is in R1, FALSE otherwise.
   */
  protected function inR1($word, $string)
  {
    $r1 = substr($word, $this->r($word, 1));
    return strpos($r1, $string) !== FALSE;
  }

  /**
   * Checks whether the given string is contained in R2.
   *
   * @param string $word
   *
   * @param string $string
   *   The string.
   *
   * @return bool
   *   TRUE if the string is in R2, FALSE otherwise.
   */
  protected function inR2($word, $string)
  {
    $r2 = substr($word, $this->r($word, 2));
    return strpos($r2, $string) !== FALSE;
  }


  /**
   * Checks whether the given string contains a vowel.
   *
   * @param string $string
   *   The string to check.
   *
   * @return bool
   *   TRUE if the string contains a vowel, FALSE otherwise.
   */
  protected function containsVowel($string)
  {
    $inc = 0;
    $return = FALSE;
    while ($inc < strlen($string)) {
      if ($this->isVowel($inc, $string)) {
        $return = TRUE;
        break;
      }
      $inc++;
    }
    return $return;
  }

  /**
   * Checks whether the given string is a valid -li prefix.
   *
   * @param string $string
   *   The string to check.
   *
   * @return bool
   *   TRUE if the given string is a valid -li prefix, FALSE otherwise.
   */
  protected function validLi($string)
  {
    return in_array($string, array(
      'c',
      'd',
      'e',
      'g',
      'h',
      'k',
      'm',
      'n',
      'r',
      't',
    ));
  }

  // Direct stems:
  protected function special_words($word)
  {
    Switch ($word) {
      case "skis":
        return "ski";
      case "skies":
        return "sky";
      case "dying":
        return "die";
      case "lying":
        return "lie";
      case "tying":
        return "tie";
      case "idly":
        return "idl";
      case "gently":
        return "gentl";
      case "ugly":
        return "ugli";
      case "early":
        return "earli";
      case "only":
        return "onli";
      case "singly":
        return "singl";
      case "sky":
        return "sky";
      case "news":
        return "news";
      case "howe":
        return "howe";
      case "atlas":
        return "atlas";
      case "cosmos":
        return "cosmos";
      case "bias":
        return "bias";
      case "andes":
        return "andes";
      case "inning":
        return "inning";
      case "innings":
        return "inning";
      case "outing":
        return "outing";
      case "outings":
        return "outing";
      case "canning":
        return "canning";
      case "cannings":
        return "canning";
      case "herring":
        return "herring";
      case "herrings":
        return "herring";
      case "earring":
        return "earring";
      case "earrings":
        return "earring";
      case "proceed":
        return "proceed";
      case "proceeds":
        return "proceed";
      case "proceeded":
        return "proceed";
      case "proceeding":
        return "proceed";
      case "exceed":
        return "exceed";
      case "exceeds":
        return "exceed";
      case "exceeded":
        return "exceed";
      case "exceeding":
        return "exceed";
      case "succeed":
        return "succeed";
      case "succeeds":
        return "succeed";
      case "succeeded":
        return "succeed";
      case "succeeding":
        return "succeed";
    }

    return "";

  }

// Words returned invariant.
  protected function stop_words($word)
  {

    /* This is the original from nltk:
      stop_words = Array( _
      "this", "from", "wouldn", "then", "now", "has", "just", "while", "about", _
      "for", "how", "other", "ourselves", "himself", "d", "couldn't", "when", _
      "aren", "than", "if", "weren't", "hadn't", "our", "hers", "their", "you're", _
      "haven", "wouldn't", "hasn", "all", "y", "before", "didn", "own", "doesn't", _
      "what", "o", "t", "itself", "at", "through", "had", "they", "such", "during", _
      "who", "after", "into", "aren't", "hadn", "shan", "shouldn't", "here", _
      "each", "its", "or", "them", "herself", "but", "yours", "having", "is", _
      "down", "couldn", "needn", "which", "once", "ve", "it", "are", "you'll", _
      "don", "am", "to", "needn't", "mightn", "it's", "too", "off", "don't", _
      "more", "theirs", "hasn't", "shouldn", "i", "isn", "mightn't", "further", _
      "on", "me", "myself", "your", "ain", "him", "both", "isn't", "weren", _
      "above", "very", "some", "these", "won", "be", "why", "of", "been", "s", _
      "can", "that", "by", "most", "does", "m", "should've", "mustn", "you'd", _
      "yourselves", "won't", "as", "whom", "re", "over", "where", "mustn't", _
      "that'll", "was", "a", "up", "themselves", "doesn", "do", "in", "didn't", _
      "his", "she", "ours", "you", "so", "again", "should", "ma", "not", "out", _
      "under", "ll", "being", "there", "wasn't", "she's", "between", "only", _
      "with", "you've", "an", "my", "same", "few", "and", "did", "he", "will", _
      "because", "we", "doing", "any", "no", "her", "shan't", "until", "yourself", _
      "haven't", "the", "were", "those", "wasn", "have", "against", "below", "nor")
    */

    // Slightly reduced set - better for our purposes:
    $stop_words = array("this", "from", "wouldn", "then", "now", "has", "just", "while", "about", "for", "how", "other", "ourselves", "himself", "couldn't", "when", "aren", "than", "weren't", "hadn't", "haven", "wouldn't", "hasn", "all", "before", "didn", "own", "doesn't", "what", "itself", "through", "had", "such", "during", "who", "after", "into", "aren't", "hadn", "shan", "shouldn't", "here", "each", "its", "them", "herself", "but", "having", "down", "couldn", "needn", "which", "once", "don", "needn't", "mightn", "too", "off", "don't", "more", "hasn't", "shouldn", "isn", "mightn't", "further", "myself", "ain", "him", "both", "isn't", "weren", "above", "very", "some", "these", "won", "why", "been", "can", "that", "most", "does", "should've", "mustn", "yourselves", "won't", "whom", "over", "where", "mustn't", "that'll", "was", "themselves", "doesn", "didn't", "again", "should", "not", "out", "under", "being", "there", "wasn't", "between", "only", "with", "you've", "same", "few", "and", "did", "will", "because", "doing", "any", "shan't", "until", "yourself", "haven't", "the", "were", "those", "wasn", "have", "against", "below", "nor");

    if (in_array($word, $stop_words)) {
      return $word;
    }
    else {
      return "";
    }

  }

  /**
   *
   * Additional words to look up:
   * Includes irregular verbs which we want to stem (so that for example "saw"
   * matches "see").
   *
   * @param $word
   * @return string
   */
  protected function stem_lookup_additional($word)
  {

    switch ($word) {

      // Contractions
      case "i'm":
      case "i'll":
      case "i've":
      case "i'd":
        return "i";
      case "you're":
      case "you'll":
      case "you've":
      case "you'd":
        return "you";
      case "he's":
      case "he'll":
      case "he'd":
        return "he";
      case "she's":
      case "she'll":
      case "she'd":
        return "she";
      case "it's":
      case "it'll":
      case "it've":
      case "it'd":
        return "it";
      case "we're":
      case "we'll":
      case "we've":
      case "we'd":
        return "we";
      case "they're":
      case "they'll":
      case "they've":
      case "they'd":
        return "they";

      // Irregular verbs:
      case "arose":
      case "arisen":
        return "aris";
      case "awoke":
      case "awoken":
        return "awak";
      case "was":
      case "were":
      case "is":
      case "am":
      case "are":
      case "been":
        return "be";
      case "bore":
      case "born":
      case "borne":
        return "bear";
      case "beat":
      case "beaten":
        return "beat";
      case "became":
      case "become":
        return "becom";
      case "began":
      case "begun":
        return "begin";
      case "bent":
        return "bend";
      case "bet":
        return "bet";
      case "bound":
        return "bind";
      case "bit":
      case "bitten":
        return "bite";
      case "bled":
        return "bleed";
      case "blew":
      case "blown":
        return "blow";
      case "broke":
      case "broken":
        return "break";
      case "bred":
        return "breed";
      case "brought":
        return "bring";
      case "broadcast":
        return "broadcast";
      case "built":
        return "build";
      case "burnt":
      case "burned":
        return "burn";
      case "burst":
        return "burst";
      case "bought":
        return "buy";
      case "bus":
        return "bus";
      case "buses":
        return "bus";
      case "could":
        return "can";
      case "caught":
        return "catch";
      case "chose":
      case "chosen":
        return "choos";
      case "clung":
        return "cling";
      case "came":
      case "come":
        return "come";
      case "cost":
        return "cost";
      case "crept":
        return "creep";
      case "cut":
        return "cut";
      case "dealt":
        return "deal";
      case "dug":
        return "dig";
      case "did":
      case "done":
        return "do";
      case "drew":
      case "drawn":
        return "draw";
      case "dreamt":
      case "dreamed":
        return "dream";
      case "drank":
      case "drunk":
        return "drink";
      case "drove":
      case "driven":
        return "drive";
      case "ate":
      case "eaten":
        return "eat";
      case "fell":
      case "fallen":
        return "fall";
      case "fed":
        return "feed";
      case "felt":
        return "feel";
      case "fought":
        return "fight";
      case "found":
        return "find";
      case "flew":
      case "flown":
        return "fly";
      case "flung":
        return "fling";
      case "forbade":
      case "forbidden":
        return "forbid";
      case "forgot":
      case "forgotten":
        return "forget";
      case "forgave":
      case "forgiven":
        return "forgiv";
      case "froze":
      case "frozen":
        return "freez";
      case "got":
        return "get";
      case "gave":
      case "given":
        return "give";
      case "went":
      case "gone":
        return "go";
      case "ground":
        return "grind";
      case "grew":
      case "grown":
        return "grow";
      case "hung":
        return "hang";
      case "had":
        return "have";
      case "heard":
        return "hear";
      case "hid":
      case "hidden":
        return "hide";
      case "hit":
        return "hit";
      case "held":
        return "hold";
      case "hurt":
        return "hurt";
      case "kept":
        return "keep";
      case "knelt":
        return "kneel";
      case "knew":
      case "known":
        return "know";
      case "laid":
        return "lie";
      case "led":
        return "lead";
      case "leant":
      case "leaned":
        return "lean";
      case "learnt":
      case "learned":
        return "learn";
      case "left":
        return "leav";
      case "lent":
        return "lent";
      case "lay":
      case "lain":
        return "lie";
      case "lied":
        return "lie";
      case "lit":
      case "lighted":
        return "light";
      case "lost":
        return "lose";
      case "made":
        return "make";
      case "might":
        return "may";
      case "meant":
        return "mean";
      case "met":
        return "meet";
      case "mowed":
      case "mown":
        return "mow";
      case "had to":
      case "had-to":
        return "must";
      case "overtook":
      case "overtaken":
        return "overtak";
      case "paid":
        return "pay";
      case "paste":
      case "pasted":
      case "pastes":
        return "paste";
      case "put":
        return "put";
      case "read":
        return "read";
      case "rode":
      case "ridden":
        return "ride";
      case "rang":
      case "rung":
        return "ring";
      case "rose":
      case "risen":
        return "rise";
      case "ran":
      case "run":
        return "run";
      case "sawed":
      case "sawn":
        return "saw";
      case "said":
        return "say";
      case "saw":
      case "seen":
        return "see";
      case "sold":
        return "sell";
      case "sent":
        return "send";
      case "set":
        return "set";
      case "sewed":
      case "sewn":
        return "sew";
      case "shat":
        return "shit";
      case "shook":
      case "shaken":
        return "shake";
      case "should":
        return "shall";
      case "shed":
        return "shed";
      case "shone":
        return "shine";
      case "shot":
        return "shoot";
      case "showed":
      case "shown":
        return "show";
      case "shrank":
      case "shrunk":
        return "shrink";
      case "shut":
        return "shut";
      case "sang":
      case "sung":
        return "sing";
      case "sank":
      case "sunk":
        return "sink";
      case "sat":
        return "sit";
      case "slept":
        return "sleep";
      case "slid":
        return "slide";
      case "smelt":
        return "smell";
      case "sowed":
      case "sown":
        return "sow";
      case "spoke":
      case "spoken":
        return "speak";
      case "spelt":
      case "spelled":
        return "spell";
      case "spent":
        return "spend";
      case "spilt":
      case "spilled":
        return "spill";
      case "spat":
        return "spit";
      case "spread":
        return "spread";
      case "stood":
        return "stand";
      case "stole":
      case "stolen":
        return "steal";
      case "stuck":
        return "stick";
      case "stung":
        return "sting";
      case "stank":
      case "stunk":
        return "stink";
      case "struck":
        return "strike";
      case "swore":
      case "sworn":
        return "swear";
      case "swept":
        return "sweep";
      case "swelled":
      case "swollen":
        return "swell";
      case "swam":
      case "swum":
        return "swim";
      case "swung":
        return "swing";
      case "took":
      case "taken":
        return "take";
      case "taught":
        return "teach";
      case "tore":
      case "torn":
        return "tear";
      case "told":
        return "tell";
      case "thought":
        return "think";
      case "threw":
      case "thrown":
        return "throw";
      case "trod":
        return "tread";
      case "understood":
        return "understand";
      case "woke":
      case "woken":
        return "wake";
      case "wore":
      case "worn":
        return "wear";
      case "wept":
        return "weep";
      case "would":
        return "will";
      case "won":
        return "win";
      case "wound":
        return "wind";
      case "wrote":
      case "written":
        return "write";

      // Additional
      case "add":
      case "adding":
      case "added":
      case "adds":
        return "add";
    }

    return "";

  }
}
