<?php
private function defineUsers()
{			
	//Allows all, but create and delete books.
	$restrictionSet = new RestrictionSet
	(
		'allow-all', 
		[
			['page-id' => 'book', 'action' => 'create'],
			['page-id' => 'author', 'action' => 'delete']
		]
	);
	
	//Startpage is the book card of Book ID 5.
	$startpage = new Startpage('books', 'card', ['id' => 1]);
	
	//Users
	return
	[
		new CrudkitUser('johnsmith', 'pwd123'),
		new CrudkitUser('janedoe', 'pwd456', $startpage, $restrictionSet)
	];	
}
?>