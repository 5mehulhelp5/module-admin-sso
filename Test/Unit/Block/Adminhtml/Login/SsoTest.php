<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSso\Test\Unit\Block\Adminhtml\Login;

use DmLab\AdminSso\Block\Adminhtml\Login\Sso;
use DmLab\AdminSso\Model\ActiveProviderResolver;
use DmLab\AdminSso\Model\Config;
use DmLab\SsoCore\Api\ProviderPresetInterface;
use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Phrase\RendererInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;

class SsoTest extends TestCase
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

    private function preset(string $buttonLabel, ?string $iconUrl): ProviderPresetInterface
    {
        $preset = $this->createStub(ProviderPresetInterface::class);
        $preset->method('getButtonLabel')->willReturn($buttonLabel);
        $preset->method('getButtonIconUrl')->willReturn($iconUrl);

        return $preset;
    }

    private function block(
        bool $enabled,
        ?ProviderPresetInterface $active,
        ?BackendUrlInterface $backendUrl = null
    ): Sso {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);

        $resolver = $this->createStub(ActiveProviderResolver::class);
        $resolver->method('getActive')->willReturn($active);

        return (new ObjectManager($this))->getObject(Sso::class, [
            'context' => $this->createStub(Context::class),
            'config' => $config,
            'activeProviderResolver' => $resolver,
            'backendUrl' => $backendUrl ?? $this->createStub(BackendUrlInterface::class),
        ]);
    }

    public function testAvailableWhenEnabledAndProviderActive(): void
    {
        $block = $this->block(true, $this->preset('Sign in with Okta', 'https://cdn/okta.svg'));

        self::assertTrue($block->isAvailable());
        self::assertSame('Sign in with Okta', $block->getButtonLabel());
        self::assertSame('https://cdn/okta.svg', $block->getButtonIconUrl());
    }

    public function testButtonLabelIsTranslated(): void
    {
        $renderer = $this->createStub(RendererInterface::class);
        $renderer->method('render')->willReturnCallback(
            static fn (array $source): string => 'Anmelden mit ' . end($source)
        );
        Phrase::setRenderer($renderer);

        $block = $this->block(true, $this->preset('Okta', null));

        self::assertSame('Anmelden mit Okta', $block->getButtonLabel());
    }

    public function testHiddenWhenModuleDisabled(): void
    {
        $block = $this->block(false, $this->preset('Sign in with Okta', null));

        self::assertFalse($block->isAvailable());
    }

    public function testHiddenWhenNoProviderActive(): void
    {
        $block = $this->block(true, null);

        self::assertFalse($block->isAvailable());
        self::assertSame('', $block->getButtonLabel());
        self::assertNull($block->getButtonIconUrl());
    }

    public function testStartUrlBuiltFromRoute(): void
    {
        $backendUrl = $this->createMock(BackendUrlInterface::class);
        $backendUrl->expects(self::once())
            ->method('getUrl')
            ->with('adminsso/sso/start')
            ->willReturn('https://magento.loc/admin/adminsso/sso/start');

        $block = $this->block(true, $this->preset('Sign in with Okta', null), $backendUrl);

        self::assertSame('https://magento.loc/admin/adminsso/sso/start', $block->getStartUrl());
    }

    public function testRendersNothingWhenNotAvailable(): void
    {
        $block = $this->block(false, null);

        $toHtml = new \ReflectionMethod($block, '_toHtml');

        self::assertSame('', $toHtml->invoke($block));
    }
}
