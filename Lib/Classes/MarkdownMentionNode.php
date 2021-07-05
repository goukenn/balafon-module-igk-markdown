<?php

namespace igk\Markdown;


class MarkdownMentionNode extends MarkdownNode{
    public function getCanAddChild($tag=null)
    {
        return false;
    }
    public function getIsRenderTagName()
    {
        return false;
    }
    public function __construct($mention){
        parent::__construct("markdown-mention");
        $this->Content = $mention;
    }
}