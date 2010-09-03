<?php
include (dirname(__FILE__)."/xapian.php");

class Indexer
{
	private $_db;
	private $_indexer;
	private $_stemmer;

	public function __construct($database = null)
	{
		if (!$database) $database = $_SERVER["DOCUMENT_ROOT"]."/../application/data";
		$this->_db = new XapianWritableDatabase($database, Xapian::DB_CREATE_OR_OPEN);
		$this->_stemmer = new XapianStem("spanish");


	}

	public function index($model, $fieldContent = "texto")
	{
		$this->_indexer = new XapianTermGenerator();
		$this->_indexer->set_stemmer($this->_stemmer);
		$items = new $model();
		foreach($items->select() as $item) {
			$guid = $model."-".$item->id."-".web::instance()->getLanguage();
			$title = $item->get($item->getTitleField());
			$content = notildes(strip_tags(html_entity_decode("$title\n".$item->$fieldContent, ENT_COMPAT, "UTF-8")));

			$doc = new XapianDocument();
			$doc->set_data($content);

			$doc->add_value(1, $item->id);
			$doc->add_value(2, $model);
			$doc->add_value(3, $title);
			$doc->add_term($guid);

			$this->_indexer->set_document($doc);
			$this->_indexer->index_text($content);

			$this->_db->replace_document($guid, $doc);
		}
	}

	public function find($query)
	{
		$results = array();

		$enquire = new XapianEnquire($this->_db);
		$qp = new XapianQueryParser();

		$qp->set_stemmer($this->_stemmer);
		$qp->set_database($this->_db);
		$qp->set_stemming_strategy(XapianQueryParser::STEM_SOME);
		$query = $qp->parse_query($query);

		$enquire->set_query($query);
		$matches = $enquire->get_mset(0, 25);


		$i = $matches->begin();
		while (!$i->equals($matches->end())) {

			$doc = $i->get_document();
			$modelName = $doc->get_value(2);
			$item = new $modelName($doc->get_value(1));
			$result = new stdClass();
			$result->item 	= $item;
			$result->model 	= $modelName;
			$result->title 	= $doc->get_value(3);
			$result->percent = $i->get_percent();
			$results[]= $result;
			$i->next();
		}
		return $results;
	}
}
