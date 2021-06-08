$(document).ready(function()
{
    var mediaId = $("#crudkit-field-mediaId");
    var mediaType = $("#crudkit-field-mediaType");

    var mediaEpisodesVolumes = addNewReadonlyField('Episoden/Bände verfüg.', 'crudkit-field-mediaEpisodesVolumes');

    //Set the episodes an volumes which are available, so the user can see whats missing or going on
    mediaId.on("change.select2", function(e)
    {
        var data = $(this).select2("data")[0];

        mediaEpisodesVolumes.html(data.episodesVolumes);
        mediaType.val(data.type);
    });

    function addNewReadonlyField(name, id)
    {
        var container = $('#create-form dl');
        var field = $( 
        `
        <div class="form-group crudkit-lookup crudkit-before-after-field">
			<i class="fa fa-info-circle"></i>&nbsp;&nbsp;<label>${name}</label>
			<div>																									
			    <spany id="${id}">&nbsp;<span>											
			</div>
		</div>
        `);

        container.append(field);

        return container.find(`#${id}`);
    }
});