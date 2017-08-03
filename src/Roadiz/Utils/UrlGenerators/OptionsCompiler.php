<?php
/**
 * Copyright (c) 2017. Ambroise Maupate and Julien Blanchet
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
 * @file OptionsCompiler.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 */

namespace RZ\Roadiz\Utils\UrlGenerators;

use RZ\Roadiz\Utils\Document\UrlOptionsResolver;

/**
 * Compile Intervention Request options into a single query string.
 *
 * @package RZ\Roadiz\Utils\UrlGenerators
 */
class OptionsCompiler
{
    /**
     * @var array
     */
    private $options;

    /**
     * Compile Intervention Request options into a single query string.
     *
     * @param array $options
     * @return string
     */
    public function compile($options)
    {
        $resolver = new UrlOptionsResolver();
        $this->options = $resolver->resolve($options);

        $shortOptions = [];

        if (null === $this->options['fit'] && $this->options['width'] > 0) {
            $shortOptions['w'] = 'w' . (int) $this->options['width'];
        }
        if (null === $this->options['fit'] && $this->options['height'] > 0) {
            $shortOptions['h'] = 'h' . (int) $this->options['height'];
        }
        if (null !== $this->options['crop']) {
            $shortOptions['c'] = 'c' . strip_tags($this->options['crop']);
        }
        if ($this->options['blur'] > 0) {
            $shortOptions['l'] = 'l' . ($this->options['blur']);
        }
        if (null !== $this->options['fit']) {
            $shortOptions['f'] = 'f' . strip_tags($this->options['fit']);
        }
        if ($this->options['rotate'] > 0) {
            $shortOptions['r'] = 'r' . ($this->options['rotate']);
        }
        if ($this->options['sharpen'] > 0) {
            $shortOptions['s'] = 's' . ($this->options['sharpen']);
        }
        if ($this->options['contrast'] > 0) {
            $shortOptions['k'] = 'k' . ($this->options['contrast']);
        }
        if ($this->options['grayscale']) {
            $shortOptions['g'] = 'g1';
        }
        if ($this->options['quality'] > 0) {
            $shortOptions['q'] = 'q' . $this->options['quality'];
        }
        if (null !== $this->options['background']) {
            $shortOptions['b'] = 'b' . strip_tags($this->options['background']);
        }
        if ($this->options['progressive']) {
            $shortOptions['p'] = 'p1';
        }
        if ($this->options['interlace']) {
            $shortOptions['i'] = 'i1';
        }

        return implode('-', $shortOptions);
    }
}