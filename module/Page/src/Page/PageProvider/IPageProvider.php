<?php
namespace Page\PageProvider;

interface IPageProvider
{
    /**
     * Get pages
     *
     * @param string $language
     * @return array
     *      boolean url_active
     *      string url_title
     *      array url_params
     *      array xml_map
     *          string lastmod
     *          string changefreq
     *          string priority
     *      array children
     */
    public function getPages($language);
}