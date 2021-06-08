$(document).ready(function()
{
    var reloadLabel = $('#crudkit-action-setup-reload-media label');
    var reloadUnfinishedLabel = $('#crudkit-action-setup-reload-unfinished-media label');

    reloadLabel.text(`ALLE Anime/Manga aktualisieren. Dauer: ${crudkit.record._duration}`);
    reloadUnfinishedLabel.text(`Laufende Anime/Manga aktualisieren. Dauer: ${crudkit.record._durationUnfinished}`);
});