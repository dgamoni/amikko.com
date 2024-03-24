<?php
function custom_child_scripts() {


	wp_enqueue_style(
		'custom_core_style', 
		CORE_URL . '/css/custom_core_style.css',
		array(),
		rand()
	);

	wp_enqueue_style(
		'adaptive', 
		CORE_URL .  '/css/adaptive.css',
		array('custom_core_style'),
		rand()
	);

	wp_enqueue_script(
	    'custom_core',
	    CORE_URL . '/js/custom_core.js',
        array('jquery'), 
        rand(),
        true  
	);

	wp_localize_script( 'custom_script', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	
	// if ( is_page_template('template-custom.php') || is_post_type_archive('custom')  ) {
	// 	wp_enqueue_script(
	// 	    'custom_template_js',
	// 	    CORE_URL . '/js/custom_template.js',
	//         array('jquery'), 
	//         rand(),
	//         true  
	// 	);
	// 	wp_localize_script( 'custom_template_js', 'js_url', 
 //            array( 
 //                'imgurl' => CORE_URL.'/img/',
 //                'ajaxurl' => admin_url( 'admin-ajax.php' )
 //                 )
 //        );		
	// }

	
	
}
add_action( 'wp_enqueue_scripts', 'custom_child_scripts' ); 

function custom_admin_theme_style() {
    wp_enqueue_style('custom-admin-style', CORE_URL .'/css/custom_admin_style.css', array(), rand());
    //wp_enqueue_script('custom_admin_script',  CORE_URL . '/js/custom_admin_js.js', array('jquery'), rand(), true );

}
//add_action('admin_enqueue_scripts', 'custom_admin_theme_style');