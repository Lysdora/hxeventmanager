        <div class='em-bookings-table em_obj' id="em-bookings-table">                
                <div class='table-wrap'>
                <table id='dbem-bookings-table' class='widefat post '>
                    <thead>
                        <tr>
                            <?php /*                        
                            <th class='manage-column column-cb check-column' scope='col'>
                                <input class='select-all' type="checkbox" value='1' />
                            </th>
                            */ ?>
                            <th class='manage-column' scope='col'><?php echo implode("</th><th class='manage-column' scope='col'>", $this->get_headers()); ?></th>
                        </tr>
                    </thead>
                    <?php if( $this->bookings_count > 0 ): ?>
                    <tbody>
                        <?php 
                        $rowno = 0;
                        $event_count = (!empty($event_count)) ? $event_count:0;
                        foreach ($this->bookings->bookings as $EM_Booking) {
                            //If booking status is confirmed or pending payment, display. Otherwise skip.
                            if($EM_Booking->booking_status == 0 || $EM_Booking->booking_status == 1):
                            ?>
                            <tr>
                                <?php  /*
                                <th scope="row" class="check-column" style="padding:7px 0px 7px;"><input type='checkbox' value='<?php echo $EM_Booking->booking_id ?>' name='bookings[]'/></th>
                                */ 
                                /* @var $EM_Booking EM_Booking */
                                /* @var $EM_Ticket_Booking EM_Ticket_Booking */
                                if( $this->show_tickets ){
                                    foreach($EM_Booking->get_tickets_bookings()->tickets_bookings as $EM_Ticket_Booking){
                                        ?><td><?php echo implode('</td><td>', $this->get_row($EM_Ticket_Booking)); ?></td><?php
                                    }
                                }else{
                                    ?><td><?php echo implode('</td><td>', $this->get_row($EM_Booking)); ?></td><?php
                                }
                                ?>
                            </tr>
                            <?php
                            endif;
                        }
                        ?>
                    </tbody>
                    <?php else: ?>
                        <tbody>
                            <tr><td scope="row" colspan="<?php echo count($this->cols); ?>"><?php _e('No bookings.', 'dbem'); ?></td></tr>
                        </tbody>
                    <?php endif; ?>
                </table>
                </div>
                <?php if( !empty($bookings_nav) && $this->bookings_count >= $this->limit ) : ?>
                <div class='tablenav'>
                    <?php echo $bookings_nav; ?>
                    <div class="clear"></div>
                </div>
                <?php endif; ?>
        </div>
