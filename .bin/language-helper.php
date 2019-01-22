<?php

namespace Wirecard;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * This class creates files from template files by substituting control
 * structures and template tags for language information. Template files must be
 * named [filename].template.[extension]
 *
 * EXAMPLE
 *
 * Print a string in the default language:
 * {{ strings.plugin_name }}
 *
 * Print a string in a specific language:
 * {{ strings.plugin_name | de_DE }}
 *
 * Print the language tag in the default language:
 * {{ lang }}
 *
 * Print the language locale in the default language:
 * {{ locale }}
 *
 * Print content for each available language:
 * @forlang
 * <label lang="{{ lang }}">{{ strings.plugin_name }}</label>
 * @endforlang
 */
class LanguageHelper
{
    public const DEFAULT_LOCALE = 'en_US';
    public const TEMPLATE_SUFFIX = 'template';
    public const TEMPLATE_FOLDERS = [
        './Commands',
        './Components',
        './Controllers',
        './Exception',
        './Models',
        './Resources',
        './Subscriber',
    ];
    public const LANGUAGES_FOLDER = './Resources/languages';
    public const STATUS_CODES = [
        0 => 'SUCCESS',
        1 => 'WARNING',
        2 => 'ERROR',
    ];

    private $verbose;
    private $templateFiles;
    private $languageFiles;
    private $languageData;

    public function __construct($verbose = false)
    {
        $this->verbose = $verbose;
        $this->templateFiles = $this->getTemplateFiles();
        $this->languageFiles = glob(self::LANGUAGES_FOLDER . '/*.json');
        $this->languageData = [];

        // gather language data
        if ($this->languageFiles) {
            foreach ($this->languageFiles as $languageFile) {
                $locale = basename($languageFile, '.json');

                $this->languageData[$locale] = $this->flattenArrayPaths([
                    'locale' => $locale,
                    'lang' => explode('_', $locale)[0],
                    'strings' => json_decode(file_get_contents($languageFile), true) ?? [],
                ]);
            }
        }

        if (!$this->languageData) {
            $this->log('Could not find any language files in ' . self::LANGUAGES_FOLDER, 2);
        }
    }

    /**
     * Returns an array of template files within the template folders.
     *
     * @return array
     */
    private function getTemplateFiles(): array
    {
        $templateFiles = [];

        foreach (self::TEMPLATE_FOLDERS as $templateFolder) {
            $filesInFolder = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templateFolder));

            foreach ($filesInFolder as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                // filter templates
                $fileName = $file->getBasename(".{$file->getExtension()}");

                if (substr($fileName, -strlen(self::TEMPLATE_SUFFIX)) === self::TEMPLATE_SUFFIX) {
                    $templateFiles[] = $file->getPathname();
                }
            }
        }

        return $templateFiles;
    }

    /**
     * Flattens a multidimensional array to a single key.
     *
     * @param array $array
     * @param string $separator
     * @param array $path
     * @return array
     * @example
     * [
     *     'a' => [
     *         'b' => [
     *             'c' => 'foo',
     *         ],
     *         'd' => 'bar',
     *     ],
     * ]
     * ↓
     * [
     *     'a.b.c' => 'foo',
     *     'a.d' => 'bar',
     * ]
     */
    private function flattenArrayPaths(array $array, string $separator = '.', array $path = []): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            $outputPath = $path;
            $outputPath[] = $key;

            if (is_array($value)) {
                $output += $this->flattenArrayPaths($value, $separator, $outputPath);
            } else {
                $output[implode($separator, $outputPath)] = $value;
            }
        }

        return $output;
    }

    /**
     * Logs a message if the verbose setting is enabled.
     *
     * @param string $text
     * @param int $statusCode
     * @return bool
     */
    private function log(string $text, ?int $statusCode = null): bool
    {
        if (!$this->verbose) {
            return false;
        }

        $statusCodeName = self::STATUS_CODES[$statusCode] ?? null;

        echo $statusCodeName ? "{$statusCodeName}: {$text}\n" : "{$text}\n";

        return true;
    }

    /**
     * Replaces variables inside template tags in a text.
     *
     * @param string $text
     * @param string $locale Note that a locale defined in a template tag will
     * override this setting, e.g. {{ strings.test | de_DE }}
     * @return string
     */
    private function replaceVariables(string $text, string $locale = self::DEFAULT_LOCALE): string
    {
        return preg_replace_callback('/{{\s*(.+?)( \| (.+?))?\s*}}/', function ($match) use ($locale) {
            $variable = $match[1];
            $locale = $match[3] ?? $locale;
            $languageData = $this->languageData[$locale] ?? [];

            if (!isset($languageData[$variable])) {
                $this->log("Variable \"{$variable}\" was not found in locale \"{$locale}\"", 1);
            }

            return $languageData[$variable] ?? '';
        }, $text);
    }

    /**
     * Creates a file from a template by substituting control structures and
     * template tags for language information.
     *
     * @param string $path
     * @return bool
     */
    public function createFileFromTemplate(string $path): bool
    {
        if (!$this->languageData) {
            return false;
        }

        $file = str_replace('.' . self::TEMPLATE_SUFFIX, '', $path);
        $templateContent = file_get_contents($path) ?? '';

        // replace variables within loops
        $fileContent = preg_replace_callback('/[ \t]*@forlang\n?(.*?)[ \t]*@endforlang\n?/s', function ($content) {
            $output = '';

            foreach ($this->languageData as $language) {
                $output .= $this->replaceVariables($content[1], $language['locale']);
            }

            return $output;
        }, $templateContent);

        // replace root variables
        $fileContent = $this->replaceVariables($fileContent);

        $success = !!file_put_contents($file, $fileContent);

        if ($success) {
            $this->log("File created successfully for template {$path}", 0);
        } else {
            $this->log("File could not be created for template {$path}", 2);
        }

        return $success;
    }

    /**
     * Creates a file for each available template.
     *
     * @return bool
     */
    public function createFilesFromTemplates(): bool
    {
        if (!$this->templateFiles) {
            if ($this->verbose) {
                $this->log('Could not find any template files to operate on', 1);
            }

            return false;
        }

        foreach ($this->templateFiles as $templateFile) {
            if (!$this->createFileFromTemplate($templateFile)) {
                return false;
            }
        }

        return true;
    }
}

(new LanguageHelper(true))->createFilesFromTemplates();
