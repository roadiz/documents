<?php
/**
 * Copyright (c) 2016. Ambroise Maupate and Julien Blanchet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * Except as contained in this notice, the name of the ROADIZ shall not
 * be used in advertising or otherwise to promote the sale, use or other dealings
 * in this Software without prior written authorization from Ambroise Maupate and Julien Blanchet.
 *
 * @file ViewOptionsResolver.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\Document;

/**
 * Class ViewOptionsResolver
 * @package RZ\Roadiz\Utils\Document
 */
class ViewOptionsResolver extends UrlOptionsResolver
{
    public function __construct()
    {
        parent::__construct();

        $this->setDefaults([
            'identifier' => null,
            'id' => null,
            'class' => null,
            'alt' => null,
            'title' => null,
            'custom_poster' => null,
            'embed' => false,
            'lazyload' => false,
            'lazyload_class' => 'lazyload',
            'inline' => true,
            'autoplay' => false,
            'muted' => false,
            'loop' => false,
            'controls' => true,
            'fullscreen' => true,
            'srcset' => [],
            'sizes' => [],
            'picture' => false,
            /*
             * Soundcloud
             */
            'hide_related' => false,
            'show_comments' => false,
            'show_user' => false,
            'show_reposts' => false,
            'show_artwork' => false,
            'visual' => false,
            /*
             * Vimeo
             */
            'displayTitle' => false,
            'byline' => false,
            'portrait' => false,
            'color' => null,
            'api' => true,
            'automute' => false,
            'autopause' => false,
            /*
             * Youtube
             */
            'modestbranding' => true,
            'rel' => false,
            'showinfo' => false,
            'start' => false,
            'end' => false,
            'enablejsapi' => true,
            'playlist' => false,
            /*
             * Mixcloud
             */
            'mini' => false,
            'light' => true,
            'hide_cover' => true,
            'hide_artwork' => false,
        ]);

        $this->setAllowedTypes('identifier', ['null', 'string']);
        $this->setAllowedTypes('id', ['null', 'string']);
        $this->setAllowedTypes('class', ['null', 'string']);
        $this->setAllowedTypes('alt', ['null', 'string']);
        $this->setAllowedTypes('title', ['null', 'string']);
        $this->setAllowedTypes('custom_poster', ['null', 'string']);
        $this->setAllowedTypes('embed', ['boolean']);
        $this->setAllowedTypes('lazyload', ['boolean']);
        $this->setAllowedTypes('lazyload_class', ['string']);
        $this->setAllowedTypes('inline', ['boolean']);
        $this->setAllowedTypes('autoplay', ['boolean']);
        $this->setAllowedTypes('muted', ['boolean']);
        $this->setAllowedTypes('loop', ['boolean']);
        $this->setAllowedTypes('controls', ['boolean']);
        $this->setAllowedTypes('fullscreen', ['boolean']);
        $this->setAllowedTypes('srcset', ['array']);
        $this->setAllowedTypes('sizes', ['array']);
        $this->setAllowedTypes('picture', ['boolean']);

        // Soundcloud
        $this->setAllowedTypes('hide_related', ['boolean']);
        $this->setAllowedTypes('show_comments', ['boolean']);
        $this->setAllowedTypes('show_user', ['boolean']);
        $this->setAllowedTypes('show_reposts', ['boolean']);
        $this->setAllowedTypes('show_artwork', ['boolean']);
        $this->setAllowedTypes('visual', ['boolean']);

        // Vimeo
        $this->setAllowedTypes('displayTitle', ['boolean']);
        $this->setAllowedTypes('byline', ['boolean']);
        $this->setAllowedTypes('portrait', ['boolean']);
        $this->setAllowedTypes('automute', ['boolean']);
        $this->setAllowedTypes('autopause', ['boolean']);
        $this->setAllowedTypes('color', ['null', 'string']);
        $this->setAllowedTypes('api', ['boolean']);

        // Youtube
        $this->setAllowedTypes('modestbranding', ['boolean']);
        $this->setAllowedTypes('rel', ['boolean']);
        $this->setAllowedTypes('showinfo', ['boolean']);
        $this->setAllowedTypes('start', ['boolean', 'integer']);
        $this->setAllowedTypes('end', ['boolean', 'integer']);
        $this->setAllowedTypes('enablejsapi', ['boolean']);

        /*
         * Mixcloud
         */
        $this->setAllowedTypes('mini', ['boolean']);
        $this->setAllowedTypes('light', ['boolean']);
        $this->setAllowedTypes('hide_cover', ['boolean']);
        $this->setAllowedTypes('hide_artwork', ['boolean']);
    }
}
