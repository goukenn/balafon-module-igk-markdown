<?php

namespace igk\Markdown;


class MarkdownLiNode extends MarkdownNode{   
    protected $html_tagname ="li";
    public function __construct(){
        parent::__construct("markdown-li");  
    }
}