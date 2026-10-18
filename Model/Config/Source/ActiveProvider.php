<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminSso\Model\Config\Source;

use DmLab\AdminSso\Model\PresetRegistry;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Active-provider dropdown source for the admin config field.
 *
 * Options are built from the {@see PresetRegistry} — i.e. whatever provider
 * plugins registered their preset — so the list grows as plugins are installed
 * without this core knowing any IdP.
 */
class ActiveProvider implements OptionSourceInterface
{
    /**
     * @param PresetRegistry $presetRegistry
     */
    public function __construct(
        private readonly PresetRegistry $presetRegistry
    ) {
    }

    /**
     * @inheritDoc
     *
     * @return array<int,array{value:string,label:\Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        foreach ($this->presetRegistry->getAll() as $preset) {
            $options[] = ['value' => $preset->getCode(), 'label' => __($preset->getLabel())];
        }

        return $options;
    }
}
