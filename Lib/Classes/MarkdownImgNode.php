<?php

namespace igk\Markdown;


class MarkdownImgNode extends MarkdownNode{
    protected $html_tagname = "img";
    public function getCanAddChild($tag=null)
    {
        return false;
    }
    public function getIsRenderTagName()
    {
        return false;
    }
    public function __construct($src=null, $alt=""){
        parent::__construct("markdown-image");
        $this["src"] = $src;
        $this["alt"] = $alt; 
    }
}