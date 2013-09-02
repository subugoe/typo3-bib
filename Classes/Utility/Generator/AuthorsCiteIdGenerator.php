<?php
namespace Ipf\Bib\Utility\Generator;

class AuthorsCiteIdGenerator extends CiteIdGenerator {

	function generateBasicId($row) {
		$authors = $row['authors'];
		return $this->simplified_string($authors[0]['sn']);
	}

}

?>