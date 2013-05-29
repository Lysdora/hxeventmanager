<?php

class CustomBookingsBookingsTable extends EM_Bookings_Table
{
    function __construct()
    {
        parent::__construct();
    }

    function get_booking_actions($EM_Booking)
    {
        return array();
    }

    function get_headers()
    {
        return $this->cols_template;        
    }

    function output()
    {
        do_action('em_bookings_table_header',$this); //won't be overwritten by JS   
        $this->output_table();
        do_action('em_bookings_table_footer',$this); //won't be overwritten by JS           
    }

    function output_table()
    {
        $EM_Ticket = $this->get_ticket();
        $EM_Event = $this->get_event();
        $EM_Person = $this->get_person();
        $this->get_bookings(false); //get bookings and refresh
        error_log('headers in output_table: '.print_r($this->get_headers(), true));
        include(CB_PLUGIN_PATH . 'views/bookings-table.php');
    }
}