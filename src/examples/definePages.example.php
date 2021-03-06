<?php
function definePages()
{
	//<CRUDKIT-PAGES-START> please do not remove this line - otherwise /admin-panel/auto-generate will not work
	
	//Function for custom action button
	$showAuthor = function($record, $pageDescriptor, $action)
	{
		//this will open the list page, filtered to the book´s author
		return 
			Response::redirectToAction('\Alddesign\Crudkit\Controllers\AdminPanelController@listView', 
		    ['page-id' => 'author', 'ff-0' => 'id', 'fo-0' => '=', 'fv-0' => $record['author_id']]);
	};
	
	$test = function($record, $pageDescriptor, $action)
	{
		//will show the book´s title
		echo $record["title"];
		die;
	};
	
	return
	[
		'author' => (new PageDescriptor('Author', 'author', $this->tables['author']))
			->setSummaryColumnsAll() //defines the columns which are shown on the list page (all in this case)
			->setCardLinkColumns(['id']) //clicking on these column(s) will open the card page
			,
		'book' => (new PageDescriptor('Title', 'title', $this->tables['title']))
			->setSummaryColumnsAll()
			->setCardLinkColumns(['id'])
			->addAction('authors', 'Author', 'Show author...', $showAuthor,true, true, 'file-text', 'primary')
			->addAction('test', 'Test', 'Click me', $test,true, true, 'file-text', 'primary')
	];
	//<CRUDKIT-PAGES-END> please do not remove this line - otherwise /admin-panel/auto-generate will not work
}