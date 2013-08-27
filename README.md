HX Event Manager
==============

!!! Please Note !!!
This plugin relies on the excellent Event Manager plugin that can be downloaded from http://wordpress.org/plugins/events-manager/ for free. HX Event Manager is an extension to that plugin to add some extra functionality. Without this plugin HX Event Manager will not work at all.

h1. What is HX Event Manager
It's an extension to the WordPress Events Manager plugin to add extra functionality necessary for some events. These include:

* Create, manage and use custom booking form fields and their data in tables and exports
* Add a shortcode to display a participants table on any page

h1. How to install
Download the zip package and unpack contents in the wp-content/plugins dir. Make sure Event Manager plugin is installed first before activating HX Event Manager. Activate HXEM and start creating custom fields.

h1. How do I...
h2. Add new custom form fields?
Go to Events -> Custom Form Fields in the left menu. Currently it is not possible to create completely different registration forms and decide which one to use, the created fields will simply be joined with the already existing basic registration form fields.

h2. See the custom form fields data in the bookings table?
Use the gears symbol at the top of the bookings table to specify which columns need to be displayed. You should be able to drag the custom form field column names to the left to include them in the table.

h2. Include the booking table / participants list in a page or post?
Use the following short code syntax:

[bookings-table event=$eventID colums=booking_date,first_name,my_custom_field_slug,booking_comment]

You can get the Event ID by hovering over the link in the Events table and using the number that comes after event_id in the URL. The column names are based on the slugs used by Event Manager and the slugs created by the HXEM plugin when you create a new custom field. 

h1. Why this name?
HX stands for Hospitality Exchange. The need for this plugin was born in the CouchSurfing / BeWelcome community while organizing big events. The Events Manager plugin provides a good basic functionality, but lacked some features that were essential to a good administration. Because EM is so extensible with hooks and filters, this plugin could be made to work on top of that.