<?php
/*
Template Name: Stock Report :)
*/
if (!is_user_logged_in() || !current_user_can('edit_posts')) wp_die('This page is private.');
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php _e('Stock Report'); ?></title>
	<style>
		body { background:white; color:black; width: 95%; margin: 0 auto; }
		table { border: 1px solid #000; width: 100%; }
		table td, table th { border: 1px solid #000; padding: 6px; }
	</style>
</head>
<body>
	<header>
		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		
		    <h1 class="title"><?php the_title(); ?></h1>
		
			<?php the_content(); ?>
			
		<?php endwhile; endif; ?>
	</header>
	<section>
	<?php 

	global $woocommerce;
	?>
	<table cellspacing="0" cellpadding="2">
		<thead>
			<tr>	
				<th scope="col" style="text-align:left;"><?php _e('Product', 'woothemes'); ?></th>
				<th scope="col" style="text-align:left;"><?php _e('SKU', 'woothemes'); ?></th>
				<th scope="col" style="text-align:left;"><?php _e('Stock', 'woothemes'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php

		$args = array(
			'post_type'			=> 'product',
			'post_status' 		=> 'publish',
	        'posts_per_page' 	=> -1,
	        'orderby'			=> 'title',
	        'order'				=> 'ASC',
			'meta_query' 		=> array(
	            array(
	                'key' 	=> '_manage_stock',
	                'value' => 'yes'
	            )
	        ),
			'tax_query' => array(
				array(
					'taxonomy' 	=> 'product_type',
					'field' 	=> 'slug',
					'terms' 	=> array('simple'),
					'operator' 	=> 'IN'
				)
			)
		);
		
		$loop = new WP_Query( $args );
	
		while ( $loop->have_posts() ) : $loop->the_post();
		
                        global $product;
			?>
			<tr>
				<td><?php echo $product->get_title(); ?></td>
				<td><?php echo $product->sku; ?></td>
				<td><?php echo $product->stock; ?></td>
			</tr>
			<?php
		endwhile; 
		
		?>
		</tbody>
	</table>
	
	<h2>Variations</h2>
	<table cellspacing="0" cellpadding="2">
		<thead>
			<tr>
				<th scope="col" style="text-align:left;"><?php _e('Variation', 'woothemes'); ?></th>
				<th scope="col" style="text-align:left;"><?php _e('Parent', 'woothemes'); ?></th>
				<th scope="col" style="text-align:left;"><?php _e('SKU', 'woothemes'); ?></th>
				<th scope="col" style="text-align:left;"><?php _e('Stock', 'woothemes'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		
		$args = array(
			'post_type'			=> 'product_variation',
			'post_status' 		=> 'publish',
	        'posts_per_page' 	=> -1,
	        'orderby'			=> 'title',
	        'order'				=> 'ASC',
			'meta_query' => array(
				array(
					'key' 		=> '_stock',
					'value' 	=> array('', false, null),
					'compare' 	=> 'NOT IN'
				)
			)
		);
		
		$loop = new WP_Query( $args );
	
		while ( $loop->have_posts() ) : $loop->the_post();
		
                        $product = new WC_Product_Variation( $loop->post->ID );
			?>
			<tr>
				<td><?php echo $product->get_title(); ?></td>
				<td><?php echo get_the_title( $loop->post->post_parent ); ?></td>
				<td><?php echo $product->sku; ?></td>
				<td><?php echo $product->stock; ?></td>
			</tr>
			<?php
		endwhile; 
		
		?>
		</tbody>
	</table>
</body>
</html>