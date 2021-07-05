<?php

namespace igk\Markdown;


class MarkdownQuotesNode extends MarkdownNode{
    protected $html_tagname = "quotes";
    public function getIsGroup(){
        
    }
    public function getCanAddChild($tag=null)
    {
        return false;
    }
    public function getIsRenderTagName()
    {
        return false;
    }
     
    public function __construct($content = ""){
        parent::__construct("markdown-quotes");
        $this->Content = $content;
    }
}