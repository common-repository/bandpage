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

//
//   Base class for all BandPage widgets.  Should define common functionality.
//

class BandPage_Base_Widget extends WP_Widget {

    var $type = null;

    function form( $instance ) {
        // Set instance defaults to null
        $type = $theme = $title = null;

        $band_id = get_option( 'bandpage_bid' );

        // Set defaults
        $opacity    = 100;
        $height     = 400;
        $width      = 250;

        extract( $instance );

        // Do not have a band connected yet
        if ( false == $band_id || empty( $band_id ) ) {

            $id = null;

            // Is this widget already placed?
            if ( ! empty( $instance ) ) {
                // Create a id and instantle load the button
                $id = uniqid('pb-');
            }
            ?>

            <div class="bp-connect-config">
                <div <?php echo $id ? "id='$id'" : '' ?>class="bp-connect"></div>
                <p>Press the Connect button above to import your BandPage content.</p>
            </div>

            <?php
            if ( ! empty( $instance ) ) {
                ?>
                <script>
                    jQuery(document).ready(function($) {
                        setup_button('#<?php echo $id ?>');
                    });
                </script>

                <?php
            }
        }
        ?>

        <div class="bp-connect-form"<?php echo $band_id ? '' : ' style="display: none;"' ?>>
            <p>
                <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
                <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Widget Type:' ); ?>
                    <select class="" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" >
                        <option <?php if ( 'photo' == $type ) echo 'selected="selected"'; ?> value="photo">Photo</option> 
                        <option <?php if ( 'video' == $type ) echo 'selected="selected"'; ?> value="video">Video</option> 
                        <option <?php if ( 'player' == $type ) echo 'selected="selected"'; ?> value="player">Music</option> 
                        <option <?php if ( 'tour' == $type ) echo 'selected="selected"'; ?> value="tour">Shows</option> 
                        <option <?php if ( 'bio' == $type ) echo 'selected="selected"'; ?> value="bio">Bio</option> 
                        <option <?php if ( 'mailinglist' == $type ) echo 'selected="selected"'; ?> value="mailinglist">Mailing List</option> 
                    </select>
                </label> 
            <p>
            <p>
                <label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e( 'Height:' ); ?>
                <input class="" size="5" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" value="<?php echo esc_attr( $height ); ?>" />
                px</label> 
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'width' ); ?>"><?php _e( 'Width:' ); ?>
                <input class="" size="5" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" type="text" value="<?php echo esc_attr( $width ); ?>" />
                px</label> 
            </p>
            <p>
                <label for="<?php echo $this->get_field_id( 'theme' ); ?>"><?php _e( 'Theme:' ); ?>
                    <select class="" id="<?php echo $this->get_field_id( 'theme' ); ?>" name="<?php echo $this->get_field_name( 'theme' ); ?>" >
                        <option <?php if ( 'light' == $theme ) echo 'selected="selected"'; ?> value="light">Light</option> 
                        <option <?php if ( 'dark' == $theme ) echo 'selected="selected"'; ?> value="dark">Dark</option> 
                    </select>
                </label> 
            <p>
                <label for="<?php echo $this->get_field_id( 'opacity' ); ?>"><?php _e( 'Transparency:' ); ?>
                    <input class="" size="5" min="0" max="100" id="<?php echo $this->get_field_id( 'opacity' ); ?>" name="<?php echo $this->get_field_name( 'opacity' ); ?>" type="number" value="<?php echo esc_attr( $opacity ); ?>" />
                    0 to 100
                </label> 
            </p>
        </div>
        <?php 
    }

    // Runs when admin saves this instance options.
    function update( $new_instance, $old_instance ) {
        // processes widget options to be saved
        $opacity = (int) $new_instance['opacity'];
        if ( $opacity < 0 || $opacity > 100 ) {
            $opacity = 100;
        }
        $new_instance['opacity'] = $opacity;
        return $new_instance;
    }


    // Should only do the load part once
    function widget( $args, $instance ) {
        global $Band_Page;

        extract( $args );
        extract( $instance );

        if ( $this->type != null ) {
            $type = $this->type;
        }

        // If auth token has already loaded during this page load, returns the same one if already auth'd
        $access_token = $Band_Page->get_auth_token();

        $band_id = get_option( 'bandpage_bid' );

        print $before_widget;
		if ( isset( $title ) ) {
		    $title = apply_filters( 'widget_title', $title );
			echo $before_title . $title . $after_title;
        }
        ?>
            <div class="container"></div>
        <?php

        print $after_widget;

        $Band_Page->add_extension(array(
                                'bandbid'       => $band_id,
                                'widget_id'     => $args['widget_id'],
                                'type'          => $type,
                                'width'         => $width,
                                'height'        => $height,
                                'opacity'       => $opacity,
                                'theme'         => $theme,
                                'access_token'  => $access_token,
                                ));
    }

}

?>
