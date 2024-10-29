<?php
/*
Copyright © 2012 BandPage, Inc.

This program is free software: you can redistribute it and/or 
modif it under the terms of the GNU General Public License as 
published by the Free Software Foundation, either version 3 
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
See the GNU General Public License for more details. 

You should have received a copy of the GNU General Public 
License along with this program.  
If not, see <http://www.gnu.org/licenses/>.
 */

add_action( 'widgets_init', 'bandpage_extension_widget_init' );

function bandpage_extension_widget_init() {
	register_widget( 'BandPage_Extension_Widget' );
}

class BandPage_Extension_Widget extends BandPage_Base_Widget {

    function __construct() {
		parent::__construct( 'bpextension', __( 'BandPage', 'bandpage' ), array(
			'classname'   => 'widget-bp-extension bp-extension',
			'description' => __( 'Display your BandPage content', 'bandpage' )
		) );

	}

}

?>
