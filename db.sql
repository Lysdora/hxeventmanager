--
-- Table structure for table wp_cb_fields
--

CREATE TABLE $cb_fields (
  field_slug varchar(255) NOT NULL,
  field_label varchar(255) NOT NULL,
  field_description text NOT NULL,
  field_checkbox_label varchar(255) DEFAULT NULL,
  field_type enum('text','select','textarea','checkbox', 'captcha') NOT NULL,
  field_options text NOT NULL,
  field_required tinyint(1) NOT NULL,
  field_active tinyint(1) NOT NULL,
  UNIQUE KEY  field_slug (field_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table wp_cb_field_data
--

CREATE TABLE $cb_field_data (
  field_data_ID int(11) NOT NULL AUTO_INCREMENT,
  user_ID int(11) NOT NULL,
  event_ID int(11) NOT NULL,
  booking_ID int(11) NOT NULL,
  field_slug varchar(255) NOT NULL,
  field_data text NOT NULL,
  PRIMARY KEY  (field_data_ID)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;