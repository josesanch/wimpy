<?php
class html_template_filter_stripcomments implements html_template_ifilter
{
	
	public function apply($data)
	{		
		return preg_replace( '°<!--.*-->°msU', '', $data );
	}	
} 
?>