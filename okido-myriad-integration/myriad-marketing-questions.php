<?php

use \theantichris\WpPluginFramework as wpf;
global $viewData;

class myriad_marketing_questions_plugin {

	public function __construct(){
		global $wpdb, $wp_query;
		$this->wpdb =& $wpdb;
		$this->wp_query =& $wp_query;

		add_action('init', array(&$this, 'create_marketing_questions_posttype'));

        add_action('add_meta_boxes', array(&$this, 'create_meta_box'));
        add_filter('save_post', array(&$this, 'save_meta_box'), 10, 2);

		add_action('woocommerce_after_order_notes', array(&$this, 'survey_checkout_fields'));
		add_action('woocommerce_checkout_process', array(&$this, 'myriad_custom_checkout_field_process'));
		add_action('woocommerce_checkout_update_order_meta', array(&$this, 'survey_update_order_meta'));

        add_action('prefix_hourly_event', array(&$this, 'prefix_do_this_hourly'));
	}

	/**
	 * On the scheduled action hook, run a function.
	 */
	public function prefix_do_this_hourly() {
		$this->sendDemographics();
	}

	function sendDemographics()
	{
		// error_reporting(E_ALL); ini_set('display_errors', '1');
		// Get all the orders which have demographics to send to Myriad
		$myquery = new WP_Query( "post_type=shop_order&meta_key=demogs-to-send&meta_value=y&order=ASC&posts_per_page=-1" );

	    $orderController = MyriadOrderController::instance();
		// Loop through them all
		while ($myquery->have_posts())
		{
			$myquery->the_post();
			// Retrieve the meta data from the order
			// We only need the numeric value so split off the rest
			$demographic_questions = unserialize(get_post_meta(get_the_ID(), 'demogs-data', true));

			$myriad_id = get_post_meta(get_the_ID(), 'myriad-id', true );
			$contactID = $orderController->getOrderBasic($myriad_id)['Invoice_Contact_ID'];

			foreach($demographic_questions as $demographic_question_id => $demographic_question_value)
			{
				// Map the demographic fields to their Myriad ID equivalents and send to Myriad
				$orderController->setContactDemographicAnswer($contactID, $demographic_question_id, $demographic_question_value);
			}

			// Mark the order as having had its demographics sent
		 	update_post_meta( $myquery->post->ID, 'demogs-to-send', 'n' );
			
		}
		
		wp_reset_postdata();

	}

	public function survey_update_order_meta($order_id){
		$demographic_questions = $_POST['demographic'];

		update_post_meta( $order_id, 'demogs-data', isset($_POST['demographic']) && is_array($_POST['demographic']) ? serialize($_POST['demographic']) : serialize(array()) );
		update_post_meta( $order_id, 'demogs-to-send', 'y' );
	}

