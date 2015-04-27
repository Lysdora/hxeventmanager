jQuery(function($)
{
    /* Recalculate the total price for the current order */
    if($('.em-ticket-select').length != 0)
    {
       $('.em-ticket-select').on('change', recalculateAllPrices);
       currency = $('td.em-bookings-ticket-table-price').first().text().substring(0,1);

       $('table.em-tickets').after('<h3>Total amount: <span id="cb-totalprice"></span></h3>');

       $('.em-ticket-select').trigger('change');
    }   



    /* Search for CS usernames in the Bookings table and make them pretty by making it a clickable link */
    tdindex = undefined;

    //CS
    $('#dbem-bookings-table th').each(function(i, e)
    {
        var header = $(e).text().toLowerCase();
        if(header.indexOf('cs nick') !== -1 || header.indexOf('cs nickname') !== -1 
            || header.indexOf('couchsurfing') !== -1 || header.indexOf('couch surfing') !== -1)
        {
            tdindex = i;
        }
    });

    if(tdindex)
    {
        $('#dbem-bookings-table tr').each(function(i, e)
        {
            var element = $(e).find('td').eq(tdindex);
            var nickname = element.text();
            console.log('extracted nick: '+nickname);

            if(nickname.length != 0 && nickname != '--')
            {
                element.html('<a href="http://couchsurfing.org/people/'+nickname+'" target="_blank">'+nickname+'</a>');
            }
        })
    }

    tdindex = undefined;

    //BW
    $('#dbem-bookings-table th').each(function(i, e)
    {
        var header = $(e).text().toLowerCase();
        if(header.indexOf('bw nick') !== -1 || header.indexOf('bw nickname') !== -1 
            || header.indexOf('bewelcome') !== -1)
        {
            tdindex = i;
        }
    });

    if(tdindex)
    {
        $('#dbem-bookings-table tr').each(function(i, e)
        {
            var element = $(e).find('td').eq(tdindex);
            var nickname = element.text();
            console.log('extracted nick: '+nickname);

            if(nickname.length != 0 && nickname != '--')
            {
                element.html('<a href="http://bewelcome.org/members/'+nickname+'" target="_blank">'+nickname+'</a>');
            }
        })
    }

    /* Prevent people from setting a T-shirt size without actually ordering a T-shirt */
    $('#cb-form-what-size-t-shirt-do-you-want').parent().hide();

    $('.em-bookings-ticket-table-type:contains("T-shirt")')
    .siblings('td.em-bookings-ticket-table-spaces')
    .children('select')
    .on('change', checkTshirt);

    /* Prevent people from setting a T-shirt size without actually ordering a T-shirt */
    $('#cb-form-dinner-choice').parent().hide();

    $('.em-bookings-ticket-table-type:contains("Main Dinner")')
    .siblings('td.em-bookings-ticket-table-spaces')
    .children('select')
    .on('change', checkDinner);

})


/* Function to recalculate the prices in the table when registering for the event */
function recalculateAllPrices(event)
{
    var totalamount = 0.0;

    $('.em-ticket').each(function(i, e)
    {
        var price = parseFloat($(e).find('.em-bookings-ticket-table-price').first().text().replace(/[^\d.,]/g, "").replace(',','.'));
        var spaces = parseInt($(e).find('.em-bookings-ticket-table-spaces select').first().val());

        if($(e).find('.em-bookings-ticket-table-spaces').first().text().indexOf('N/A') != -1)
          totalamount += 0; //skip this item for total price calculation, because it is no longer available
        else
          totalamount += (spaces * price);
    })

    $('#cb-totalprice').text(currency+totalamount.toFixed(2));
}         

function checkTshirt(event)
{
    if(jQuery(event.target).val() == 0)
        $('#cb-form-what-size-t-shirt-do-you-want').val(0).parent().hide();
    else
        $('#cb-form-what-size-t-shirt-do-you-want').parent().show();
}

function checkDinner(event)
{
    if(jQuery(event.target).val() == 0)
        $('#cb-form-dinner-choice').val(0).parent().hide();
    else
        $('#cb-form-dinner-choice').parent().show();
}