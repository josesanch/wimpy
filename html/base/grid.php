<?
class html_base_grid extends html_object {

	private $columns;
//	public function __construct() {

//	}

	public function toHtml($model, $sql = null, $columns = null) {
		$form = new html_base_form(get_class($model));
		$form->onsubmit('return do_search(this);');

		$model->setPageSize(25);
		if(web::request("page")) $model->setCurrentPage(web::request("page"));

		if(web::request("search")) {
				if($columns) $c = split(" ?, ?", $columns);
				else $c = array_keys($model->getFields());
				$search = array();
				foreach($c as $field) {
					$search[]= "$field like '%".web::request('search')."%'";
				}
				$search = " (".join(" or ", $search).")";
				$sql = $sql ? $sql." and ".$search : $search;

		}

		$columns = $columns ? split(" ?, ?", $columns) :  array_keys($model->getFields());
		$sqlcolumns = array();
		foreach($columns as $column) {
			$attrs = $model->getFields($column);
			if($attrs['belongs_to']) {
				$belongs_model_name =$attrs['belongs_to'];
				$table = substr($column, 0, -3);
				$belongs_model = new $belongs_model_name;
				$sqlcolumns[]= "(select ".$belongs_model->getTitleField()." from $table where id=$column) as $column";
			} else {
				$sqlcolumns[] = $column;
			}

		}
		if(web::request("order")) {
			$order = "order: ".web::request("order");
			$desc = web::request("desc") == 'true' ? "false" : "true";
			if(web::request("desc") == 'true') $order .= " desc";
		} else {
			$desc = "false";
			$order = "order: id";
		}
		$results = $model->select($sql, "columns: ".join(", ", $sqlcolumns), $order);

		$form->add(js_once('jquery')."

				<div style='width: 90%; background-color: gray; margin: auto; '>
				<div style='padding: 10px; color: white; width: 98%; height: 20px;' >
					<table border=0 width='100%' cellpadding=0 cellspacing='0'>
						<td>
							Buscar: <input type='text' name='search' value='".web::request('search')."' size=20>
							<input type=button value=buscar onclick=\"do_search(this.form)\";>
							<input type=button value=' + nuevo' onclick='document.location=\"/admin/".get_class($model)."/edit/0".web::params()."\"'>
						<td align='right'>".helpers_paginate::toHtml($results)."</td>
					</table>
				</div>
				</div>


		");

		$form->add("<table border=0 class=grid cellpadding=3 cellspacing=1 align=center width='90%'>
						<tr >");

		foreach($columns as $column) {
			if(web::request('order') == $column) {
	        	$arrow = web::request('desc') == 'true' ? "&uarr;" : "&darr;";
			} else {
				$arrow = '';
			}
			$form->add("<th class=grid_header><a href='".web::uri("/order=$column/desc=$desc")."' class='header'>$column</a> $arrow</th>");
		}

		$i = 0;


		foreach($results as $row) {
			$form->add("<tr class='grid_row row_".($i++ % 2 == 0 ? 'even' : 'odd')."' onclick='document.location=\"/admin/".get_class($row)."/edit/".$row->get("id")."".web::params()."\"'>");
			foreach($columns as $column) {
				$form->add("<td class=grid_cell>".$row->get($column)."</td>");
			}
			$form->add("</tr>");
		}
		$form->add("</table>");

		$form->add("<div style='width: 90%; background-color: gray; margin: auto; text-align: right;'><div style='padding: 5px;margin-right: 10px; color: white;'>".helpers_paginate::toHtml($results)."</div></div><br><br>
		<script>
			var background = '';
			$('.grid_row').bind('mouseover', function() {

				$(this).addClass('grid_row_hover');

			});
			$('.grid_row').bind('mouseout', function() {
				$(this).removeClass('grid_row_hover');
			});

			function do_search(form) {
				document.location='".web::uri("/page=/search=")."' + form.search.value;
				return false;
			}
		</script>
		");

		return $form->toHtml();

	}
}
?>
