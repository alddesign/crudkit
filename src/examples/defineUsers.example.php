<?php
function defineUsers()
{			
	/*
	allow everything except 
	-updating/delete books
	-access to authors in general
	*/
	$restrictionSet1 = 
	new RestrictionSet
	('allow-all', 
		[	
			new RestrictionSetEntry('update', 'book'), 
			new RestrictionSetEnty('delete', 'book'),
			new RestrictionSetEntry('', 'author')
		]
	);

	/*
	deny everything except 
	-viewing list of books and authors
	*/
	$restrictionSet2 = 
	new RestrictionSet
	('deny-all', 
		[	
			new RestrictionSetEntry('list', 'book'), 
			new RestrictionSetEnty('list', 'author')
		]
	);

	$users = 
	[
		new User('the-admin', 'M0stS@ecurPwd4This1'), //has all rights
		new User('janedoe', 'P@ssw0rd', $restrictionSet1), //restricted
		new User('johndoe', 'jd123', $restrictionSet2) //restricted
	];
	
	return new AuthHelper($users);	
}
?>