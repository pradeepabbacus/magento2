<?php
namespace Smartwave\Porto\Plugin;

use Magento\Framework\App\ResponseInterface;

class UpdateBodyClass
{
	private $context;
	protected $helper;

	public function __construct(
		\Magento\Framework\View\Element\Context $context,
		\Smartwave\Porto\Helper\Data $helper
	) {
		$this->context = $context;
		$this->helper = $helper;
	}

	public function beforeRenderResult(\Magento\Framework\View\Result\Page $subject, ResponseInterface $response) {
		
		$page_layout = $this->helper->getConfig('porto_settings/general/layout');
		if($page_layout == "full_width") {
	        $page_layout = "layout-fullwidth";
	    } else if($page_layout == "1140") {
	        $page_layout = "layout-1140";
	    } else if($page_layout == "1280") {
	        $page_layout = "layout-1280";
	    }
		if($page_layout){
			$subject->getConfig()->addBodyClass($page_layout);
		}

	    $boxed = $this->helper->getConfig('porto_settings/general/boxed');
		if($boxed){
			$subject->getConfig()->addBodyClass($boxed);
		}

		if ($this->helper->getConfig('porto_settings/header/mobile_sticky_header'))
        	$subject->getConfig()->addBodyClass("mobile-sticky");
        if ($this->helper->getConfig('porto_settings/header/header_type') == "10")
        	$subject->getConfig()->addBodyClass("side-header");

		return [$response];
	}
}