<?php
private function defineRelations()
{		
	$this->tables['book']
		->defineManyToOneColumn('author_id', 'author', 'id');	
}
?>