<?php
namespace Smartwave\Porto\Model\Config\Settings\General;

class Notice implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('No')],
            ['value' => '1', 'label' => __('Above of the Header')],
            ['value' => '2', 'label' => __('Below of the Header')]
        ];
    }

    public function toArray()
    {
        return [
            '' => __('No'),
            '1' => __('Above of the Header'),
            '2' => __('Below of the Header')
        ];
    }
}
