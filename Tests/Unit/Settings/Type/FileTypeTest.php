<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/usercentrics.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Usercentrics\Tests\Unit\Settings\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use T3G\AgencyPack\Usercentrics\Settings\Type\FileType;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The settings type API is identical in TYPO3 v13 and v14, so one suite covers both.
 */
final class FileTypeTest extends UnitTestCase
{
    public static function validValuesDataProvider(): array
    {
        return [
            'empty list' => [[]],
            'file entry' => [[['dataProcessingService' => 'My Service', 'file' => 'my.js']]],
            'inline entry' => [[['dataProcessingService' => 'My Service', 'value' => 'alert(1);']]],
            'entry with attributes and options' => [[[
                'dataProcessingService' => 'My Service',
                'file' => 'my.js',
                'attributes' => ['async' => 'async'],
                'options' => ['priority' => 1],
            ]]],
            'multiple entries' => [[
                ['dataProcessingService' => 'A', 'file' => 'a.js'],
                ['dataProcessingService' => 'B', 'value' => 'alert(2);'],
            ]],
        ];
    }

    #[DataProvider('validValuesDataProvider')]
    #[Test]
    public function validateAcceptsValidValues(array $value): void
    {
        self::assertTrue($this->subject()->validate($value, $this->definition()));
    }

    public static function invalidValuesDataProvider(): array
    {
        return [
            'string that is no json' => ['not json'],
            'integer' => [42],
            'null' => [null],
            'entry without data processing service' => [[['file' => 'my.js']]],
            'entry with empty data processing service' => [[['dataProcessingService' => '', 'file' => 'my.js']]],
            'entry with neither file nor value' => [[['dataProcessingService' => 'My Service']]],
            'entry with empty file and empty value' => [[[
                'dataProcessingService' => 'My Service',
                'file' => '',
                'value' => '',
            ]]],
            'entry that is a scalar' => [['my.js']],
        ];
    }

    #[DataProvider('invalidValuesDataProvider')]
    #[Test]
    public function validateRejectsInvalidValues(mixed $value): void
    {
        self::assertFalse($this->subject()->validate($value, $this->definition()));
    }

    #[Test]
    public function validateAcceptsJsonEncodedValues(): void
    {
        $json = json_encode([['dataProcessingService' => 'My Service', 'file' => 'my.js']], JSON_THROW_ON_ERROR);

        self::assertTrue($this->subject()->validate($json, $this->definition()));
    }

    #[Test]
    public function transformValueNormalizesJsonEncodedValuesToArrays(): void
    {
        $json = json_encode([['dataProcessingService' => 'My Service', 'file' => 'my.js']], JSON_THROW_ON_ERROR);

        self::assertSame(
            [['dataProcessingService' => 'My Service', 'file' => 'my.js']],
            $this->subject()->transformValue($json, $this->definition())
        );
    }

    #[Test]
    public function transformValueKeepsValidArrays(): void
    {
        $value = [['dataProcessingService' => 'My Service', 'value' => 'alert(1);']];

        self::assertSame($value, $this->subject()->transformValue($value, $this->definition()));
    }

    #[Test]
    public function transformValueFallsBackToDefaultAndLogsForInvalidValues(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('reverting to default'), ['key' => 'plugin.tx_usercentrics.jsFiles']);

        $default = [['dataProcessingService' => 'Default', 'file' => 'default.js']];

        self::assertSame(
            $default,
            (new FileType($logger))->transformValue('nonsense', $this->definition($default))
        );
    }

    /**
     * A non-array default must not leak out of the declared array return type.
     */
    #[Test]
    public function transformValueReturnsEmptyArrayForNonArrayDefault(): void
    {
        self::assertSame([], $this->subject()->transformValue('nonsense', $this->definition(null)));
    }

    #[Test]
    public function javaScriptModuleIsTheRegisteredImportMapSpecifier(): void
    {
        self::assertSame('@t3g/usercentrics/settings/type/uc-file.js', $this->subject()->getJavaScriptModule());
    }

    private function subject(): FileType
    {
        return new FileType(new NullLogger());
    }

    private function definition(string|int|float|bool|array|null $default = []): SettingDefinition
    {
        return new SettingDefinition(
            key: 'plugin.tx_usercentrics.jsFiles',
            type: 'uc_file',
            default: $default,
            label: 'JS Files',
        );
    }
}
