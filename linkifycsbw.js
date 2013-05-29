/* Search for CS usernames in the Bookings table and make them pretty by making it a clickable link */

jQuery(function($)
{
    console.log('init linkify');
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

            if(nickname.length != 0)
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

            if(nickname.length != 0)
            {
                element.html('<a href="http://bewelcome.org/members/'+nickname+'" target="_blank">'+nickname+'</a>');
            }
        })
    }
})
