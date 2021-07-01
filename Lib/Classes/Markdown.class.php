<?php
namespace igk\Markdown; 

class Markdown {
	public function parse($src){
		$dv = igk_createnode("div");
		git_convert_md_to_html($dv, $src);
		return $dv->render();
	}
}