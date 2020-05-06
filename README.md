# Stemmers used on the Commtap Symbols Server

## Purpose
The stemmers here are implementations of algorithms from https://snowballstem.org/algorithms/. 

The stemmers are designed to be used with applications which add picture communication symbols to text with user involvement.

These stemmers may stem more than the original stemmers, for example the implementation of the Porter Stemmer here has a look up table for irregular verbs - for example "ran" stems to "run". This is because if an end user has a picture symbol labelled "run" in their symbol set, but they type "ran" into their software, we would like that software to offer the picture symbol "run" as a candidate symbol - rather than nothing at all.

For the StemmerEnglishPorter2 class you can see the additional look up words in the stem_lookup_additional method.

## How to use

The stemmer classes are in src/classes.

```php
$stemmer = new StemmerEnglishPorter2();
echo $stemmer->stem('ran'); // run
echo $stemmer->stem('intention'); // intent
echo $stemmer->stem('cycles'); // cycl
```

## Testing

Tests for use with PHPUnit are in tests.


