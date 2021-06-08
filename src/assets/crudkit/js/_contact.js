$(document).ready(function()
{
    var zip = $("#crudkit-field-zipCode");
    var countryCode = $("#crudkit-field-countryCode");
    var city = $("#crudkit-field-city");
    var region = $("#crudkit-field-region");

    zip.on("change.select2", function(e)
    { 
        var data = $(this).select2("data")[0];

        //console.log(data);

        if(data.textparts)
        {
            countryCode.val(data.textparts.countryCode);
            city.val(data.textparts.city);
            region.val(data.textparts.region);
        }
    });
});