	public function survey_checkout_fields( $checkout ) {	

    	$orderController =  MyriadOrderController::instance();
    	$has_questions = false;

	    $args = array('post_type' => 'marketing_questions', 'posts_per_page' => -1, 'orderby' =>'date', 'order' => 'ASC');
		$loop = new WP_Query( $args );

		$loop_questions = array();
		while ( $loop->have_posts() ) : $loop->the_post();
			global $post, $woocommerce; 
			if($this->is_category_in_cart(get_post_meta($post->ID, 'woocommcerce_product_type', true), $woocommerce->cart->get_cart()) || $this->is_product_in_cart(get_post_meta($post->ID, 'woocommerce_product_ids', true), $woocommerce->cart->get_cart())){
				$has_questions = true;

				$weight = (int)get_post_meta($post->ID, 'woocommcerce_question_weight', true) > 1 ? (int)get_post_meta($post->ID, 'woocommcerce_question_weight', true) : 75;

				$demographic_question_answers = $orderController->getDemographicAnswers(get_post_meta($post->ID, 'myriad_question_id', true));

				if(((int)get_post_meta($post->ID, 'myriad_question_type', true) == 2 || (int)get_post_meta($post->ID, 'myriad_question_type', true) == 3) && count($demographic_question_answers) == 0){
					$demographic_question_answers = array('yes' => 'Yes', 'no' => 'No');
				}

				$required = get_post_meta($post->ID, 'woocommcerce_answer_required', true);
				switch((int)get_post_meta($post->ID, 'myriad_question_type', true)){
					case '2':
						$loop_questions[$weight][] = woocommerce_form_field( 'demographic['.get_post_meta($post->ID, 'myriad_question_id', true).']', array(
							'type'          => 'select',
							'class'         => array('my-field-class form-row-wide demographic_' . str_replace(array('[', ']'), '', get_post_meta($post->ID, 'myriad_question_id', true)) . '_field'),
							'label'         => the_title(null, null, false),
							'options'       => /*array('' => 'Select') + */$demographic_question_answers,
							'custom_attributes' => array('multiple' => "multiple") + ($required != "no" ? array('required' => 'required') : array()),
							'return'		=> true,
						), $checkout->get_value( 'demographic['.get_post_meta($post->ID, 'myriad_question_id', true).']' ));
						break;
					case 13:
						$loop_questions[$weight][] = woocommerce_form_field( 'demographic['.get_post_meta($post->ID, 'myriad_question_id', true).']', array(
							'type'          => 'text',
							'class'         => array('my-field-class form-row-wide'),
							'label'         => the_title(null, null, false),
							'custom_attributes' => ($required != "no" ? array('required' => 'required') : array()),
							'return'		=> true,
						), $checkout->get_value( 'demographic['.get_post_meta($post->ID, 'myriad_question_id', true).']' ));
						break;
					default:
						$loop_questions[$weight][] = woocommerce_form_field( 'demographic['.get_post_meta($post->ID, 'myriad_question_id', true).']', array(
							'type'          => 'select',
							'class'         => array('my-field-class form-row-wide'),
							'label'         => the_title(null, null, false),
							'options'       => /*array('' => 'Select') + */$demographic_question_answers,
							'return'		=> true,
							'custom_attributes' => ($required != "no" ? array('required' => 'required') : array()),
						), $checkout->get_value( 'demographic['.get_post_meta($post->ID, 'myriad_question_id', true).']' ));
						break;
					
				}
			}
	    endwhile;

		if($has_questions){
			echo '<div id="survey_checkout_fields"><div class="gd_checkout_section">
								<h3 >Before confirming your registration, we’d like to know a little bit more about you</h3></div>';
		}

	    ksort($loop_questions);

	    foreach($loop_questions as $questions)
	    {
	    	foreach($questions as $question)
	    	{
	    		print $question;
	    	}
	    }
	 
	 	if($has_questions == true)
	 	{
			echo '</div>';
	 	}
	}

	/**
	 * Validate the custom field.
	 */ 
	function myriad_custom_checkout_field_process() {
		global $woocommerce;

	    $args = array('post_type' => 'marketing_questions', 'posts_per_page' => -1, 'orderby' =>'date', 'order' => 'ASC');
		$loop = new WP_Query( $args );

		$error = false;

		$loop_questions = array();
		while ( $loop->have_posts() ) : $loop->the_post();
			global $post, $woocommerce; 
			if($this->is_category_in_cart(get_post_meta($post->ID, 'woocommcerce_product_type', true), $woocommerce->cart->get_cart()) || $this->is_product_in_cart(get_post_meta($post->ID, 'woocommerce_product_ids', true), $woocommerce->cart->get_cart())){
				$required = get_post_meta($post->ID, 'woocommcerce_answer_required', true);

				if($required != "no" && 
					(
						!isset($_POST['demographic'][get_post_meta($post->ID, 'myriad_question_id')[0]]) 
					||  $_POST['demographic'][get_post_meta($post->ID, 'myriad_question_id')[0]] == ""
					)
					){
						$error = true;
				}
			}
		endwhile;

		if($error)
		{
			$woocommerce->add_error('Please ensure all fields marked with an <span color="red">*</span> have been entered.');
		}
	}

	private function is_product_in_cart($product_ids, $cart){
		$product_in_cart = false;

	    foreach ( $cart as $cart_item_key => $values )
	    {
	    	$_product = $values['data'];

	    	if(in_array($_product->id, $product_ids)){
	    		$product_in_cart = true;
	    	}
	    }

	    return $product_in_cart;
	}

	private function is_category_in_cart($category, $cart)
	{
	    //Check to see if user has product in cart
	    global $woocommerce;

	    $product_in_cart = false;

	    switch($category)
	    {
    		case "empty":
    			return $product_in_cart;
    			break;
			case "subscription":
				$category = 16;
				break;
			case "promotionalsubscription":
				$category = 17;
				break;
			case "backissue":
				$category = 15;
				break;
	    }

	    // start of the loop that fetches the cart items
	    foreach ( $cart as $cart_item_key => $values )
	    {
	        $_product = $values['data'];
	        $terms = get_the_terms( $_product->id, 'product_cat' );

	        $cat_ids = array();

	        foreach ($terms as $term)
	        {
	            $cat_ids[] = $term->term_id;
	        }

	        if(in_array($category, (array)$cat_ids))
	        {

	          //category is in cart!
	           $product_in_cart = true;
	        }
	    }

	    return $product_in_cart;
	}

