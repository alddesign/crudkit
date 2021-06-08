$(document).ready(function()
{
	setBannerImage();
	moveCoverImage();
	formatGenres();

	function formatGenres()
	{
		var baseUrl = 'https://anilist.co/search/anime?genres=';
		var lookup = $('#crudkit-lookup-genres');
		var lookupValue = $('#crudkit-lookup-genres .crudkit-lookup-value');
		var genresText = lookupValue.html();

		lookup.addClass('crudkit-with-btn');
		if(genresText)
		{

			lookupValue.html('');
			var genres = genresText.split(';;');
			genres.forEach(genre => 
			{
				if(genre)
				{
					var url = baseUrl + encodeURIComponent(genre);
					lookupValue.append($(`<a class="btn btn-small btn-accent" target="_blank" href="${url}" style="margin: 0 10px 8px 0;"><i class="fa fa-external-link"></i> &nbsp;${genre}</a>`));
				}
			});
		}
	}

	function setBannerImage()
	{
		var bannerImage = $("#crudkit-field-bannerImage");
		var img = bannerImage.find(".crudkit-card-field-wrapper3 img");
		var header = $(".content-header");
		var headerRow = $(".content-header .row:first-child");

		bannerImage.remove();

		if(bannerImage.length === 1 && img.attr("src"))
		{
			header.css(
			{
				"background": `linear-gradient(to bottom, rgba(0,0,0,0), rgba(0,0,0,0.6)), url('${img.attr("src")}')`, 
				"background-position": "center", 
				"background-size" : "cover",
				"height": "250px",
				"padding-top" : "0"
			});
		
			headerRow.css(
			{
				"background" : "rgba(35,38,67,.5)",
				"padding-top" : "15px",
				"color": "#e5e7ea",
				"padding-top": "15px"
			});
		}
	}
	
	function moveCoverImage()
	{
		var fields = $("#crudkit-field-id, #crudkit-field-titleUserPreferred, #crudkit-field-description");
		var coverImage = $("#crudkit-field-coverImage");
		fields.remove();

		var dl = $(".content dl"); 
		var row = $('<div class="row"><div class="col-md-3 col--1"></div><div class="col-md-9 col--2"></div></div>');
		row.find('.col--1').append(coverImage);
		row.find('.col--2').append(fields);

		dl.prepend(row);
	}
});