<?php

namespace Application\Utility;

use Transliterator;

class Slug
{
    /**
     * Slugify title
     * 
     * @param string $title
     * @param integer $objectId
     * @param integer $maxChars
     * @param string $spaceDevider
     * @return string
     */
    public static function slugify($title, $objectId = 0, $maxChars = 50, $spaceDevider = '_')
    {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower();');
        $title = preg_replace('/[-\s]+/', $spaceDevider, $transliterator->transliterate($title));
        $slug = $objectId ? $objectId . $spaceDevider . $title : $title;

        return strlen($slug) > $maxChars
            ? substr($slug, 0, $maxChars)
            : $slug;
    }
}