	public function create_marketing_questions_posttype() {
	    register_post_type( 'marketing_questions',
	        array(
	            'labels' => array(
	                'name' => 'Marketing Questions',
	                'singular_name' => 'Marketing Questions',
	                'add_new' => 'Add New',
	                'add_new_item' => 'Add New Marketing Questions Review',
	                'edit' => 'Edit',
	                'edit_item' => 'Edit Marketing Questions Review',
	                'new_item' => 'New Marketing Questions Review',
	                'view' => 'View',
	                'view_item' => 'View Marketing Questions Review',
	                'search_items' => 'Search Marketing Questions Reviews',
	                'not_found' => 'No Marketing Questions found',
	                'not_found_in_trash' => 'No Marketing Questions found in Trash',
	                'parent' => 'Parent Marketing Questions Review'
	            ),
	 
	            'public' => true,
	            'show_in_menu' => true,
	            'menu_position' => 5,
	            'supports' => array('title'),
	            'taxonomies' => array( '' ),
	            'menu_icon' => plugins_url( 'images/image.png', __FILE__ ),
	            'has_archive' => true
	        )
	    );
	}

    public function create_meta_box( $post_type, $post ) {
        // http://codex.wordpress.org/Function_Reference/add_meta_box
        add_meta_box(
            'marketing_questions_meta_box', // (string) (required) HTML 'id' attribute of the edit screen section
            __( 'Marketing Questions', 'marketing_questions' ), // (string) (required) Title of the edit screen section, visible to user
            array( &$this, 'print_meta_box' ), // (callback) (required) Function that prints out the HTML for the edit screen section. The function name as a string, or, within a class, an array to call one of the class's methods.
            'marketing_questions', // (string) (required) The type of Write screen on which to show the edit screen section ('post', 'page', 'dashboard', 'link', 'attachment' or 'custom_post_type' where custom_post_type is the custom post type slug)
            'normal', // (string) (optional) The part of the page where the edit screen section should be shown ('normal', 'advanced', or 'side')
            'high' // (string) (optional) The priority within the context where the boxes should show ('high', 'core', 'default' or 'low')
        );
    }

