<?php
function defineTables()
{
	//<CRUDKIT-TABLES-START> please do not remove this line - otherwise /admin-panel/auto-generate will not work
	return 
	[
		'author' => (new TableDescriptor('author', ['id'], true))
			->addColumn('id', 'Id', 'integer', [])
			->addColumn('firstname', 'Firstname', 'string', [])
			->addColumn('lastname', 'Lastname', 'string', [])
			->addColumn('active', 'Active', 'boolean', [])
			->addColumn('no_of_books', 'No. of Books', 'int', [])
			,
		'book' => (new TableDescriptor('book', ['id'], true))
			->addColumn('id', 'Id', 'integer', [])
			->addColumn('title', 'Title', 'string', [])
			->addColumn('description', 'Description', 'string', [])
			->addColumn('price', 'Price', 'float', [])
			->addColumn('author_id', 'Author Id', 'integer', [])
			,
	];
	//<CRUDKIT-TABLES-END> please do not remove this line - otherwise /admin-panel/auto-generate will not work
}
?>