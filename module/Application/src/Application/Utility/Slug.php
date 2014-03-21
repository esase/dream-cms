<?php

namespace Application\Utility;

use Transliterator;
use Zend\Math\Rand;

class Slug
{
    /**
     * Slugify a title
     * 
     * @param string $title
     * @param integer $maxChars
     * @param string $spaceDevider
     * @param integer $objectId
     * @param string $pattern
     * @return string
     */
    public static function slugify($title, $maxChars = 100, $spaceDevider = '-', $objectId = 0, $pattern = '0-9a-z\s')
    {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower();');
        $title = preg_replace('/[^' . $pattern. ']/i', '', $transliterator->transliterate($title));
        $title = str_replace(' ', $spaceDevider, $title);

        $slug = $objectId ? $objectId . $spaceDevider . $title : $title;

        return strlen($slug) > $maxChars
            ? substr($slug, 0, $maxChars)
            : $slug;
    }

    /**
     * Generate a random slug
     *
     * @param integer $slugLength
     * @param string $slugChars
     * @return string
     */
    public static function generateRandomSlug($slugLength = 10, $slugChars = 'abcdefghijklmnopqrstuvwxyz')
    {
        return Rand::getString($slugLength, $slugChars, true);
    }
}