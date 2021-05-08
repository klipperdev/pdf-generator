<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Component\PdfGenerator\Generator;

use Klipper\Component\PdfGenerator\Exception\RuntimeException;
use Klipper\Component\PdfGenerator\GeneratorInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ChromeGenerator implements GeneratorInterface
{
    public const DEFAULT_OPTIONS = [
        'headless' => null,
        'incognito' => null,
        'mute-audio' => null,
        'no-first-run' => null,
        'no-margins' => null,
        'enable-viewport' => null,
        'disable-gpu' => null,
        'disable-translate' => null,
        'disable-extensions' => null,
        'disable-sync' => null,
        'disable-default-apps' => null,
        'hide-scrollbars' => null,
    ];

    private string $binaryPath;

    private string $tempDirectory;

    private array $options = [];

    private ?bool $validated = null;

    public function __construct(string $binaryPath, ?string $tempDirectory = null, array $defaultOptions = [])
    {
        $this->binaryPath = $binaryPath;
        $this->tempDirectory = $tempDirectory ?? sys_get_temp_dir();
        $this->setOptions(static::DEFAULT_OPTIONS);
        $this->setOptions($defaultOptions);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        foreach ($options as $option => $value) {
            $this->setOption($option, $value);
        }

        return $this;
    }

    public function setOption(string $name, ?string $value): self
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function generate(string $originPath, ?string $targetPath = null, array $options = []): \SplFileInfo
    {
        $this->validateBinary();
        $targetPath = $targetPath ?: $this->tempDirectory.'/'.$this->getUniqueFilename();

        if (!strstr($targetPath, '.pdf')) {
            $targetPath .= '.pdf';
        }

        $process = new Process(array_merge(
            [$this->binaryPath],
            $this->builtOptions(array_merge(
                $this->options,
                [
                    'print-to-pdf-no-header' => null,
                    'print-to-pdf' => $targetPath,
                ],
                $options
            )),
            [$originPath]
        ));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                'Some error occurred while generating PDF',
                0,
                new ProcessFailedException($process)
            );
        }

        return new \SplFileInfo($targetPath);
    }

    /**
     * @throws ProcessFailedException
     */
    private function validateBinary(): void
    {
        if (null === $this->validated) {
            $process = new Process(array_merge(
                [$this->binaryPath],
                $this->builtOptions(array_merge(
                    $this->options,
                    ['version'],
                ))
            ));
            $process->run();

            if (!($this->validated = $process->isSuccessful())) {
                throw new RuntimeException(
                    'The Google Chrome binary is invalid',
                    0,
                    new ProcessFailedException($process)
                );
            }
        }
    }

    private function builtOptions(array $options): array
    {
        $finalOptions = [];

        foreach ($options as $option => $value) {
            if (null !== $value && !empty($value)) {
                $option .= '='.$value;
            }

            $finalOptions[] = '--'.$option;
        }

        return $finalOptions;
    }

    private function getUniqueFilename(): string
    {
        do {
            $filename = md5(time().mt_rand()).'.pdf';
        } while (!$this->isUniqueFilename($filename));

        return $filename;
    }

    private function isUniqueFilename(string $filename): bool
    {
        return !file_exists($this->tempDirectory.'/'.$filename);
    }
}