    public function print_meta_box( $post, $metabox ) {
    	$orderController =  MyriadOrderController::instance();
    	$demographic_questions = $orderController->getDemographicQuestions();
        ?>
            <input type="hidden" name="meta_box_ids[]" value="<?php echo $metabox['id']; ?>" />
            <?php wp_nonce_field( 'save_' . $metabox['id'], $metabox['id'] . '_nonce' ); ?>

            <table class="form-table">

            <tr>
            	<th>
            		<label for="myriad_question_id"><?php _e( 'Myriad Question', 'myriad-question' ); ?></label>
            	</th>
	            <td>
	            	<select name="myriad_question_id" type="text" id="myriad_question_id" value="<?php echo get_post_meta($post->ID, 'myriad_question_id', true); ?>" class="regular-text">
	            		<?php foreach($demographic_questions as $demographic_question_id => $demographic_question): ?>
	            			<?php if(in_array((int)$demographic_question[0], array(1,2,13))): ?>
	            				<option value="<?php print $demographic_question_id; ?>|<?php print $demographic_question[0]; ?>" <?php if((int)get_post_meta($post->ID, 'myriad_question_id', true) == (int)$demographic_question_id): ?>SELECTED="selected"<?php endif;?>><?php print $demographic_question[1]; ?></option>
	            			<?php endif; ?>
	            		<?php endforeach; ?>
	            	</select>
	            </td>
	        </tr>

            <tr>
            	<th>
            		<label for="woocommcerce_product_type"><?php _e( 'Product Type', 'myriad-question' ); ?></label>
            	</th>
	            <td>
	            	<select name="woocommcerce_product_type" type="text" id="woocommcerce_product_type" class="regular-text">
	            		<option value="empty" <?php if(get_post_meta($post->ID, 'woocommcerce_product_type', true) == "empty"): ?>SELECTED<?php endif;?>>-- Select a category --</option>
	            		<option value="subscription" <?php if(get_post_meta($post->ID, 'woocommcerce_product_type', true) == "subscription"): ?>SELECTED<?php endif;?>>Subscription</option>
	            		<option value="backissue" <?php if(get_post_meta($post->ID, 'woocommcerce_product_type', true) == "backissue"): ?>SELECTED<?php endif;?>>Back Issue</option>
	            	</select>

	            </td>
	        </tr>     

            <tr>
            	<th>
            		<label for="woocommerce_product_ids"><?php _e( 'or Products', 'myriad-question' ); ?></label>
            	</th>
	            <td>
	            	<select name="woocommerce_product_ids[]" type="text" id="woocommerce_product_ids" value="<?php echo get_post_meta($post->ID, 'woocommerce_product_ids', true); ?>" class="regular-text" multiple>
						<?php
						$args = array('post_type' => 'product', 'posts_per_page' => -1, 'orderby' =>'date', 'order' => 'ASC');
						$loop = new WP_Query( $args );
						while ( $loop->have_posts() ) : $loop->the_post();
						global $product; 
						?>
							<option value="<?php print $product->post->ID; ?>" <?php if(in_array($product->post->ID, get_post_meta($post->ID, 'woocommerce_product_ids', true))): ?>SELECTED<?php endif; ?>><?php print $product->post->post_title; ?></option>
						<?php endwhile; wp_reset_query(); ?>
	            	</select>
	            </td>
	        </tr>   

            <tr>
            	<th>
            		<label for="woocommcerce_answer_required"><?php _e( 'Answer Required', 'myriad-question' ); ?></label>
            	</th>
	            <td>
	            	<select name="woocommcerce_answer_required" type="text" id="woocommcerce_answer_required" class="regular-text">
	            		<option value="yes" <?php if(get_post_meta($post->ID, 'woocommcerce_answer_required', true) == "yes"): ?>SELECTED<?php endif;?>>Yes</option>
	            		<option value="no" <?php if(get_post_meta($post->ID, 'woocommcerce_answer_required', true) == "no"): ?>SELECTED<?php endif;?>>No</option>
	            	</select>

	            </td>
	        </tr>     

            <tr>
            	<th>
            		<label for="woocommcerce_question_weight"><?php _e( 'Question Weight', 'myriad-question' ); ?></label>
            	</th>
	            <td>
	            	<input type="text" name="woocommcerce_question_weight" value="<?php print get_post_meta($post->ID, 'woocommcerce_question_weight', true) ?>" />

	            </td>
	        </tr>
        </table>

            <!-- These hidden fields are a registry of fields that need to be saved for each metabox. The field names correspond to the field name output above. -->
            <input type="hidden" name="<?php echo $metabox['id']; ?>_fields[]" value="myriad_question_id" />
            <input type="hidden" name="<?php echo $metabox['id']; ?>_fields[]" value="woocommcerce_product_type" />
            <input type="hidden" name="<?php echo $metabox['id']; ?>_fields[]" value="woocommerce_product_ids" />
            <input type="hidden" name="<?php echo $metabox['id']; ?>_fields[]" value="woocommcerce_question_weight" />
            <input type="hidden" name="<?php echo $metabox['id']; ?>_fields[]" value="woocommcerce_answer_required" />
        <?php
    }

    public function save_meta_box( $post_id, $post ) {
        // Check if this information is being submitted by means of an autosave; if so, then do not process any of the following code
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){ return; }

        // Determine if the postback contains any metaboxes that need to be saved
        if( empty( $_POST['meta_box_ids'] ) ){ return; }

        // Iterate through the registered metaboxes
        foreach( $_POST['meta_box_ids'] as $metabox_id ){
            // Verify thhe request to update this metabox
            if( ! wp_verify_nonce( $_POST[ $metabox_id . '_nonce' ], 'save_' . $metabox_id ) ){ continue; }

            // Determine if the metabox contains any fields that need to be saved
            if( count( $_POST[ $metabox_id . '_fields' ] ) == 0 ){ continue; }

            // Iterate through the registered fields        
            foreach( $_POST[ $metabox_id . '_fields' ] as $field_id ){
            	if($field_id == "myriad_question_id"){
            		$myriad_field = explode("|", $_POST[ $field_id ]);
                	update_post_meta($post_id, $field_id, $myriad_field[0]);
                	update_post_meta($post_id, "myriad_question_type", $myriad_field[1]);
            	} else {
					// Update or create the submitted contents of the fiels as post meta data
                	// http://codex.wordpress.org/Function_Reference/update_post_meta
                	update_post_meta($post_id, $field_id, $_POST[ $field_id ]);
            	}
            }
        }
        return $post;
    }

	public function register_marketing_questions_settings(){

	}

}