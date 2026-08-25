<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Settings\Type;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsTypeInterface;

#[AsTaggedItem(index: 'uc_file')]
readonly class FileType implements SettingsTypeInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        if (is_string($value)) {
            try {
                $value = json_decode($value, false, 4, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // invalid json, ignore and handle below
            }
        }
        if (!is_array($value)) {
            return false;
        }
        return $this->doValidate($value, $definition);
    }

    public function transformValue(mixed $value, SettingDefinition $definition): array
    {
        if (is_string($value)) {
            try {
                $value = json_decode($value, false, 4, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // invalid json, ignore and handle below
            }
        }
        if (!is_array($value) || !$this->doValidate($value, $definition)) {
            $this->logger->warning('Setting validation field, reverting to default: {key}', ['key' => $definition->key]);
            return $definition->default;
        }

        return array_map(static fn (array|object $entry) => is_object($entry) ? (array)$entry : $entry, $value);
    }

    public function doValidate(array $value, SettingDefinition $definition): bool
    {
        foreach ($value as $v) {
            if (is_object($v)) {
                $v = (array)$v;
            }
            if (!is_array($v) || '' === ($v['dataProcessingService'] ?? '') || ('' === ($v['file'] ?? '') && '' === ($v['value'] ?? ''))) {
                return false;
            }
        }
        return true;
    }

    public function getJavaScriptModule(): string
    {
        return '@t3g/usercentrics/settings/type/uc-file.js';
    }
}
