<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSso\Test\Unit\Model\Config\Source;

use DmLab\AdminSso\Model\Config\Source\ActiveProvider;
use DmLab\AdminSso\Model\PresetRegistry;
use DmLab\SsoCore\Api\ProviderPresetInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use PHPUnit\Framework\TestCase;

class ActiveProviderTest extends TestCase
{
    /** @var RendererInterface|null Restored after each test. */
    private ?RendererInterface $originalRenderer = null;

    protected function setUp(): void
    {
        $this->originalRenderer = Phrase::getRenderer();
    }

    protected function tearDown(): void
    {
        Phrase::setRenderer($this->originalRenderer);
    }

    private function preset(string $code, string $label): ProviderPresetInterface
    {
        $preset = $this->createStub(ProviderPresetInterface::class);
        $preset->method('getCode')->willReturn($code);
        $preset->method('getLabel')->willReturn($label);

        return $preset;
    }

    public function testFirstOptionIsAlwaysAnEmptyPlaceholder(): void
    {
        $source = new ActiveProvider(new PresetRegistry([]));

        $options = $source->toOptionArray();
        self::assertCount(1, $options);
        self::assertSame('', $options[0]['value']);
    }

    public function testBuildsOneOptionPerRegisteredPreset(): void
    {
        $source = new ActiveProvider(new PresetRegistry([
            $this->preset('okta', 'Okta'),
            $this->preset('azure', 'Microsoft Entra ID'),
        ]));

        $options = $source->toOptionArray();

        self::assertSame('', $options[0]['value']);
        self::assertSame(['value' => 'okta', 'label' => 'Okta'], self::normalize($options[1]));
        self::assertSame(['value' => 'azure', 'label' => 'Microsoft Entra ID'], self::normalize($options[2]));
    }

    public function testPresetLabelsAreTranslatablePhrases(): void
    {
        $renderer = $this->createStub(RendererInterface::class);
        $renderer->method('render')->willReturnCallback(
            static fn (array $source): string => strtoupper(end($source))
        );
        Phrase::setRenderer($renderer);

        $source = new ActiveProvider(new PresetRegistry([$this->preset('okta', 'Okta')]));

        $label = $source->toOptionArray()[1]['label'];
        self::assertInstanceOf(Phrase::class, $label);
        self::assertSame('Okta', $label->getText());
        self::assertSame('OKTA', (string)$label);
    }

    private static function normalize(array $option): array
    {
        return ['value' => $option['value'], 'label' => (string)$option['label']];
    }
}
