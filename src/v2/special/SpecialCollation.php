<?php

namespace sinri\ark\database\sqlite\v2\special;
/**
 * @since 2.0
 */
interface SpecialCollation
{
    /**
     * Name of the SQL collating function to be created or redefined.
     * @return string
     */
    public function getName():string;

    /**
     * The name of a PHP function or user-defined function to apply as a callback,
     *  defining the behavior of the collation.
     * It should accept two values and return as strcmp() does,
     *  i.e. it should return -1, 1, or 0
     *  if the first string sorts before, sorts after, or is equal to the second.
     * @param $first
     * @param $second
     * @return int `-1`: first sorted before second; `0`: first sorted equal second; `1`: first sorted after second;
     */
    public function comparator($first,$second):int;
}