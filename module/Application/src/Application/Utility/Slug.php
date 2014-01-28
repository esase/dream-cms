<?php

namespace Application\Utility;

use Transliterator;

class Slug
{
    /**
     * Slugify title
     * 
     * @param string $title
     * @param integer $maxChars
     * @param string $spaceDevider
     * @param integer $objectId
     * @return string
     */
    public static function slugify($title, $maxChars = 100, $spaceDevider = '-', $objectId = 0)
    {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower();');
        $title = preg_replace('/[^0-9a-z\s]/i', '', $transliterator->transliterate($title));
        $title = str_replace(' ', $spaceDevider, $title);

        $slug = $objectId ? $objectId . $spaceDevider . $title : $title;

        return strlen($slug) > $maxChars
            ? substr($slug, 0, $maxChars)
            : $slug;
    }
}