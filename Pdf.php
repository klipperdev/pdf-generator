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

use Symfony\Component\Filesystem\Filesystem;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Pdf implements PdfInterface
{
    private GeneratorInterface $generator;

    private Filesystem $filesystem;

    private string $tempDirectory;

    public function __construct(
        GeneratorInterface $generator,
        ?string $tempDirectory = null,
        ?Filesystem $filesystem = null
    ) {
        $this->generator = $generator;
        $this->tempDirectory = $tempDirectory ?? sys_get_temp_dir();
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function generate(string $originPath, ?string $targetPath = null, array $options = []): \SplFileInfo
    {
        return $this->generator->generate($originPath, $targetPath, $options);
    }

    /**
     * @throws
     */
    public function generateFromContent(string $content, ?string $targetPath = null, array $options = []): \SplFileInfo
    {
        $filename = $this->getUniqueFilename();

        try {
            $this->filesystem->dumpFile($filename, $content);
            $pdfFile = $this->generate($filename, $targetPath, $options);
            $this->filesystem->remove($filename);
        } catch (\Throwable $e) {
            $this->filesystem->remove($filename);

            throw $e;
        }

        return $pdfFile;
    }

    private function getUniqueFilename(): string
    {
        do {
            $filename = md5(time().mt_rand()).'.html';
        } while (!$this->isUniqueName($filename));

        return $this->tempDirectory.'/'.$filename;
    }

    private function isUniqueName(string $filename): bool
    {
        return !$this->filesystem->exists($this->tempDirectory.'/'.$filename);
    }
}
