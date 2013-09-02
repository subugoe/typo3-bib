<?php

class Tx_Bib_Utility_Generator_AuthorsCiteIdGenerator extends Tx_Bib_Utility_Generator_CiteIdGenerator {

	function generateBasicId($row) {
		$authors = $row['authors'];
		return $this->simplified_string($authors[0]['sn']);
	}

}

?>