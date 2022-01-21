<?php

namespace Commercepundit\Gallery\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Get GalleryManagerInterface
 *
 * @api
 */
interface GalleryManagerInterface
{
    /**
     * Returns Get Gallery Images
     * @return string[]
     */
    public function getGalleryImages();
}
