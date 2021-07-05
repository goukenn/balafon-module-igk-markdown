<?php

namespace igk\Markdown;


class MarkdownOlNode extends MarkdownNode{   

    protected $html_tagname ="ol";
    public function __construct(){
        parent::__construct("markdown-ol");  
    }
}