<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\PdfGenerator;

use Klipper\Component\PdfGenerator\Exception\RuntimeException;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface PdfInterface
{
    /**
     * @throws RuntimeException When an error occurred
     */
    public function generate(string $originPath, ?string $targetPath = null, array $options = []): \SplFileInfo;

    /**
     * @throws RuntimeException When an error occurred
     */
    public function generateFromContent(string $content, ?string $targetPath = null, array $options = []): \SplFileInfo;
}
