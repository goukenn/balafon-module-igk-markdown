<?php

namespace igk\Markdown;


class MarkdownUlNode extends MarkdownNode{   
    protected $html_tagname ="ul";
    public function __construct(){
        parent::__construct("markdown-ul");  
    }
}