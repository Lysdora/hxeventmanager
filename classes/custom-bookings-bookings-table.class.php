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

    /**
     * Gets the bookings for this object instance according to its settings
     * @param boolean $force_refresh
     * @return EM_Bookings
     */
    function get_bookings($force_refresh = true){   
        if( empty($this->bookings) || $force_refresh ){
            $this->events = array();
            $EM_Ticket = $this->get_ticket();
            $EM_Event = $this->get_event();
            $EM_Person = $this->get_person();
            if( $EM_Person !== false ){
                $args = array('person'=>$EM_Person->ID,'scope'=>$this->scope,'status'=>$this->get_status_search(),'order'=>$this->order,'orderby'=>$this->orderby);
                $this->bookings_count = EM_Bookings::count($args);
                $this->bookings = EM_Bookings::get(array_merge($args, array('limit'=>$this->limit,'offset'=>$this->offset)));
                foreach($this->bookings->bookings as $EM_Booking){
                    //create event
                    if( !array_key_exists($EM_Booking->event_id,$this->events) ){
                        $this->events[$EM_Booking->event_id] = new EM_Event($EM_Booking->event_id);
                    }
                }
            }elseif( $EM_Ticket !== false ){
                //searching bookings with a specific ticket
                $args = array('ticket_id'=>$EM_Ticket->ticket_id, 'order'=>$this->order,'orderby'=>$this->orderby);
                $this->bookings_count = EM_Bookings::count($args);
                $this->bookings = EM_Bookings::get(array_merge($args, array('limit'=>$this->limit,'offset'=>$this->offset)));
                $this->events[$EM_Ticket->event_id] = $EM_Ticket->get_event();
            }elseif( $EM_Event !== false ){
                //bookings for an event
                $args = array('event'=>$EM_Event->event_id,'scope'=>false,'status'=>$this->get_status_search(),'order'=>$this->order,'orderby'=>$this->orderby);
                //$args['owner'] = !current_user_can('manage_others_bookings') ? get_current_user_id() : false;
                $this->bookings_count = EM_Bookings::count($args);
                $this->bookings = EM_Bookings::get(array_merge($args, array('limit'=>$this->limit,'offset'=>$this->offset)));
                $this->events[$EM_Event->event_id] = $EM_Event;
            }else{
                //all bookings for a status
                $args = array('status'=>$this->get_status_search(),'scope'=>$this->scope,'order'=>$this->order,'orderby'=>$this->orderby);
                //$args['owner'] = !current_user_can('manage_others_bookings') ? get_current_user_id() : false;
                $this->bookings_count = EM_Bookings::count($args);
                $this->bookings = EM_Bookings::get(array_merge($args, array('limit'=>$this->limit,'offset'=>$this->offset)));
                //Now let's create events and bookings for this instead of giving each booking an event
                foreach($this->bookings->bookings as $EM_Booking){
                    //create event
                    if( !array_key_exists($EM_Booking->event_id,$this->events) ){
                        $this->events[$EM_Booking->event_id] = new EM_Event($EM_Booking->event_id);
                    }
                }
            }
        }
        return $this->bookings;
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
        $_REQUEST['is_public'] = 1;
        $this->get_bookings(false); //get bookings and refresh
        include(CB_PLUGIN_PATH . 'views/bookings-table.php');
    }
}