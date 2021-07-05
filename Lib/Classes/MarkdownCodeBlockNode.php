<?php

namespace igk\Markdown;


class MarkdownCodeBlockNode extends MarkdownNode{
    
    public function getCanAddChild($tag=null)
    {
        return false;
    }
    public function getIsRenderTagName()
    {
        return false;
    }
    public function getIsLitteralContent(){
        return true;
    }
    public function __construct($type){
        parent::__construct("markdown-code-block");
        $this->setAttribute("type", $type);
    }
}