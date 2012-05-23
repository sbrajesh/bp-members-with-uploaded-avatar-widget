<?php
/**
 * Plugin Name: BP Members With Uploaded Avatars Widget
 * Author: Brajesh Singh
 * Plugin URI: http://buddydev.com
 * Author URI: http://ThinkingInWp.com
 * Version:1.0
 * Description:Show the members who have uploaded avatar
 */

class BPMemberWithUploadedAvatarWidget extends WP_Widget{
    
    function __construct() {
        parent::__construct(false, __( 'Members With Uploaded Avatars'));
    }
    /**
     * Output the content/list members here 
     * @param type $args
     * @param type $instance Instance of the widget
     */
    function widget($args,$instance){
        extract($args);
        echo $before_widget.$before_title.$instance['title'].$after_title;
               
        $helper=BPMemberWithAvatarHelper::get_instance();
        $helper->list_users($instance);
        echo $after_widget; 
        }
    
    
    function update($new_instance,$old_instance){
        $instance=$old_instance;
        $instance['title']=esc_html($new_instance['title']);
        $instance['max']=absint($new_instance['max']);
        $instance['type']=esc_html($new_instance['type']);
        $instance['size']=esc_html($new_instance['size']);
        $instance['height']=absint($new_instance['height']);
        $instance['width']=absint($new_instance['width']);
        
        return $instance;
    }
    
    
    function form($instance){
            $default=array('title'=>__('Recent Members'),'type'=>'random','max'=>5,'size'=>'full','width'=>50,'height'=>50);
            $instance=(array)$instance;//type cast to array
            $instance=wp_parse_args($instance,$default);
            extract($instance);?>
         
                 <p>
                     <label for="bp-member-with-avatar-title"><strong><?php _e('Title:'); ?> </strong>
                         <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
                     </label>
                 </p>
		 <p>
                     <label for="bp-member-with-avatar-max"><?php _e('Maximum no. of Users to show'); ?>
                         <input class="widefat" id="<?php echo $this->get_field_id( 'max' ); ?>" name="<?php echo $this->get_field_name( 'max' ); ?>" type="text" value="<?php echo esc_attr( $max ); ?>" style="width: 30%" />
                     </label>
                 </p>
                 <p>
                     <label for="bp-member-with-avatar-type"><?php _e('Order By'); ?>
                         <select class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" style="width: 30%">
                             <option value="random" <?php if($type=='random'):?> selected='selected'<?php endif;?> >Random</option>
                             <option value="alphabetical" <?php if($type=='alphabetical'):?> selected='selected'<?php endif;?> >Alphabetically</option>
                             <option value="active" <?php if($type=='active'):?> selected='selected'<?php endif;?> >Active</option>
                             <option value="newest" <?php if($type=='newest'):?> selected='selected'<?php endif;?> >Recently Joined</option>
                             <option value="popular" <?php if($type=='popular'):?> selected='selected'<?php endif;?> >Popularity</option>
                         </select>    
                     
                     </label>
                 </p>
                <p>
                     <label for="bp-member-with-avatar-size"><?php _e('Avatar Size'); ?>
                         <select class="widefat" id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>" style="width: 30%">
                             <option value="full" <?php if($size=='full'):?> selected='selected'<?php endif;?> >Full</option>
                             <option value="thumb" <?php if($size=='thumb'):?> selected='selected'<?php endif;?> >Thumb</option>
                            
                             
                         </select>    
                     
                     </label>
                 </p> 
		<p>
                     <label for="bp-member-with-avatar-height"><?php _e('Avatar height'); ?>
                         <input class="widefat" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" type="text" value="<?php echo esc_attr( $height ); ?>" style="width: 30%" />
                     </label>
                 </p>
		<p>
                     <label for="bp-member-with-avatar-width"><?php _e('Avatar Widtht'); ?>
                         <input class="widefat" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" type="text" value="<?php echo esc_attr( $width ); ?>" style="width: 30%" />
                     </label>
                 </p>

    <?php        
    }
    
}

/**
 * Register the widget
 */
add_Action('widgets_init','bp_register_member_with_avata_widget');

function bp_register_member_with_avata_widget(){
    register_widget('BPMemberWithUploadedAvatarWidget');
    
}
/**
 * Helper class for keeping record of avatar change & providing list of the users
 * It is implemented as a singleton class
 */

class BPMemberWithAvatarHelper{
    
    private  static $instance;
    private function __construct(){
            //record on new avatar upload
            add_action('xprofile_avatar_uploaded',array($this,'log_uploaded'));
            //on avatar delete
            add_action('bp_core_delete_existing_avatar',array($this,'log_deleted'));

    }
    /**
     * Get the singleton object
     * @return BPMemberWithAvatarHelper object
     */
    public static function get_instance(){
        
        if(!isset (self::$instance))
            self::$instance=new self();
        
        return self::$instance;
    }
    
    //on new avatar upload, record it to user meta
    function log_uploaded(){
        update_user_meta(bp_loggedin_user_id(), 'has_avatar', 1);
    }
    
    //on delete avatar, delete it from user meta
    function log_deleted($args){
        if($args['object']!='user')
        return;
        //we are sure it was user avatar delete

        //remove the log from user meta
        delete_user_meta(bp_loggedin_user_id(), 'has_avatar');
    }

    /**
     *
     * @global type $wpdb
     * @param type $type
     * @return type Return an array of Users object with the specifie
     */
    function get_users_with_avatar($max,$type='random'){
        global $wpdb;
        
        //Find all users with uploaded avatar
        $ids=$wpdb->get_col($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta } WHERE meta_key='has_avatar'"));//we don't need to check for meta value anyway
        
        if(empty($ids))
            return false;
        //ask buddypress to return the users based on type, I did not write a query as it will need to be redoing the samething as in the called function
        $users = BP_Core_User::get_users( $type, $max, 0,0,$ids,false,false);//I know, we are repeating here
        $users=$users['users'];
        return $users;

    }
    
    //helper to list users with avatars, will extend in future to show more details
    function list_users($args){
        $args=wp_parse_args((array)$args,array('type'=>'random','max'=>5,'size'=>'full','width'=>50,'height'=>50));
        extract($args);
        $users=self::get_users_with_avatar($max,$type);
        if(!empty($users)):?>
       <?php foreach($users as $user):?>   
            <a href="<?php echo bp_core_get_user_domain($user->id) ?>"><?php echo bp_core_fetch_avatar(array('type'=>$size,'width'=>$width,'height'=>$height,'item_id'=>$user->id)) ?></a>
     
      <?php endforeach; 
      else:?>
            <div class="error"><p>No members found</p></div>  
      
    <?php endif;
    echo '<div class="clear"></div>';
    }
}

BPMemberWithAvatarHelper::get_instance();