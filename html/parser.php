<?
/**
 * @desc Objeto html base.
 *
 *
 * @Author José Sánchez Moreno
 * \ingroup html
 * @version 0.9
 * @date 1-1-03
 */

class html_parser
{
	function html_parser($html)
	{
		$this->_html = $html;
	}

	function getImages()
	{
		$images = array();
		if(preg_match_all("/<img[^>]*src=['\\\"]([^\\\"']*)['\\\"]|<img[^>]*src=([\w])/", $this->_html, $match))
		{
			return $match[1];
		}
		return $images;



	}

}
?>
