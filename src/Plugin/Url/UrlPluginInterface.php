<?php
namespace Phoebe\Plugin\Url;

interface UrlPluginInterface
{
    /**
     * @return string
     */
    public function getMessagePattern();

    /**
     * @param array $matches
     * @param ChannelContext $context
     */
    public function processMessage(array $matches, ChannelContext $context);
}
